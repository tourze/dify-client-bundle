<?php

namespace Tourze\DifyClientBundle\Tests\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\RequestTask;
use Tourze\DifyClientBundle\Message\ProcessDifyMessage;

/**
 * @internal
 */
#[CoversClass(ProcessDifyMessage::class)]
final class ProcessDifyMessageTest extends TestCase
{
    public function testMessageShouldStoreRequestTaskAndContent(): void
    {
        $requestTask = new RequestTask();
        $requestTask->setTaskId('task-123');
        $requestTask->setAggregatedContent('Test content');

        $message = new ProcessDifyMessage($requestTask, 'AI response content');

        $this->assertSame($requestTask, $message->getRequestTask());
        $this->assertEquals('AI response content', $message->getContent());
        $this->assertEquals([], $message->getOriginalMessages());
    }

    public function testMessageShouldStoreOriginalMessages(): void
    {
        $requestTask = new RequestTask();
        $requestTask->setTaskId('task-123');

        $originalMessages = [
            new Message(),
            new Message(),
        ];

        $message = new ProcessDifyMessage($requestTask, 'Response', $originalMessages);

        $this->assertSame($requestTask, $message->getRequestTask());
        $this->assertEquals('Response', $message->getContent());
        $this->assertSame($originalMessages, $message->getOriginalMessages());
        $this->assertCount(2, $message->getOriginalMessages());
    }

    public function testMessageShouldHandleEmptyContent(): void
    {
        $requestTask = new RequestTask();
        $requestTask->setTaskId('task-123');

        $message = new ProcessDifyMessage($requestTask, '');

        $this->assertSame($requestTask, $message->getRequestTask());
        $this->assertEquals('', $message->getContent());
        $this->assertEquals([], $message->getOriginalMessages());
    }

    public function testMessageShouldHandleEmptyOriginalMessages(): void
    {
        $requestTask = new RequestTask();
        $requestTask->setTaskId('task-123');

        $message = new ProcessDifyMessage($requestTask, 'Content', []);

        $this->assertSame($requestTask, $message->getRequestTask());
        $this->assertEquals('Content', $message->getContent());
        $this->assertEquals([], $message->getOriginalMessages());
    }

    public function testMessageShouldPreserveRequestTaskState(): void
    {
        $requestTask = new RequestTask();
        $requestTask->setTaskId('task-456');
        $requestTask->setAggregatedContent('Original user messages');
        $requestTask->setMessageCount(3);

        $message = new ProcessDifyMessage($requestTask, 'AI response');

        $this->assertSame($requestTask, $message->getRequestTask());
        $this->assertEquals('task-456', $message->getRequestTask()->getTaskId());
        $this->assertEquals('Original user messages', $message->getRequestTask()->getAggregatedContent());
        $this->assertEquals(3, $message->getRequestTask()->getMessageCount());
    }
}
