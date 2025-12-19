<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Command\Support;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\DifyClientBundle\Entity\FailedMessage;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\RequestTask;

final class FailedMessageDisplayer
{
    public function displayMessageInfo(FailedMessage $message, SymfonyStyle $io): void
    {
        $this->displayMessageBasicInfo($message, $io);
        $this->displayMessageRetryHistory($message, $io);
    }

    public function displayRequestTaskInfo(RequestTask $requestTask, SymfonyStyle $io): void
    {
        $this->displayRequestTaskBasicInfo($requestTask, $io);
        $this->displayRequestTaskMessages($requestTask, $io);
        $this->displayRequestTaskFailedMessages($requestTask, $io);
    }

    private function displayMessageBasicInfo(FailedMessage $message, SymfonyStyle $io): void
    {
        $io->horizontalTable(
            ['属性', '值'],
            [
                ['ID', $message->getId()],
                ['任务ID', null !== $message->getTaskId() ? $message->getTaskId() : 'N/A'],
                ['错误信息', $message->getError()],
                ['尝试次数', $message->getAttempts()],
                ['失败时间', $message->getFailedAt()?->format('Y-m-d H:i:s') ?? 'N/A'],
                ['会话ID', null !== $message->getConversation() ? $message->getConversation()->getId() : 'N/A'],
                ['已重试', $message->isRetried() ? '是' : '否'],
            ]
        );
    }

    private function displayMessageRetryHistory(FailedMessage $message, SymfonyStyle $io): void
    {
        $retryHistory = $message->getRetryHistory();
        if (null === $retryHistory) {
            return;
        }

        $this->showRetryHistorySection($retryHistory, $io);
    }

    /**
     * @param array<array<string, mixed>> $retryHistory
     */
    private function showRetryHistorySection(array $retryHistory, SymfonyStyle $io): void
    {
        $io->section('重试历史');
        foreach ($retryHistory as $retry) {
            $io->text(sprintf(
                '- %s: %s',
                is_string($retry['timestamp'] ?? null) ? $retry['timestamp'] : '未知时间',
                is_string($retry['result'] ?? null) ? $retry['result'] : '未知结果'
            ));
        }
    }

    private function displayRequestTaskBasicInfo(RequestTask $requestTask, SymfonyStyle $io): void
    {
        $io->horizontalTable(
            ['属性', '值'],
            [
                ['RequestTask ID', $requestTask->getTaskId()],
                ['状态', $requestTask->getStatus()->getLabel()],
                ['消息数量', $requestTask->getMessageCount()],
                ['聚合内容', substr($requestTask->getAggregatedContent(), 0, 100) . '...'],
                ['创建时间', $requestTask->getCreateTime()?->format('Y-m-d H:i:s') ?? 'N/A'],
                ['完成时间', $requestTask->getCompletedAt()?->format('Y-m-d H:i:s') ?? 'N/A'],
            ]
        );
    }

    private function displayRequestTaskMessages(RequestTask $requestTask, SymfonyStyle $io): void
    {
        $messages = $requestTask->getMessages();
        if ($messages->isEmpty()) {
            return;
        }

        $this->showRequestTaskMessagesSection($messages, $io);
    }

    /**
     * @param Collection<int, Message> $messages
     */
    private function showRequestTaskMessagesSection(Collection $messages, SymfonyStyle $io): void
    {
        $io->section('批次中的消息');
        foreach ($messages as $index => $message) {
            $io->text(sprintf('%d. %s', $index + 1, substr($message->getContent(), 0, 80) . '...'));
        }
    }

    private function displayRequestTaskFailedMessages(RequestTask $requestTask, SymfonyStyle $io): void
    {
        $failedMessages = $requestTask->getFailedMessages();
        if ($failedMessages->isEmpty()) {
            return;
        }

        $this->showFailedMessagesSection($failedMessages, $io);
    }

    /**
     * @param Collection<int, FailedMessage> $failedMessages
     */
    private function showFailedMessagesSection(Collection $failedMessages, SymfonyStyle $io): void
    {
        $io->section('关联的失败消息');
        foreach ($failedMessages as $failedMessage) {
            $io->text(sprintf('- 失败消息 #%d: %s', $failedMessage->getId(), substr($failedMessage->getError(), 0, 60) . '...'));
        }
    }
}
