<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Enum\MessageRole;
use Tourze\DifyClientBundle\Enum\MessageStatus;
use Tourze\DifyClientBundle\Event\DifyErrorEvent;
use Tourze\DifyClientBundle\Event\DifyReplyEvent;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;
use Tourze\DifyClientBundle\Exception\DifySettingNotFoundException;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;
use Tourze\DifyClientBundle\Repository\MessageRepository;

/**
 * 文本生成服务
 *
 * 提供单次文本生成功能，不依赖会话上下文
 * 对应 API: POST /completion-messages, DELETE /completion-messages/{task_id}
 */
final readonly class CompletionService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EventDispatcherInterface $eventDispatcher,
        private DifySettingRepository $settingRepository,
        private MessageRepository $messageRepository,
        private ClockInterface $clock,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 发送文本生成请求
     * @param array<string, mixed> $inputs
     * @param array<string, mixed>|null $files
     */
    public function generateCompletion(
        string $prompt,
        array $inputs = [],
        string $userId = 'system',
        ?array $files = null,
    ): Message {
        $setting = $this->getActiveSetting();

        // 创建提示消息
        $promptMessage = $this->createPromptMessage($prompt, $userId, $inputs, $files);
        $this->persistMessage($promptMessage);

        try {
            $response = $this->sendCompletionRequest($setting, $prompt, $inputs, $userId, $files);
            $replyContent = $this->parseResponse($response);

            // 创建生成的回复消息
            $completionMessage = $this->createCompletionMessage($promptMessage, $replyContent, $response);
            $this->persistMessage($completionMessage);

            // 标记提示消息为已发送
            $promptMessage->setStatus(MessageStatus::SENT);
            $promptMessage->setSentAt($this->clock->now());
            $this->persistMessage($promptMessage);

            // 触发成功事件
            $this->eventDispatcher->dispatch(new DifyReplyEvent(
                null, // 文本生成没有会话上下文
                $replyContent,
                $promptMessage,
                true
            ));

            return $completionMessage;
        } catch (\Exception $e) {
            $this->handleCompletionFailure($promptMessage, $e);
            throw $e;
        }
    }

    /**
     * 流式文本生成
     * @param array<string, mixed> $inputs
     * @param array<string, mixed>|null $files
     */
    public function generateStreamCompletion(
        string $prompt,
        array $inputs = [],
        string $userId = 'system',
        ?array $files = null,
    ): \Generator {
        $setting = $this->getActiveSetting();

        $promptMessage = $this->createPromptMessage($prompt, $userId, $inputs, $files);
        $this->persistMessage($promptMessage);

        try {
            $response = $this->sendStreamCompletionRequest($setting, $prompt, $inputs, $userId, $files);
            $fullReply = '';

            foreach ($response as $chunk) {
                $chunkStr = is_string($chunk) ? $chunk : '';
                $replyContent = $this->parseStreamChunk($chunkStr);
                if ('' !== $replyContent) {
                    $fullReply .= $replyContent;

                    // 触发流式回复事件
                    $this->eventDispatcher->dispatch(new DifyReplyEvent(
                        null,
                        $replyContent,
                        $promptMessage,
                        false
                    ));

                    yield $replyContent;
                }
            }

            // 保存完整的生成回复
            $completionMessage = $this->createCompletionMessage($promptMessage, $fullReply);
            $this->persistMessage($completionMessage);

            $promptMessage->setStatus(MessageStatus::SENT);
            $promptMessage->setSentAt($this->clock->now());
            $this->persistMessage($promptMessage);

            // 触发完整回复事件
            $this->eventDispatcher->dispatch(new DifyReplyEvent(
                null,
                $fullReply,
                $promptMessage,
                true
            ));
        } catch (\Exception $e) {
            $this->handleCompletionFailure($promptMessage, $e);
            throw $e;
        }
    }

    /**
     * 停止文本生成
     */
    public function stopCompletion(string $taskId): void
    {
        $setting = $this->getActiveSetting();

        try {
            $this->sendStopRequest($setting, $taskId);
        } catch (\Exception $e) {
            throw new DifyRuntimeException(sprintf('Failed to stop completion for task %s: %s', $taskId, $e->getMessage()));
        }
    }

    /**
     * 获取用户的文本生成历史
     *
     * @return array<Message>
     */
    public function getUserCompletions(string $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->messageRepository->findBy(
            [
                'metadata' => ['user_id' => $userId, 'type' => 'completion'],
                'role' => MessageRole::USER,
            ],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * 根据提示消息查找对应的生成结果
     */
    public function findCompletionByPrompt(Message $promptMessage): ?Message
    {
        return $this->messageRepository->findOneBy([
            'metadata' => ['prompt_message_id' => $promptMessage->getId()],
            'role' => MessageRole::ASSISTANT,
        ]);
    }

    /**
     * 获取文本生成统计信息
     * @return array<string, mixed>
     */
    public function getCompletionStats(): array
    {
        $qb = $this->messageRepository->createQueryBuilder('m');

        // 查找文本生成相关的消息
        $qb->where("JSON_EXTRACT(m.metadata, '$.type') = :type")
            ->setParameter('type', 'completion')
        ;

        $totalCompletions = (int) $qb
            ->select('COUNT(m.id)')
            ->andWhere('m.role = :role')
            ->setParameter('role', MessageRole::USER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $successfulCompletions = (int) $qb
            ->select('COUNT(m.id)')
            ->andWhere('m.role = :role')
            ->andWhere('m.status = :status')
            ->setParameter('role', MessageRole::USER)
            ->setParameter('status', MessageStatus::SENT)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $failedCompletions = $totalCompletions - $successfulCompletions;

        // 最近7天的生成趋势
        $sevenDaysAgo = $this->clock->now()->modify('-7 days');
        $dailyStats = $qb
            ->select('DATE(m.createdAt) as date, COUNT(m.id) as count')
            ->andWhere('m.createdAt >= :sevenDaysAgo')
            ->andWhere('m.role = :role')
            ->setParameter('sevenDaysAgo', $sevenDaysAgo)
            ->setParameter('role', MessageRole::USER)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        return [
            'total_completions' => $totalCompletions,
            'successful_completions' => $successfulCompletions,
            'failed_completions' => $failedCompletions,
            'success_rate' => $totalCompletions > 0 ? round($successfulCompletions / $totalCompletions * 100, 2) : 0,
            'daily_stats_7_days' => $dailyStats,
        ];
    }

    /**
     * 清理过期的文本生成记录
     */
    public function cleanupOldCompletions(\DateInterval $expiry): int
    {
        $expiredDate = $this->clock->now()->sub($expiry);

        $qb = $this->messageRepository->createQueryBuilder('m');
        /** @var array<Message> $expiredMessages */
        $expiredMessages = $qb
            ->where("JSON_EXTRACT(m.metadata, '$.type') = :type")
            ->andWhere('m.createdAt < :expiredDate')
            ->setParameter('type', 'completion')
            ->setParameter('expiredDate', $expiredDate)
            ->getQuery()
            ->getResult()
        ;

        $cleanupCount = 0;

        foreach ($expiredMessages as $message) {
            $this->entityManager->remove($message);
            ++$cleanupCount;
        }

        if ($cleanupCount > 0) {
            $this->entityManager->flush();
        }

        return $cleanupCount;
    }

    private function getActiveSetting(): DifySetting
    {
        $setting = $this->settingRepository->findActiveSetting();
        if (null === $setting) {
            throw new DifySettingNotFoundException();
        }

        return $setting;
    }

    /**
     * @param array<string, mixed> $inputs
     * @param array<string, mixed>|null $files
     */
    private function createPromptMessage(string $prompt, string $userId, array $inputs, ?array $files): Message
    {
        $message = new Message();
        $message->setRole(MessageRole::USER);
        $message->setContent($prompt);
        $message->setStatus(MessageStatus::PENDING);
        $message->setCreateTime($this->clock->now());

        // 设置metadata标识这是文本生成
        $metadata = [
            'user_id' => $userId,
            'type' => 'completion',
            'inputs' => $inputs,
        ];

        if ([] !== $files && null !== $files) {
            $metadata['files'] = $files;
        }

        $message->setMetadata($metadata);

        return $message;
    }

    /**
     * @param array<string, mixed>|null $response
     */
    private function createCompletionMessage(Message $promptMessage, string $content, ?array $response = null): Message
    {
        $message = new Message();
        $message->setRole(MessageRole::ASSISTANT);
        $message->setContent($content);
        $message->setStatus(MessageStatus::RECEIVED);
        $message->setReceivedAt($this->clock->now());

        // 关联到提示消息
        $metadata = [
            'prompt_message_id' => $promptMessage->getId(),
            'type' => 'completion',
        ];

        // 保存 Dify message ID
        if (is_array($response) && isset($response['message_id'])) {
            $metadata['dify_message_id'] = $response['message_id'];
        }

        if (is_array($response) && isset($response['task_id'])) {
            $metadata['task_id'] = $response['task_id'];
        }

        $message->setMetadata($metadata);

        return $message;
    }

    private function persistMessage(Message $message): void
    {
        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $inputs
     * @param array<string, mixed>|null $files
     * @return array<string, mixed>
     */
    private function sendCompletionRequest(
        DifySetting $setting,
        string $prompt,
        array $inputs,
        string $userId,
        ?array $files,
    ): array {
        $url = rtrim($setting->getBaseUrl(), '/') . '/completion-messages';

        $payload = [
            'inputs' => $inputs,
            'query' => $prompt,
            'response_mode' => 'blocking',
            'user' => $userId,
        ];

        if ([] !== $files && null !== $files) {
            $payload['files'] = $files;
        }

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'timeout' => $setting->getTimeout(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Dify completion request failed: ' . $response->getContent(false));
        }

        /** @var array<string, mixed> */
        return $response->toArray();
    }

    /**
     * @param array<string, mixed> $inputs
     * @param array<string, mixed>|null $files
     */
    private function sendStreamCompletionRequest(
        DifySetting $setting,
        string $prompt,
        array $inputs,
        string $userId,
        ?array $files,
    ): \Generator {
        $url = rtrim($setting->getBaseUrl(), '/') . '/completion-messages';

        $payload = [
            'inputs' => $inputs,
            'query' => $prompt,
            'response_mode' => 'streaming',
            'user' => $userId,
        ];

        if ([] !== $files && null !== $files) {
            $payload['files'] = $files;
        }

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'timeout' => $setting->getTimeout(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Dify completion stream request failed: ' . $response->getContent(false));
        }

        foreach ($this->httpClient->stream($response) as $chunk) {
            yield $chunk->getContent();
        }
    }

    private function sendStopRequest(DifySetting $setting, string $taskId): void
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/completion-messages/' . $taskId;

        $response = $this->httpClient->request('DELETE', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
            ],
            'timeout' => $setting->getTimeout(),
        ]);

        if (204 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Failed to stop Dify completion: ' . $response->getContent(false));
        }
    }

    /** @param array<string, mixed> $data */
    private function parseResponse(array $data): string
    {
        $answer = $data['answer'] ?? '';

        return is_string($answer) ? $answer : '';
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

    private function handleCompletionFailure(Message $promptMessage, \Exception $e): void
    {
        $promptMessage->setStatus(MessageStatus::FAILED);
        $promptMessage->setErrorMessage($e->getMessage());
        $promptMessage->setRetryCount($promptMessage->getRetryCount() + 1);
        $this->persistMessage($promptMessage);

        $this->eventDispatcher->dispatch(new DifyErrorEvent(
            null,
            $promptMessage,
            $e->getMessage(),
            $e
        ));
    }
}
