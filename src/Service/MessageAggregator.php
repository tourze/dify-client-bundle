<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\RequestTask;
use Tourze\DifyClientBundle\Enum\MessageRole;
use Tourze\DifyClientBundle\Enum\MessageStatus;
use Tourze\DifyClientBundle\Enum\RequestTaskStatus;
use Tourze\DifyClientBundle\Message\ProcessDifyMessage;
use Tourze\DifyClientBundle\Repository\ConversationRepository;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;
use Tourze\DifyClientBundle\Repository\MessageRepository;

final class MessageAggregator
{
    private ?Conversation $currentConversation = null;

    private ?RequestTask $currentRequestTask = null;

    private \DateTimeImmutable $aggregationWindowStart;

    public function __construct(
        private readonly MessageRepository $messageRepository,
        private readonly ConversationRepository $conversationRepository,
        private readonly DifySettingRepository $settingRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly ClockInterface $clock,
        private readonly EntityManagerInterface $entityManager,
        private readonly int $aggregationTimeout = 30,
    ) {
        $this->aggregationWindowStart = $this->clock->now();
    }

    public function addMessage(string $content, ?Conversation $conversation = null): Message
    {
        if (null === $conversation) {
            $conversation = $this->getCurrentConversation();
        }

        $message = new Message();
        $message->setConversation($conversation);
        $message->setRole(MessageRole::USER);
        $message->setContent($content);
        $message->setStatus(MessageStatus::PENDING);
        $message->setMetadata([
            'aggregated_at' => $this->clock->now()->format(\DateTimeInterface::ATOM),
        ]);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // 添加到当前批次或创建新批次
        $this->addToCurrentBatch($message);
        $this->checkAndProcessAggregation();

        return $message;
    }

    public function forceProcess(): void
    {
        if (null !== $this->currentRequestTask) {
            $this->processCurrentBatch();
        }
    }

    public function getCurrentConversation(): Conversation
    {
        if (null === $this->currentConversation) {
            $this->currentConversation = $this->conversationRepository->createConversation();
        }

        $this->conversationRepository->updateLastActive($this->currentConversation);

        return $this->currentConversation;
    }

    private function addToCurrentBatch(Message $message): void
    {
        if (null === $this->currentRequestTask) {
            $this->createNewBatch();
        }

        // At this point currentRequestTask is guaranteed to be non-null
        assert(null !== $this->currentRequestTask);

        $this->currentRequestTask->addMessage($message);

        // 更新批次的消息计数和聚合内容
        $messages = $this->currentRequestTask->getMessages()->toArray();
        $this->currentRequestTask->setMessageCount(count($messages));
        $this->currentRequestTask->setAggregatedContent($this->aggregateMessageContent($messages));

        $this->entityManager->flush();
    }

    private function createNewBatch(): void
    {
        $this->currentRequestTask = new RequestTask();
        $this->currentRequestTask->setTaskId('batch_' . uniqid() . '_' . time());
        $this->currentRequestTask->setStatus(RequestTaskStatus::PENDING);
        $this->currentRequestTask->setAggregatedContent('');
        $this->currentRequestTask->setMessageCount(0);
        $this->currentRequestTask->setMetadata([
            'conversation_id' => $this->getCurrentConversation()->getId(),
            'window_start' => $this->aggregationWindowStart->format(\DateTimeInterface::ATOM),
        ]);

        $this->entityManager->persist($this->currentRequestTask);
        $this->entityManager->flush();
    }

    private function checkAndProcessAggregation(): void
    {
        if (null === $this->currentRequestTask) {
            return;
        }

        $activeSetting = $this->getActiveSetting();
        if (null === $activeSetting) {
            return;
        }

        $batchThreshold = $activeSetting->getBatchThreshold();
        $currentTime = $this->clock->now();
        $timeElapsed = $currentTime->getTimestamp() - $this->aggregationWindowStart->getTimestamp();

        $messageCount = $this->currentRequestTask->getMessageCount();

        if ($messageCount >= $batchThreshold || $timeElapsed >= $this->aggregationTimeout) {
            $this->processCurrentBatch();
        }
    }

    private function processCurrentBatch(): void
    {
        if (null === $this->currentRequestTask || $this->currentRequestTask->getMessages()->isEmpty()) {
            return;
        }

        $messages = $this->currentRequestTask->getMessages()->toArray();
        $aggregatedContent = $this->currentRequestTask->getAggregatedContent();

        // 发送到队列处理
        $this->messageBus->dispatch(new ProcessDifyMessage(
            $this->currentRequestTask,
            $aggregatedContent,
            $messages
        ));

        // 标记消息为已聚合
        $this->messageRepository->markMessagesAsAggregated($messages);

        // 重置批次状态
        $this->currentRequestTask = null;
        $this->aggregationWindowStart = $this->clock->now();
    }

    /** @param array<Message> $messages */
    private function aggregateMessageContent(array $messages): string
    {
        $contents = array_map(fn (Message $msg) => $msg->getContent(), $messages);

        if (1 === count($contents)) {
            return $contents[0];
        }

        return implode("\n\n", array_map(function ($content, $index) {
            return '消息' . ($index + 1) . "：\n" . $content;
        }, $contents, array_keys($contents)));
    }

    private function getActiveSetting(): ?DifySetting
    {
        return $this->settingRepository->findActiveSetting();
    }

    public function reset(): void
    {
        $this->currentConversation = null;
        $this->currentRequestTask = null;
        $this->aggregationWindowStart = $this->clock->now();
    }

    public function getPendingCount(): int
    {
        return null !== $this->currentRequestTask ? $this->currentRequestTask->getMessageCount() : 0;
    }

    public function getTimeUntilNextProcess(): int
    {
        $elapsed = $this->clock->now()->getTimestamp() - $this->aggregationWindowStart->getTimestamp();

        return max(0, $this->aggregationTimeout - $elapsed);
    }

    public function getCurrentRequestTask(): ?RequestTask
    {
        return $this->currentRequestTask;
    }
}
