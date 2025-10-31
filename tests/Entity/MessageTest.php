<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\RequestTask;
use Tourze\DifyClientBundle\Enum\MessageRole;
use Tourze\DifyClientBundle\Enum\MessageStatus;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Message::class)]
final class MessageTest extends AbstractEntityTestCase
{
    private function getConversation(): Conversation
    {
        /** @var Conversation|null $conversation */
        static $conversation = null;
        if (null === $conversation) {
            $conversation = new Conversation();
            $conversation->setConversationId('test-conv-123');
        }

        return $conversation;
    }

    protected function createEntity(): Message
    {
        return new Message();
    }

    protected function onSetUp(): void
    {
        parent::setUp();

        // 不需要额外的设置逻辑
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'role' => ['role', MessageRole::USER];
        yield 'status' => ['status', MessageStatus::PENDING];
        yield 'content' => ['content', 'Test message content'];
        yield 'retryCount' => ['retryCount', 0];
        yield 'errorMessage' => ['errorMessage', 'Test error message'];
    }

    public function testCreateMessageWithDefaultValuesShouldSucceed(): void
    {
        $message = new Message();

        $this->assertNull($message->getId());
        $this->assertEquals(MessageStatus::PENDING, $message->getStatus());
        $this->assertNull($message->getSentAt());
        $this->assertNull($message->getReceivedAt());
        $this->assertNull($message->getMetadata());
        $this->assertEquals(0, $message->getRetryCount());
        $this->assertNull($message->getErrorMessage());
        $this->assertNull($message->getRequestTask());
    }

    public function testSetConversationShouldUpdateValue(): void
    {
        $message = new Message();

        $message->setConversation($this->getConversation());

        $this->assertSame($this->getConversation(), $message->getConversation());
    }

    public function testSetRoleShouldUpdateValue(): void
    {
        $message = new Message();

        $message->setRole(MessageRole::USER);

        $this->assertEquals(MessageRole::USER, $message->getRole());
    }

    public function testSetRoleWithAssistantShouldWork(): void
    {
        $message = new Message();

        $message->setRole(MessageRole::ASSISTANT);

        $this->assertEquals(MessageRole::ASSISTANT, $message->getRole());
    }

    public function testSetContentShouldUpdateValue(): void
    {
        $message = new Message();
        $content = '这是一条测试消息';

        $message->setContent($content);

        $this->assertEquals($content, $message->getContent());
    }

    public function testSetContentWithLongTextShouldWork(): void
    {
        $message = new Message();
        $longContent = str_repeat('这是一个很长的消息内容。', 1000);

        $message->setContent($longContent);

        $this->assertEquals($longContent, $message->getContent());
    }

    public function testSetStatusShouldUpdateValue(): void
    {
        $message = new Message();

        $message->setStatus(MessageStatus::SENT);

        $this->assertEquals(MessageStatus::SENT, $message->getStatus());
    }

    public function testSetStatusWithAllStatusesShouldWork(): void
    {
        $message = new Message();

        foreach (MessageStatus::cases() as $status) {
            $message->setStatus($status);
            $this->assertEquals($status, $message->getStatus());
        }
    }

    public function testSetSentAtShouldUpdateValue(): void
    {
        $message = new Message();
        $sentAt = new \DateTimeImmutable('2024-01-01 10:00:00');

        $message->setSentAt($sentAt);

        $this->assertEquals($sentAt, $message->getSentAt());
    }

    public function testSetSentAtWithNullShouldAcceptNull(): void
    {
        $message = new Message();
        $message->setSentAt(new \DateTimeImmutable());

        $message->setSentAt(null);

        $this->assertNull($message->getSentAt());
    }

    public function testSetReceivedAtShouldUpdateValue(): void
    {
        $message = new Message();
        $receivedAt = new \DateTimeImmutable('2024-01-01 10:05:00');

        $message->setReceivedAt($receivedAt);

        $this->assertEquals($receivedAt, $message->getReceivedAt());
    }

    public function testSetReceivedAtWithNullShouldAcceptNull(): void
    {
        $message = new Message();
        $message->setReceivedAt(new \DateTimeImmutable());

        $message->setReceivedAt(null);

        $this->assertNull($message->getReceivedAt());
    }

    public function testSetMetadataShouldUpdateValue(): void
    {
        $message = new Message();
        $metadata = [
            'retry_attempts' => 2,
            'aggregated_from' => ['msg1', 'msg2'],
            'priority' => 'high',
        ];

        $message->setMetadata($metadata);

        $this->assertEquals($metadata, $message->getMetadata());
    }

    public function testSetMetadataWithNullShouldAcceptNull(): void
    {
        $message = new Message();
        $message->setMetadata(['test' => 'value']);

        $message->setMetadata(null);

        $this->assertNull($message->getMetadata());
    }

    public function testSetRetryCountShouldUpdateValue(): void
    {
        $message = new Message();
        $retryCount = 3;

        $message->setRetryCount($retryCount);

        $this->assertEquals($retryCount, $message->getRetryCount());
    }

    public function testSetRetryCountWithZeroShouldWork(): void
    {
        $message = new Message();
        $message->setRetryCount(5);

        $message->setRetryCount(0);

        $this->assertEquals(0, $message->getRetryCount());
    }

    public function testSetRetryCountWithMaximumValueShouldWork(): void
    {
        $message = new Message();

        $message->setRetryCount(10);

        $this->assertEquals(10, $message->getRetryCount());
    }

    public function testSetErrorMessageShouldUpdateValue(): void
    {
        $message = new Message();
        $errorMessage = 'API request failed: timeout';

        $message->setErrorMessage($errorMessage);

        $this->assertEquals($errorMessage, $message->getErrorMessage());
    }

    public function testSetErrorMessageWithNullShouldAcceptNull(): void
    {
        $message = new Message();
        $message->setErrorMessage('Previous error');

        $message->setErrorMessage(null);

        $this->assertNull($message->getErrorMessage());
    }

    public function testSetRequestTaskShouldUpdateValue(): void
    {
        $message = new Message();
        $requestTask = new RequestTask();

        $message->setRequestTask($requestTask);

        $this->assertSame($requestTask, $message->getRequestTask());
    }

    public function testSetRequestTaskWithNullShouldAcceptNull(): void
    {
        $message = new Message();
        $message->setRequestTask(new RequestTask());

        $message->setRequestTask(null);

        $this->assertNull($message->getRequestTask());
    }

    public function testToStringWithUserRoleShouldReturnFormattedString(): void
    {
        $message = new Message();
        $message->setRole(MessageRole::USER);
        $message->setContent('这是一条用户消息，内容很长需要被截断以便在显示时保持简洁');

        $result = (string) $message;

        $this->assertStringStartsWith('用户消息: 这是一条用户消息，内容很长需要被截断以便在显示时保持简洁...', $result);
        $this->assertStringContainsString('...', $result);
    }

    public function testToStringWithAssistantRoleShouldReturnFormattedString(): void
    {
        $message = new Message();
        $message->setRole(MessageRole::ASSISTANT);
        $message->setContent('这是AI助手的回复');

        $result = (string) $message;

        $this->assertEquals('助手消息: 这是AI助手的回复...', $result);
    }

    public function testToStringWithShortContentShouldNotTruncate(): void
    {
        $message = new Message();
        $message->setRole(MessageRole::USER);
        $message->setContent('短消息');

        $result = (string) $message;

        $this->assertEquals('用户消息: 短消息...', $result);
    }

    public function testCompleteMessageWorkflowShouldWork(): void
    {
        $message = new Message();

        // 创建消息
        $message->setConversation($this->getConversation());
        $message->setRole(MessageRole::USER);
        $message->setContent('请帮我解答一个问题');
        $message->setStatus(MessageStatus::PENDING);

        // 发送消息
        $sentAt = new \DateTimeImmutable();
        $message->setStatus(MessageStatus::SENT);
        $message->setSentAt($sentAt);

        // 接收回复
        $receivedAt = new \DateTimeImmutable();
        $message->setStatus(MessageStatus::RECEIVED);
        $message->setReceivedAt($receivedAt);
        $message->setMetadata(['processing_time' => 2.5]);

        $this->assertEquals(MessageStatus::RECEIVED, $message->getStatus());
        $this->assertEquals($sentAt, $message->getSentAt());
        $this->assertEquals($receivedAt, $message->getReceivedAt());
        $this->assertEquals(['processing_time' => 2.5], $message->getMetadata());
    }

    public function testFailedMessageWorkflowShouldWork(): void
    {
        $message = new Message();

        // 创建消息并发送失败
        $message->setConversation($this->getConversation());
        $message->setRole(MessageRole::USER);
        $message->setContent('测试失败场景');
        $message->setStatus(MessageStatus::FAILED);
        $message->setErrorMessage('Connection timeout');
        $message->setRetryCount(1);

        $this->assertEquals(MessageStatus::FAILED, $message->getStatus());
        $this->assertEquals('Connection timeout', $message->getErrorMessage());
        $this->assertEquals(1, $message->getRetryCount());
    }
}
