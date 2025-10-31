<?php

namespace Tourze\DifyClientBundle\Tests\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyClientBundle\Message\RetryFailedMessage;

/**
 * @internal
 */
#[CoversClass(RetryFailedMessage::class)]
final class RetryFailedMessageTest extends TestCase
{
    public function testMessageShouldStoreFailedMessageId(): void
    {
        $message = new RetryFailedMessage('123');

        $this->assertEquals('123', $message->getFailedMessageId());
        $this->assertNull($message->getTaskId());
        $this->assertEquals([], $message->getContext());
        $this->assertFalse($message->shouldRetryWholeBatch());
    }

    public function testMessageShouldAcceptOptionalParameters(): void
    {
        $message = new RetryFailedMessage(
            '456',
            'task-789',
            ['retry_reason' => 'timeout'],
            true
        );

        $this->assertEquals('456', $message->getFailedMessageId());
        $this->assertEquals('task-789', $message->getTaskId());
        $this->assertEquals(['retry_reason' => 'timeout'], $message->getContext());
        $this->assertTrue($message->shouldRetryWholeBatch());
    }

    public function testMessageShouldHandleZeroId(): void
    {
        $message = new RetryFailedMessage('0');

        $this->assertEquals('0', $message->getFailedMessageId());
    }

    public function testMessageShouldHandleNegativeId(): void
    {
        $message = new RetryFailedMessage('-1');

        $this->assertEquals('-1', $message->getFailedMessageId());
    }

    public function testMessageShouldHandleEmptyContext(): void
    {
        $message = new RetryFailedMessage('123', 'task-456', []);

        $this->assertEquals('123', $message->getFailedMessageId());
        $this->assertEquals('task-456', $message->getTaskId());
        $this->assertEquals([], $message->getContext());
        $this->assertFalse($message->shouldRetryWholeBatch());
    }

    public function testMessageShouldHandleComplexContext(): void
    {
        $context = [
            'retry_reason' => 'timeout',
            'retry_count' => 3,
            'original_error' => 'Connection failed',
            'timestamp' => '2024-01-01T10:00:00Z',
        ];

        $message = new RetryFailedMessage('789', 'task-999', $context, true);

        $this->assertEquals('789', $message->getFailedMessageId());
        $this->assertEquals('task-999', $message->getTaskId());
        $this->assertEquals($context, $message->getContext());
        $this->assertTrue($message->shouldRetryWholeBatch());
    }

    public function testShouldRetryWholeBatch(): void
    {
        $message = new RetryFailedMessage('123', null, [], false);
        $this->assertFalse($message->shouldRetryWholeBatch());

        $message = new RetryFailedMessage('123', null, [], true);
        $this->assertTrue($message->shouldRetryWholeBatch());
    }
}
