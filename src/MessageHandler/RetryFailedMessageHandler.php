<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Tourze\DifyClientBundle\Entity\FailedMessage;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\RequestTask;
use Tourze\DifyClientBundle\Enum\MessageRole;
use Tourze\DifyClientBundle\Enum\MessageStatus;
use Tourze\DifyClientBundle\Enum\RequestTaskStatus;
use Tourze\DifyClientBundle\Exception\DifyRetryException;
use Tourze\DifyClientBundle\Message\ProcessDifyMessage;
use Tourze\DifyClientBundle\Message\RetryFailedMessage;
use Tourze\DifyClientBundle\Repository\FailedMessageRepository;

#[AsMessageHandler]
final class RetryFailedMessageHandler
{
    public function __construct(
        private readonly FailedMessageRepository $failedMessageRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly ClockInterface $clock,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(RetryFailedMessage $message): void
    {
        $failedMessage = $this->failedMessageRepository->find($message->getFailedMessageId());

        if (null === $failedMessage) {
            throw new DifyRetryException(sprintf('Failed message with ID %s not found', $message->getFailedMessageId()));
        }

        // 类型已通过Repository查询确定，无需重复检查

        if ($failedMessage->isRetried()) {
            throw new DifyRetryException(sprintf('Failed message %s has already been retried', $message->getFailedMessageId()));
        }

        try {
            // 记录重试开始
            $failedMessage->addRetryAttempt($this->clock->now(), 'retry_started');
            $this->entityManager->flush();

            if ($message->shouldRetryWholeBatch()) {
                // 重试整个批次
                $this->retryWholeBatch($failedMessage, $message);
            } else {
                // 重试单个消息
                $this->retrySingleMessage($failedMessage, $message);
            }

            // 记录重试成功
            $failedMessage->addRetryAttempt($this->clock->now(), 'retry_success');
            $failedMessage->setRetried(true);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // 记录重试失败
            $failedMessage->addRetryAttempt($this->clock->now(), 'retry_failed: ' . $e->getMessage());
            $this->entityManager->flush();

            throw $e;
        }
    }

    private function retryWholeBatch(FailedMessage $failedMessage, RetryFailedMessage $retryMessage): void
    {
        $requestTask = $failedMessage->getRequestTask();

        if (null === $requestTask) {
            throw new DifyRetryException('Cannot retry batch: no associated RequestTask found');
        }

        if (!$requestTask->isRetriable()) {
            throw new DifyRetryException('RequestTask is not in a retriable state');
        }

        // 重新创建批次任务
        $newRequestTask = new RequestTask();
        $newRequestTask->setTaskId('retry_batch_' . uniqid() . '_' . time());
        $newRequestTask->setStatus(RequestTaskStatus::PENDING);
        $newRequestTask->setAggregatedContent($requestTask->getAggregatedContent());
        $newRequestTask->setMessageCount($requestTask->getMessageCount());
        $newRequestTask->setMetadata(array_merge(
            $requestTask->getMetadata() ?? [],
            [
                'retry_of_request_task_id' => $requestTask->getId(),
                'retry_failed_message_id' => $failedMessage->getId(),
                'retry_task_id' => $retryMessage->getTaskId(),
                'retry_context' => $retryMessage->getContext(),
                'is_retry' => true,
            ]
        ));

        $this->entityManager->persist($newRequestTask);

        // 重新创建批次中的所有消息
        $originalMessages = $requestTask->getMessages()->toArray();
        $retryMessages = [];

        foreach ($originalMessages as $originalMessage) {
            $retryMessage = new Message();
            $retryMessage->setConversation($originalMessage->getConversation());
            $retryMessage->setRole($originalMessage->getRole());
            $retryMessage->setContent($originalMessage->getContent());
            $retryMessage->setStatus(MessageStatus::PENDING);
            $retryMessage->setMetadata(array_merge(
                $originalMessage->getMetadata() ?? [],
                [
                    'retry_of_message_id' => $originalMessage->getId(),
                    'retry_request_task_id' => $newRequestTask->getId(),
                    'is_batch_retry' => true,
                ]
            ));
            $retryMessage->setRequestTask($newRequestTask);

            $this->entityManager->persist($retryMessage);
            $retryMessages[] = $retryMessage;
        }

        $this->entityManager->flush();

        // 发送重试批次消息
        $this->messageBus->dispatch(new ProcessDifyMessage(
            $newRequestTask,
            $newRequestTask->getAggregatedContent(),
            $retryMessages
        ));

        // 标记原始任务为重试中
        $requestTask->setStatus(RequestTaskStatus::RETRYING);
        $this->entityManager->flush();
    }

    private function retrySingleMessage(FailedMessage $failedMessage, RetryFailedMessage $retryMessage): void
    {
        $originalMessage = $failedMessage->getMessage();
        $conversation = $failedMessage->getConversation();

        if (null === $originalMessage || null === $conversation) {
            throw new DifyRetryException('Cannot retry message: missing original message or conversation');
        }

        // 创建新的 RequestTask 用于单个消息重试
        $newRequestTask = new RequestTask();
        $newRequestTask->setTaskId('retry_single_' . uniqid() . '_' . time());
        $newRequestTask->setStatus(RequestTaskStatus::PENDING);
        $newRequestTask->setAggregatedContent($originalMessage->getContent());
        $newRequestTask->setMessageCount(1);
        $newRequestTask->setMetadata([
            'retry_of_message_id' => $originalMessage->getId(),
            'retry_failed_message_id' => $failedMessage->getId(),
            'retry_task_id' => $retryMessage->getTaskId(),
            'retry_context' => $retryMessage->getContext(),
            'is_single_retry' => true,
        ]);

        $this->entityManager->persist($newRequestTask);

        // 创建重试消息
        $retryMessage = new Message();
        $retryMessage->setConversation($conversation);
        $retryMessage->setRole($originalMessage->getRole());
        $retryMessage->setContent($originalMessage->getContent());
        $retryMessage->setStatus(MessageStatus::PENDING);
        $retryMessage->setMetadata(array_merge(
            $originalMessage->getMetadata() ?? [],
            [
                'retry_of_message_id' => $originalMessage->getId(),
                'retry_request_task_id' => $newRequestTask->getId(),
                'is_single_retry' => true,
            ]
        ));
        $retryMessage->setRequestTask($newRequestTask);

        $this->entityManager->persist($retryMessage);
        $this->entityManager->flush();

        // 发送重试消息
        $this->messageBus->dispatch(new ProcessDifyMessage(
            $newRequestTask,
            $originalMessage->getContent(),
            [$retryMessage]
        ));
    }
}
