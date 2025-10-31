<?php

namespace Tourze\DifyClientBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Enum\ConversationStatus;
use Tourze\DifyClientBundle\Enum\MessageRole;
use Tourze\DifyClientBundle\Enum\MessageStatus;
use Tourze\DifyClientBundle\Repository\MessageRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(MessageRepository::class)]
#[RunTestsInSeparateProcesses]
final class MessageRepositoryTest extends AbstractRepositoryTestCase
{
    private MessageRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(MessageRepository::class);
    }

    protected function getRepository(): MessageRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): Message
    {
        $conversation = new Conversation();
        $conversation->setConversationId('test-conv-' . uniqid());
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $this->persistAndFlush($conversation);

        $message = new Message();
        $message->setConversation($conversation);
        $message->setRole(MessageRole::USER);
        $message->setContent('Test message ' . uniqid());
        $message->setStatus(MessageStatus::PENDING);

        return $message;
    }

    public function testSaveShouldPersistEntity(): void
    {
        // Arrange: 创建会话和消息
        $conversation = new Conversation();
        $conversation->setConversationId('test-conv');
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $this->persistAndFlush($conversation);

        $message = new Message();
        $message->setConversation($conversation);
        $message->setRole(MessageRole::USER);
        $message->setContent('Test message');
        $message->setStatus(MessageStatus::PENDING);

        // Act: 保存消息
        $this->repository->save($message);

        // Assert: 验证已持久化
        $this->assertNotNull($message->getId());
        $this->assertEntityPersisted($message);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化消息
        $conversation = new Conversation();
        $conversation->setConversationId('test-conv-remove');
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $this->persistAndFlush($conversation);

        $message = new Message();
        $message->setConversation($conversation);
        $message->setRole(MessageRole::USER);
        $message->setContent('To remove');
        $message->setStatus(MessageStatus::PENDING);

        $this->persistAndFlush($message);
        $messageId = $message->getId();

        // Act: 删除消息
        $this->repository->remove($message);

        // Assert: 验证已删除
        $this->assertEntityNotExists(Message::class, $messageId);
    }

    public function testFindUserMessagesForAggregation(): void
    {
        // Arrange: 创建会话和用户消息
        $conversation = new Conversation();
        $conversation->setConversationId('test-conv-aggregation');
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $this->persistAndFlush($conversation);

        $cutoffTime = new \DateTimeImmutable('-1 hour');

        $userMessage = new Message();
        $userMessage->setConversation($conversation);
        $userMessage->setRole(MessageRole::USER);
        $userMessage->setContent('User message for aggregation');
        $userMessage->setStatus(MessageStatus::PENDING);
        $this->persistAndFlush($userMessage);

        // Act: 查找用户消息
        $result = $this->repository->findUserMessagesForAggregation($conversation, $cutoffTime);

        // Assert: 验证返回结果
        $this->assertCount(1, $result);
        $this->assertEquals($userMessage->getId(), $result[0]->getId());
    }

    public function testMarkMessagesAsAggregated(): void
    {
        // Arrange: 创建待聚合的消息
        $conversation = new Conversation();
        $conversation->setConversationId('test-conv-mark');
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $this->persistAndFlush($conversation);

        $message1 = new Message();
        $message1->setConversation($conversation);
        $message1->setRole(MessageRole::USER);
        $message1->setContent('Message 1');
        $message1->setStatus(MessageStatus::PENDING);
        $this->persistAndFlush($message1);

        $message2 = new Message();
        $message2->setConversation($conversation);
        $message2->setRole(MessageRole::USER);
        $message2->setContent('Message 2');
        $message2->setStatus(MessageStatus::PENDING);
        $this->persistAndFlush($message2);

        // Act: 标记消息为已聚合
        $this->repository->markMessagesAsAggregated([$message1, $message2]);

        // Assert: 验证状态已更新
        self::getEntityManager()->refresh($message1);
        self::getEntityManager()->refresh($message2);

        $this->assertEquals(MessageStatus::AGGREGATED, $message1->getStatus());
        $this->assertEquals(MessageStatus::AGGREGATED, $message2->getStatus());
    }

    public function testFindFailedMessages(): void
    {
        // Arrange: 创建失败消息
        $conversation = new Conversation();
        $conversation->setConversationId('test-conv-failed');
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $this->persistAndFlush($conversation);

        $failedMessage = new Message();
        $failedMessage->setConversation($conversation);
        $failedMessage->setRole(MessageRole::USER);
        $failedMessage->setContent('Failed message');
        $failedMessage->setStatus(MessageStatus::FAILED);
        $failedMessage->setRetryCount(1);
        $this->persistAndFlush($failedMessage);

        // Act: 查找失败消息
        $result = $this->repository->findFailedMessages();

        // Assert: 验证返回结果
        $messageIds = array_map(fn ($msg) => $msg->getId(), $result);
        $this->assertContains($failedMessage->getId(), $messageIds);
    }

    public function testFindMessagesByConversation(): void
    {
        // Arrange: 创建会话和消息
        $conversation = new Conversation();
        $conversation->setConversationId('test-conv-by-conversation');
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $this->persistAndFlush($conversation);

        $message = new Message();
        $message->setConversation($conversation);
        $message->setRole(MessageRole::USER);
        $message->setContent('Message by conversation');
        $message->setStatus(MessageStatus::PENDING);
        $this->persistAndFlush($message);

        // Act: 查找会话的消息
        $result = $this->repository->findMessagesByConversation($conversation);

        // Assert: 验证返回结果
        $this->assertCount(1, $result);
        $this->assertEquals($message->getId(), $result[0]->getId());
    }

    public function testFindPendingMessages(): void
    {
        // Arrange: 创建待处理消息
        $conversation = new Conversation();
        $conversation->setConversationId('test-conv-pending');
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $this->persistAndFlush($conversation);

        $pendingMessage = new Message();
        $pendingMessage->setConversation($conversation);
        $pendingMessage->setRole(MessageRole::USER);
        $pendingMessage->setContent('Pending message');
        $pendingMessage->setStatus(MessageStatus::PENDING);
        $this->persistAndFlush($pendingMessage);

        // Act: 查找待处理消息
        $result = $this->repository->findPendingMessages();

        // Assert: 验证返回结果包含创建的消息
        $messageIds = array_map(fn ($msg) => $msg->getId(), $result);
        $this->assertContains($pendingMessage->getId(), $messageIds);
    }

    public function testFindConversationHistory(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFlush(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
