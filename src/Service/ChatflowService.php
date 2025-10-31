<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpClient\CurlHttpClient;
use Tourze\DifyClientBundle\Entity\AppInfo;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Enum\ConversationStatus;
use Tourze\DifyClientBundle\Enum\MessageRole;
use Tourze\DifyClientBundle\Enum\MessageStatus;
use Tourze\DifyClientBundle\Event\DifyErrorEvent;
use Tourze\DifyClientBundle\Event\DifyReplyEvent;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;
use Tourze\DifyClientBundle\Repository\AppInfoRepository;
use Tourze\DifyClientBundle\Repository\ConversationRepository;
use Tourze\DifyClientBundle\Repository\ConversationVariableRepository;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;
use Tourze\DifyClientBundle\Repository\MessageRepository;
use Tourze\DifyClientBundle\Repository\SuggestedQuestionRepository;

/**
 * 对话流核心服务
 *
 * 核心业务逻辑，HTTP通信由ChatflowHttpClient处理
 */
readonly class ChatflowService
{
    private ChatflowHttpClient $httpClient;

    private MessageFactory $messageFactory;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private DifySettingRepository $settingRepository,
        private ConversationRepository $conversationRepository,
        private MessageRepository $messageRepository,
        private ConversationVariableRepository $conversationVariableRepository,
        private SuggestedQuestionRepository $suggestedQuestionRepository,
        private AppInfoRepository $appInfoRepository,
        private ClockInterface $clock,
        ?ChatflowHttpClient $httpClient = null,
        ?MessageFactory $messageFactory = null,
    ) {
        $this->httpClient = $httpClient ?? new ChatflowHttpClient(new CurlHttpClient(), $this->settingRepository);
        $this->messageFactory = $messageFactory ?? new MessageFactory($this->clock);
    }

    // ==================== 核心业务方法 ====================

    /**
     * 发送聊天消息到 Dify
     */
    public function sendMessage(string $content, ?Conversation $conversation = null, string $userId = 'system'): Message
    {
        $conversation ??= $this->createNewConversation();
        $userMessage = $this->messageFactory->createUserMessage($conversation, $content, $userId);
        $this->messageRepository->save($userMessage);

        return $this->executeBlockingMessage($conversation, $content, $userId, $userMessage);
    }

    /**
     * 发送流式聊天消息
     */
    public function sendStreamMessage(string $content, ?Conversation $conversation = null, string $userId = 'system'): \Generator
    {
        $conversation ??= $this->createNewConversation();
        $userMessage = $this->messageFactory->createUserMessage($conversation, $content, $userId);
        $this->messageRepository->save($userMessage);

        return $this->executeStreamingMessage($conversation, $content, $userId, $userMessage);
    }

    /**
     * 获取会话历史记录
     * @return array<Message>
     */
    public function getConversationHistory(Conversation $conversation, int $limit = 50, int $offset = 0): array
    {
        return $this->messageRepository->findConversationHistory($conversation, $limit, $offset);
    }

    /**
     * 创建新会话
     */
    public function createNewConversation(?string $name = null): Conversation
    {
        $conversation = new Conversation();
        $conversation->setName($name ?? '新会话');
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $conversation->setCreateTime($this->clock->now());

        $this->conversationRepository->save($conversation);

        return $conversation;
    }

    /**
     * 删除会话
     */
    public function deleteConversation(Conversation $conversation): void
    {
        $conversationId = $conversation->getConversationId();

        if (null !== $conversationId) {
            try {
                $this->httpClient->deleteConversation($conversationId);
            } catch (\Exception $e) {
                error_log(sprintf('Failed to delete Dify conversation %s: %s', $conversationId, $e->getMessage()));
            }
        }

        $conversation->setStatus(ConversationStatus::ARCHIVED);
        $conversation->setArchivedAt($this->clock->now());
        $this->conversationRepository->save($conversation);
    }

    /**
     * 重命名会话
     */
    public function renameConversation(Conversation $conversation, string $newName): Conversation
    {
        $conversationId = $conversation->getConversationId();

        if (null !== $conversationId) {
            try {
                $this->httpClient->renameConversation($conversationId, $newName);
            } catch (\Exception $e) {
                error_log(sprintf('Failed to rename Dify conversation %s: %s', $conversationId, $e->getMessage()));
            }
        }

        $conversation->setName($newName);
        $this->conversationRepository->save($conversation);

        return $conversation;
    }

    /**
     * 停止响应
     */
    public function stopResponse(string $taskId): void
    {
        $this->httpClient->stopResponse($taskId);
    }

    /**
     * 获取下一轮建议问题列表
     * @return array<string>
     */
    public function getSuggestedQuestions(Message $message): array
    {
        // 首先从本地数据库查找
        $localQuestions = $this->suggestedQuestionRepository->findByMessage($message);
        if ([] !== $localQuestions) {
            return array_map(fn ($q) => $q->getQuestion(), $localQuestions);
        }

        // 从 Dify API 获取
        $difyMessageId = $this->extractDifyMessageId($message);
        if (null === $difyMessageId) {
            return [];
        }

        try {
            $response = $this->httpClient->getSuggestedQuestions($difyMessageId);
            $data = $response['data'] ?? [];

            /** @var array<string> */
            return is_array($data) ? $data : [];
        } catch (\Exception $e) {
            error_log(sprintf('Failed to fetch suggested questions: %s', $e->getMessage()));

            return [];
        }
    }

    /**
     * 获取会话列表
     * @return array<Conversation>
     */
    public function getConversations(int $limit = 50, int $offset = 0, bool $activeOnly = true): array
    {
        $criteria = [];
        if ($activeOnly) {
            $criteria['status'] = ConversationStatus::ACTIVE;
        }

        return $this->conversationRepository->findBy(
            $criteria,
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * 获取对话变量
     * @return array<string, mixed>
     */
    public function getConversationVariables(Conversation $conversation): array
    {
        // 从本地获取
        $localVariables = $this->conversationVariableRepository->findByConversation($conversation);
        $variables = [];

        foreach ($localVariables as $variable) {
            $variables[$variable->getName()] = $variable->getValue();
        }

        // 如果有 conversationId，从 Dify 同步获取最新变量
        $conversationId = $conversation->getConversationId();
        if (null !== $conversationId) {
            try {
                $response = $this->httpClient->getConversationVariables($conversationId);
                $difyVariables = $response['data'] ?? [];

                // 合并变量（Dify 的为准）
                if (is_array($difyVariables)) {
                    /** @var array<string, mixed> $difyVariables */
                    $variables = array_merge($variables, $difyVariables);
                }
            } catch (\Exception $e) {
                error_log(sprintf('Failed to fetch conversation variables: %s', $e->getMessage()));
            }
        }

        return $variables;
    }

    // ==================== 应用信息方法 ====================

    /**
     * 获取应用基本信息（优先从本地缓存获取）
     *
     * @return array<string, mixed>
     */
    public function getAppInfo(bool $forceRefresh = false): array
    {
        $appId = $this->generateAppIdFromApiKey();

        return $this->getAppInfoWithCache($appId, $forceRefresh);
    }

    /**
     * 获取应用参数
     *
     * @return array<string, mixed>
     */
    public function getAppParameters(): array
    {
        return $this->httpClient->getAppParameters();
    }

    /**
     * 获取应用meta信息
     *
     * @return array<string, mixed>
     */
    public function getAppMeta(): array
    {
        return $this->httpClient->getAppMeta();
    }

    /**
     * 获取应用webapp设置
     *
     * @return array<string, mixed>
     */
    public function getAppSite(): array
    {
        return $this->httpClient->getAppSite();
    }

    // ==================== 私有辅助方法 ====================

    private function executeBlockingMessage(
        Conversation $conversation,
        string $content,
        string $userId,
        Message $userMessage,
    ): Message {
        try {
            $response = $this->httpClient->sendChatMessage(
                $conversation->getConversationId() ?? '',
                $content,
                $userId
            );

            $rawReply = $response['answer'] ?? '';
            $replyContent = is_string($rawReply) ? $rawReply : '';

            $this->updateConversationId($conversation, $response);
            $assistantMessage = $this->messageFactory->createAssistantMessage($conversation, $replyContent, $response);
            $this->messageRepository->save($assistantMessage);

            $this->completeMessageSending($userMessage);
            $this->dispatchSuccessEvent($conversation, $replyContent, $userMessage);

            return $assistantMessage;
        } catch (\Exception $e) {
            $this->handleMessageFailure($userMessage, $conversation, $e);
            throw $e;
        }
    }

    private function executeStreamingMessage(
        Conversation $conversation,
        string $content,
        string $userId,
        Message $userMessage,
    ): \Generator {
        try {
            $response = $this->httpClient->sendStreamChatMessage(
                $conversation->getConversationId() ?? '',
                $content,
                $userId
            );

            $fullReply = '';

            foreach ($response as $chunk) {
                $chunkStr = is_string($chunk) ? $chunk : '';
                $replyContent = $this->parseStreamChunk($chunkStr);
                if ('' !== $replyContent) {
                    $fullReply .= $replyContent;
                    $this->dispatchPartialReplyEvent($conversation, $replyContent, $userMessage);
                    yield $replyContent;
                }
            }

            $this->finishStreamingMessage($conversation, $fullReply, $userMessage);
        } catch (\Exception $e) {
            $this->handleMessageFailure($userMessage, $conversation, $e);
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $response
     */
    private function updateConversationId(Conversation $conversation, array $response): void
    {
        if (null === $conversation->getConversationId() && isset($response['conversation_id'])) {
            $conversationId = $response['conversation_id'];
            if (is_string($conversationId)) {
                $conversation->setConversationId($conversationId);
                $this->conversationRepository->save($conversation);
            }
        }
    }

    private function parseStreamChunk(string $chunk): string
    {
        $lines = explode("\n", trim($chunk));

        foreach ($lines as $line) {
            $answer = $this->extractAnswerFromLine($line);
            if (null !== $answer) {
                return $answer;
            }
        }

        return '';
    }

    private function extractAnswerFromLine(string $line): ?string
    {
        if (!str_starts_with($line, 'data: ')) {
            return null;
        }

        $data = substr($line, 6);
        if ('[DONE]' === $data) {
            return '';
        }

        return $this->decodeJsonAnswer($data);
    }

    private function decodeJsonAnswer(string $jsonData): ?string
    {
        $decoded = json_decode($jsonData, true);
        if (JSON_ERROR_NONE !== json_last_error() || !is_array($decoded) || !isset($decoded['answer'])) {
            return null;
        }

        $answer = $decoded['answer'];

        return is_string($answer) ? $answer : null;
    }

    private function completeMessageSending(Message $userMessage): void
    {
        $userMessage->setStatus(MessageStatus::SENT);
        $userMessage->setSentAt($this->clock->now());
        $this->messageRepository->save($userMessage);
    }

    private function finishStreamingMessage(Conversation $conversation, string $fullReply, Message $userMessage): void
    {
        $assistantMessage = $this->messageFactory->createAssistantMessage($conversation, $fullReply);
        $this->messageRepository->save($assistantMessage);

        $this->completeMessageSending($userMessage);
        $this->dispatchSuccessEvent($conversation, $fullReply, $userMessage);
    }

    private function handleMessageFailure(Message $userMessage, Conversation $conversation, \Exception $e): void
    {
        $userMessage->setStatus(MessageStatus::FAILED);
        $userMessage->setErrorMessage($e->getMessage());
        $userMessage->setRetryCount($userMessage->getRetryCount() + 1);
        $this->messageRepository->save($userMessage);

        $this->eventDispatcher->dispatch(new DifyErrorEvent(
            $conversation,
            $userMessage,
            $e->getMessage(),
            $e
        ));
    }

    private function dispatchSuccessEvent(Conversation $conversation, string $replyContent, Message $userMessage): void
    {
        $this->eventDispatcher->dispatch(new DifyReplyEvent(
            $conversation,
            $replyContent,
            $userMessage,
            true
        ));
    }

    private function dispatchPartialReplyEvent(Conversation $conversation, string $replyContent, Message $userMessage): void
    {
        $this->eventDispatcher->dispatch(new DifyReplyEvent(
            $conversation,
            $replyContent,
            $userMessage,
            false
        ));
    }

    private function extractDifyMessageId(Message $message): ?string
    {
        $messageMetadata = $message->getMetadata() ?? [];
        $difyMessageId = $messageMetadata['dify_message_id'] ?? null;

        return (is_string($difyMessageId) && '' !== $difyMessageId) ? $difyMessageId : null;
    }

    private function generateAppIdFromApiKey(): string
    {
        $setting = $this->settingRepository->findActiveSetting();
        if (null === $setting) {
            throw new DifyRuntimeException('No active setting found');
        }

        return substr(hash('sha256', $setting->getApiKey()), 0, 16);
    }

    /**
     * @return array<string, mixed>
     */
    private function getAppInfoWithCache(string $appId, bool $forceRefresh): array
    {
        if (!$forceRefresh) {
            $cachedAppInfo = $this->appInfoRepository->findByAppId($appId);
            if (null !== $cachedAppInfo) {
                return $this->convertAppInfoToArray($cachedAppInfo);
            }
        }

        $appInfoData = $this->httpClient->getAppInfo();
        $this->saveAppInfoToCache($appId, $appInfoData);

        return $appInfoData;
    }

    /**
     * @return array<string, mixed>
     */
    private function convertAppInfoToArray(AppInfo $appInfo): array
    {
        return [
            'id' => $appInfo->getAppId(),
            'name' => $appInfo->getName(),
            'mode' => $appInfo->getMode(),
            'description' => $appInfo->getDescription(),
            'icon' => $appInfo->getIcon(),
            'icon_background' => $appInfo->getIconBackground(),
            'enable_site' => $appInfo->isEnableSite(),
            'enable_api' => $appInfo->isEnableApi(),
            'metadata' => $appInfo->getMetadata(),
        ];
    }

    /**
     * @param array<string, mixed> $appInfoData
     */
    private function saveAppInfoToCache(string $appId, array $appInfoData): void
    {
        try {
            $appInfo = $this->appInfoRepository->findByAppId($appId);
            if (null === $appInfo) {
                $appInfo = new AppInfo();
                $appInfo->setAppId($appId);
                $appInfo->setCreateTime($this->clock->now());
            }

            // 更新应用信息
            $name = $appInfoData['name'] ?? 'Unknown App';
            $appInfo->setName(is_string($name) ? $name : 'Unknown App');

            $mode = $appInfoData['mode'] ?? 'chat';
            $appInfo->setMode(is_string($mode) ? $mode : 'chat');

            $description = $appInfoData['description'] ?? null;
            $appInfo->setDescription(is_string($description) ? $description : null);

            $icon = $appInfoData['icon'] ?? null;
            /** @var array<string, mixed>|null $validIcon */
            $validIcon = is_array($icon) ? $icon : null;
            $appInfo->setIcon($validIcon);

            $iconBg = $appInfoData['icon_background'] ?? null;
            $appInfo->setIconBackground(is_string($iconBg) ? $iconBg : null);

            $enableSite = $appInfoData['enable_site'] ?? true;
            $appInfo->setEnableSite(is_bool($enableSite) ? $enableSite : true);

            $enableApi = $appInfoData['enable_api'] ?? true;
            $appInfo->setEnableApi(is_bool($enableApi) ? $enableApi : true);

            $appInfo->setMetadata($appInfoData);

            $this->appInfoRepository->save($appInfo);
        } catch (\Exception $e) {
            // 缓存失败不应影响主要功能，只记录错误
            error_log(sprintf('Failed to cache app info: %s', $e->getMessage()));
        }
    }
}
