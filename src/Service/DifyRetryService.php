<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Symfony\Component\Messenger\MessageBusInterface;
use Tourze\DifyClientBundle\Entity\FailedMessage;
use Tourze\DifyClientBundle\Entity\RequestTask;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;
use Tourze\DifyClientBundle\Exception\FailedMessageAlreadyRetriedException;
use Tourze\DifyClientBundle\Exception\FailedMessageNotFoundException;
use Tourze\DifyClientBundle\Message\RetryFailedMessage;
use Tourze\DifyClientBundle\Repository\FailedMessageRepository;
use Tourze\DifyClientBundle\Repository\RequestTaskRepository;

final readonly class DifyRetryService
{
    public function __construct(
        private FailedMessageRepository $failedMessageRepository,
        private RequestTaskRepository $requestTaskRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    /** @param array<string, mixed> $context */
    public function retryFailedMessage(string $failedMessageId, ?string $taskId = null, array $context = [], bool $retryWholeBatch = false): void
    {
        $failedMessage = $this->failedMessageRepository->find($failedMessageId);

        if (null === $failedMessage) {
            throw new FailedMessageNotFoundException($failedMessageId);
        }

        if (!$failedMessage instanceof FailedMessage) {
            throw new DifyRuntimeException('实体类型错误，期望 FailedMessage，实际获取：' . get_class($failedMessage));
        }

        if ($failedMessage->isRetried()) {
            throw new FailedMessageAlreadyRetriedException($failedMessageId);
        }

        $retryMessage = new RetryFailedMessage($failedMessageId, $taskId, $context, $retryWholeBatch);
        $this->messageBus->dispatch($retryMessage);
    }

    /**
     * @param array<string> $failedMessageIds
     * @param array<string, mixed> $context
     * @return array<string, array<string, mixed>>
     */
    public function retryFailedMessages(array $failedMessageIds, array $context = []): array
    {
        $results = [];

        foreach ($failedMessageIds as $id) {
            try {
                $this->retryFailedMessage($id, null, $context);
                $results[$id] = ['success' => true, 'message' => 'Retry queued'];
            } catch (\Exception $e) {
                $results[$id] = ['success' => false, 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function retryByTaskId(string $taskId, array $context = []): array
    {
        $failedMessages = $this->failedMessageRepository->findBy([
            'taskId' => $taskId,
            'retried' => false,
        ]);

        if ([] === $failedMessages) {
            return ['success' => false, 'message' => 'No failed messages found for task ID: ' . $taskId];
        }

        $results = [];
        foreach ($failedMessages as $failedMessage) {
            if (!$failedMessage instanceof FailedMessage) {
                $results[] = ['id' => 'unknown', 'success' => false, 'error' => '实体类型错误'];
                continue;
            }

            try {
                $this->retryFailedMessage($failedMessage->getId() ?? '', $taskId, $context);
                $results[] = ['id' => $failedMessage->getId() ?? '', 'success' => true];
            } catch (\Exception $e) {
                $results[] = ['id' => $failedMessage->getId() ?? '', 'success' => false, 'error' => $e->getMessage()];
            }
        }

        return [
            'success' => true,
            'retried_count' => count(array_filter($results, fn ($r) => $r['success'])),
            'total_count' => count($results),
            'results' => $results,
        ];
    }

    /** @return array<FailedMessage> */
    public function getRetryableMessages(int $limit = 100): array
    {
        return $this->failedMessageRepository->findUnretriedMessages($limit);
    }

    /** @return array<FailedMessage> */
    public function getFailedMessageByTaskId(string $taskId): array
    {
        return $this->failedMessageRepository->findBy([
            'taskId' => $taskId,
            'retried' => false,
        ]);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function retryByRequestTaskId(string $requestTaskId, array $context = []): array
    {
        $failedMessages = $this->failedMessageRepository->findBy([
            'requestTask' => $requestTaskId,
            'retried' => false,
        ]);

        if ([] === $failedMessages) {
            return ['success' => false, 'message' => 'No failed messages found for RequestTask ID: ' . $requestTaskId];
        }

        $results = [];
        foreach ($failedMessages as $failedMessage) {
            if (!$failedMessage instanceof FailedMessage) {
                $results[] = ['id' => 'unknown', 'success' => false, 'error' => '实体类型错误'];
                continue;
            }

            try {
                $this->retryFailedMessage($failedMessage->getId() ?? '', null, $context, true);
                $results[] = ['id' => $failedMessage->getId() ?? '', 'success' => true];
            } catch (\Exception $e) {
                $results[] = ['id' => $failedMessage->getId() ?? '', 'success' => false, 'error' => $e->getMessage()];
            }
        }

        return [
            'success' => true,
            'retried_count' => count(array_filter($results, fn ($r) => $r['success'])),
            'total_count' => count($results),
            'results' => $results,
            'request_task_id' => $requestTaskId,
        ];
    }

    /** @return array<string, mixed> */
    public function getRequestTaskMessages(string $requestTaskId): array
    {
        $requestTask = $this->requestTaskRepository->find($requestTaskId);

        if (null === $requestTask) {
            throw new DifyRuntimeException('RequestTask not found: ' . $requestTaskId);
        }

        return [
            'request_task' => $requestTask,
            'messages' => $requestTask->getMessages()->toArray(),
            'failed_messages' => $requestTask->getFailedMessages()->toArray(),
        ];
    }
}
