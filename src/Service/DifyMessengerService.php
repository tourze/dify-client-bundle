<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Entity\FailedMessage;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\RequestTask;
use Tourze\DifyClientBundle\Enum\MessageRole;
use Tourze\DifyClientBundle\Enum\MessageStatus;
use Tourze\DifyClientBundle\Event\DifyErrorEvent;
use Tourze\DifyClientBundle\Event\DifyReplyEvent;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;
use Tourze\DifyClientBundle\Exception\DifySettingNotFoundException;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;
use Tourze\DifyClientBundle\Repository\RequestTaskRepository;

readonly class DifyMessengerService
{
    public function __construct(
        private MessageAggregator $aggregator,
        private HttpClientInterface $httpClient,
        private EventDispatcherInterface $eventDispatcher,
        private DifySettingRepository $settingRepository,
        private RequestTaskRepository $requestTaskRepository,
        private ClockInterface $clock,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function push(string $message): void
    {
        $this->aggregator->addMessage($message);
    }

    public function pushStream(string $message): void
    {
        $setting = $this->settingRepository->findActiveSetting();
        if (null === $setting) {
            throw new DifySettingNotFoundException();
        }

        $conversation = $this->getOrCreateConversation();

        $userMessage = new Message();
        $userMessage->setConversation($conversation);
        $userMessage->setRole(MessageRole::USER);
        $userMessage->setContent($message);
        $userMessage->setStatus(MessageStatus::SENT);
        $userMessage->setSentAt($this->clock->now());

        $this->entityManager->persist($userMessage);
        $this->entityManager->flush();

        try {
            $response = $this->sendStreamRequest($setting, $conversation, $message);

            $fullReply = '';
            foreach ($response as $chunk) {
                $chunkStr = is_string($chunk) ? $chunk : '';
                $replyContent = $this->parseStreamChunk($chunkStr);
                if ('' !== $replyContent) {
                    $fullReply .= $replyContent;

                    $this->eventDispatcher->dispatch(new DifyReplyEvent(
                        $conversation,
                        $replyContent,
                        $userMessage,
                        false
                    ));
                }
            }

            $assistantMessage = new Message();
            $assistantMessage->setConversation($conversation);
            $assistantMessage->setRole(MessageRole::ASSISTANT);
            $assistantMessage->setContent($fullReply);
            $assistantMessage->setStatus(MessageStatus::RECEIVED);
            $assistantMessage->setReceivedAt($this->clock->now());

            $this->entityManager->persist($assistantMessage);
            $this->entityManager->flush();

            $this->eventDispatcher->dispatch(new DifyReplyEvent(
                $conversation,
                $fullReply,
                $userMessage,
                true
            ));
        } catch (\Exception $e) {
            $userMessage->setStatus(MessageStatus::FAILED);
            $userMessage->setErrorMessage($e->getMessage());
            $userMessage->setRetryCount($userMessage->getRetryCount() + 1);

            // 创建 FailedMessage 记录
            $failedMessage = new FailedMessage();
            $failedMessage->setConversation($conversation);
            $failedMessage->setMessage($userMessage);
            $failedMessage->setError($e->getMessage());
            $failedMessage->setAttempts($userMessage->getRetryCount());
            $failedMessage->setContext([
                'exception_class' => get_class($e),
                'exception_code' => $e->getCode(),
                'original_message_content' => $userMessage->getContent(),
                'original_message_role' => $userMessage->getRole()->value,
                'request_type' => 'stream',
            ]);

            $this->entityManager->persist($userMessage);
            $this->entityManager->persist($failedMessage);
            $this->entityManager->flush();

            $this->eventDispatcher->dispatch(new DifyErrorEvent(
                $conversation,
                $userMessage,
                $e->getMessage(),
                $e
            ));
        }
    }

    /** @param array<Message> $originalMessages */
    public function processMessage(RequestTask $requestTask, string $content, array $originalMessages): void
    {
        $context = $this->validateAndPrepareProcessing($requestTask);

        try {
            $this->processMessageSuccessfully($requestTask, $content, $originalMessages, $context);
        } catch (\Exception $e) {
            $conversation = $context['conversation'];
            assert($conversation instanceof Conversation);
            $this->handleProcessingFailure($requestTask, $originalMessages, $conversation, $e);
        }
    }

    /** @return array<string, mixed> */
    private function validateAndPrepareProcessing(RequestTask $requestTask): array
    {
        $setting = $this->settingRepository->findActiveSetting();
        if (null === $setting) {
            throw new DifySettingNotFoundException();
        }

        $firstMessage = $requestTask->getMessages()->first();
        if (false === $firstMessage) {
            throw new DifyRuntimeException('No conversation found for request task');
        }

        return [
            'setting' => $setting,
            'conversation' => $firstMessage->getConversation(),
        ];
    }

    /**
     * @param array<Message> $originalMessages
     * @param array<string, mixed> $context
     */
    private function processMessageSuccessfully(RequestTask $requestTask, string $content, array $originalMessages, array $context): void
    {
        $this->requestTaskRepository->markTaskAsProcessing($requestTask);

        $setting = $context['setting'];
        $conversation = $context['conversation'];
        assert($setting instanceof DifySetting);
        assert($conversation instanceof Conversation);

        $response = $this->sendRequest($setting, $conversation, $content);
        $replyContent = $this->parseResponse($response);

        $this->updateOriginalMessages($originalMessages);
        $assistantMessage = $this->createAssistantMessage($conversation, $replyContent, $requestTask, $originalMessages);

        $this->persistSuccessfulProcessing($originalMessages, $assistantMessage, $requestTask, $replyContent);
        $this->dispatchSuccessEvent($conversation, $replyContent, $originalMessages);
    }

    /** @param array<Message> $originalMessages */
    private function updateOriginalMessages(array $originalMessages): void
    {
        foreach ($originalMessages as $message) {
            $message->setStatus(MessageStatus::SENT);
            $message->setSentAt($this->clock->now());
        }
    }

    /** @param array<Message> $originalMessages */
    private function createAssistantMessage(Conversation $conversation, string $replyContent, RequestTask $requestTask, array $originalMessages): Message
    {
        $assistantMessage = new Message();
        $assistantMessage->setConversation($conversation);
        $assistantMessage->setRole(MessageRole::ASSISTANT);
        $assistantMessage->setContent($replyContent);
        $assistantMessage->setStatus(MessageStatus::RECEIVED);
        $assistantMessage->setReceivedAt($this->clock->now());
        $assistantMessage->setMetadata([
            'request_task_id' => $requestTask->getId(),
            'original_messages' => array_map(fn ($msg) => $msg->getId(), $originalMessages),
        ]);

        return $assistantMessage;
    }

    /** @param array<Message> $originalMessages */
    private function persistSuccessfulProcessing(array $originalMessages, Message $assistantMessage, RequestTask $requestTask, string $replyContent): void
    {
        foreach ($originalMessages as $message) {
            $this->entityManager->persist($message);
        }
        $this->entityManager->persist($assistantMessage);

        $this->requestTaskRepository->markTaskAsCompleted($requestTask, $replyContent);
        $this->entityManager->flush();
    }

    /** @param array<Message> $originalMessages */
    private function dispatchSuccessEvent(Conversation $conversation, string $replyContent, array $originalMessages): void
    {
        $lastMessage = [] !== $originalMessages ? end($originalMessages) : null;
        $this->eventDispatcher->dispatch(new DifyReplyEvent(
            $conversation,
            $replyContent,
            $lastMessage,
            true
        ));
    }

    /** @param array<Message> $originalMessages */
    private function handleProcessingFailure(RequestTask $requestTask, array $originalMessages, Conversation $conversation, \Exception $e): void
    {
        $this->updateFailedMessages($originalMessages, $e);
        $this->createFailedMessageRecords($originalMessages, $conversation, $requestTask, $e);
        $this->persistFailedProcessing($originalMessages, $requestTask, $e);
        $this->dispatchErrorEvent($conversation, $originalMessages, $e);
    }

    /** @param array<Message> $originalMessages */
    private function updateFailedMessages(array $originalMessages, \Exception $e): void
    {
        foreach ($originalMessages as $message) {
            $message->setStatus(MessageStatus::FAILED);
            $message->setErrorMessage($e->getMessage());
            $message->setRetryCount($message->getRetryCount() + 1);
        }
    }

    /**
     * @param array<Message> $originalMessages
     * @return array<FailedMessage>
     */
    private function createFailedMessageRecords(array $originalMessages, Conversation $conversation, RequestTask $requestTask, \Exception $e): array
    {
        $failedMessages = [];

        foreach ($originalMessages as $message) {
            $failedMessage = new FailedMessage();
            $failedMessage->setConversation($conversation);
            $failedMessage->setMessage($message);
            $failedMessage->setRequestTask($requestTask);
            $failedMessage->setError($e->getMessage());
            $failedMessage->setAttempts($message->getRetryCount());
            $failedMessage->setContext([
                'exception_class' => get_class($e),
                'exception_code' => $e->getCode(),
                'original_message_content' => $message->getContent(),
                'original_message_role' => $message->getRole()->value,
                'request_task_id' => $requestTask->getId(),
                'batch_processing' => true,
            ]);

            $this->entityManager->persist($failedMessage);
            $failedMessages[] = $failedMessage;
        }

        return $failedMessages;
    }

    /** @param array<Message> $originalMessages */
    private function persistFailedProcessing(array $originalMessages, RequestTask $requestTask, \Exception $e): void
    {
        foreach ($originalMessages as $message) {
            $this->entityManager->persist($message);
        }

        $this->requestTaskRepository->markTaskAsFailed($requestTask, $e->getMessage());
        $this->entityManager->flush();
    }

    /** @param array<Message> $originalMessages */
    private function dispatchErrorEvent(Conversation $conversation, array $originalMessages, \Exception $e): void
    {
        $lastMessage = [] !== $originalMessages ? end($originalMessages) : null;
        $this->eventDispatcher->dispatch(new DifyErrorEvent(
            $conversation,
            $lastMessage,
            $e->getMessage(),
            $e
        ));
    }

    /** @return array<string, mixed> */
    private function sendRequest(DifySetting $setting, Conversation $conversation, string $content): array
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/chat-messages';

        $payload = [
            'inputs' => [],
            'query' => $content,
            'response_mode' => 'blocking',
            'conversation_id' => $conversation->getConversationId(),
            'user' => 'system',
        ];

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'timeout' => $setting->getTimeout(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Dify API request failed: ' . $response->getContent(false));
        }

        $data = $response->toArray();

        if (null === $conversation->getConversationId() && isset($data['conversation_id'])) {
            $conversationId = $data['conversation_id'];
            if (is_string($conversationId)) {
                $conversation->setConversationId($conversationId);
                $this->entityManager->flush();
            }
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    private function sendStreamRequest(DifySetting $setting, Conversation $conversation, string $content): \Generator
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/chat-messages';

        $payload = [
            'inputs' => [],
            'query' => $content,
            'response_mode' => 'streaming',
            'conversation_id' => $conversation->getConversationId(),
            'user' => 'system',
        ];

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'timeout' => $setting->getTimeout(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Dify API stream request failed: ' . $response->getContent(false));
        }

        foreach ($this->httpClient->stream($response) as $chunk) {
            yield $chunk->getContent();
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

    private function getOrCreateConversation(): Conversation
    {
        return $this->aggregator->getCurrentConversation();
    }

    public function flushBatch(): void
    {
        $this->aggregator->forceProcess();
    }
}
