<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Enum\ConversationStatus;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Conversation::class)]
final class ConversationTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): Conversation
    {
        return new Conversation();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'conversationId' => ['conversationId', 'conv-12345'];
        yield 'status' => ['status', ConversationStatus::ACTIVE];
        yield 'summary' => ['summary', '这是一个测试对话摘要'];
        yield 'lastActive' => ['lastActive', new \DateTimeImmutable('2024-01-01 10:00:00')];
    }

    public function testCreateConversationWithDefaultValuesShouldSucceed(): void
    {
        $conversation = $this->createEntity();

        $this->assertNull($conversation->getId());
        $this->assertNull($conversation->getConversationId());
        $this->assertEquals(ConversationStatus::ACTIVE, $conversation->getStatus());
        $this->assertNull($conversation->getSummary());
        $this->assertNull($conversation->getLastActive());
        $this->assertEmpty($conversation->getMessages());
    }

    public function testSetConversationIdShouldUpdateValue(): void
    {
        $conversation = $this->createEntity();
        $conversationId = 'conv-12345';

        $conversation->setConversationId($conversationId);

        $this->assertEquals($conversationId, $conversation->getConversationId());
    }

    public function testSetConversationIdWithNullShouldAcceptNull(): void
    {
        $conversation = $this->createEntity();
        $conversation->setConversationId('conv-12345');

        $conversation->setConversationId(null);

        $this->assertNull($conversation->getConversationId());
    }

    public function testSetStatusShouldUpdateValue(): void
    {
        $conversation = $this->createEntity();

        $conversation->setStatus(ConversationStatus::CLOSED);

        $this->assertEquals(ConversationStatus::CLOSED, $conversation->getStatus());
    }

    public function testSetSummaryShouldUpdateValue(): void
    {
        $conversation = $this->createEntity();
        $summary = '这是一个关于AI助手的对话摘要';

        $conversation->setSummary($summary);

        $this->assertEquals($summary, $conversation->getSummary());
    }

    public function testSetSummaryWithNullShouldAcceptNull(): void
    {
        $conversation = $this->createEntity();
        $conversation->setSummary('原始摘要');

        $conversation->setSummary(null);

        $this->assertNull($conversation->getSummary());
    }

    public function testSetLastActiveShouldUpdateValue(): void
    {
        $conversation = $this->createEntity();
        $lastActive = new \DateTimeImmutable('2024-01-01 10:00:00');

        $conversation->setLastActive($lastActive);

        $this->assertEquals($lastActive, $conversation->getLastActive());
    }

    public function testSetLastActiveWithNullShouldAcceptNull(): void
    {
        $conversation = $this->createEntity();
        $conversation->setLastActive(new \DateTimeImmutable());

        $conversation->setLastActive(null);

        $this->assertNull($conversation->getLastActive());
    }

    public function testAddMessageShouldAddNewMessage(): void
    {
        $conversation = $this->createEntity();

        // 使用反射创建Message实例，避免循环依赖
        $message = $this->createMock(Message::class);
        $message->method('getConversation')->willReturn($conversation);

        $conversation->addMessage($message);

        $this->assertTrue($conversation->getMessages()->contains($message));
    }

    public function testAddMessageWithExistingMessageShouldNotDuplicate(): void
    {
        $conversation = $this->createEntity();

        // 使用反射创建Message实例，避免循环依赖
        $message = $this->createMock(Message::class);
        $message->method('getConversation')->willReturn($conversation);

        $conversation->addMessage($message);
        $conversation->addMessage($message);

        $this->assertCount(1, $conversation->getMessages());
        $this->assertTrue($conversation->getMessages()->contains($message));
    }

    public function testRemoveMessageShouldRemoveExistingMessage(): void
    {
        $conversation = $this->createEntity();

        // 使用反射创建Message实例，避免循环依赖
        $message = $this->createMock(Message::class);
        $message->method('getConversation')->willReturn($conversation);

        $conversation->addMessage($message);
        $conversation->removeMessage($message);

        $this->assertFalse($conversation->getMessages()->contains($message));
    }

    public function testRemoveMessageWithNonExistingMessageShouldNotCauseError(): void
    {
        $conversation = $this->createEntity();

        // 使用反射创建Message实例，避免循环依赖
        $message = $this->createMock(Message::class);
        $message->method('getConversation')->willReturn($conversation);

        $conversation->removeMessage($message);

        $this->assertFalse($conversation->getMessages()->contains($message));
    }

    public function testToStringWithConversationIdShouldReturnConversationId(): void
    {
        $conversation = $this->createEntity();
        $conversationId = 'conv-12345';
        $conversation->setConversationId($conversationId);

        $result = (string) $conversation;

        $this->assertEquals($conversationId, $result);
    }

    public function testToStringWithoutConversationIdButWithIdShouldReturnId(): void
    {
        $conversation = $this->createEntity();

        // 使用反射设置id，因为它是通过Trait管理的
        $reflection = new \ReflectionClass($conversation);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($conversation, 'entity-id-123');

        $result = (string) $conversation;

        $this->assertEquals('entity-id-123', $result);
    }

    public function testToStringWithoutAnyIdShouldReturnDefaultValue(): void
    {
        $conversation = $this->createEntity();

        $result = (string) $conversation;

        $this->assertEquals('会话', $result);
    }

    public function testConversationShouldAcceptLongSummary(): void
    {
        $conversation = $this->createEntity();
        $longSummary = str_repeat('这是一个很长的摘要内容。', 100);

        $conversation->setSummary($longSummary);

        $this->assertEquals($longSummary, $conversation->getSummary());
    }

    public function testConversationStatusTransitionShouldWork(): void
    {
        $conversation = $this->createEntity();

        // 测试状态转换
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $this->assertEquals(ConversationStatus::ACTIVE, $conversation->getStatus());

        $conversation->setStatus(ConversationStatus::INACTIVE);
        $this->assertEquals(ConversationStatus::INACTIVE, $conversation->getStatus());

        $conversation->setStatus(ConversationStatus::CLOSED);
        $this->assertEquals(ConversationStatus::CLOSED, $conversation->getStatus());
    }
}
