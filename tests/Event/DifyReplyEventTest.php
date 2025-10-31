<?php

namespace Tourze\DifyClientBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Event\DifyReplyEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(DifyReplyEvent::class)]
final class DifyReplyEventTest extends AbstractEventTestCase
{
    public function testEventShouldStoreReplyData(): void
    {
        $conversation = new Conversation();
        $conversation->setConversationId('test-conv-123');

        $event = new DifyReplyEvent($conversation, 'Hello, how can I help you?', null);

        $this->assertSame($conversation, $event->getConversation());
        $this->assertEquals('Hello, how can I help you?', $event->getReply());
        $this->assertNull($event->getOriginalMessage());
        $this->assertTrue($event->isComplete());
    }

    public function testEventShouldStoreOriginalMessage(): void
    {
        $conversation = new Conversation();
        $conversation->setConversationId('test-conv-123');

        $originalMessage = new Message();
        $originalMessage->setContent('Original question');

        $event = new DifyReplyEvent($conversation, 'AI response', $originalMessage);

        $this->assertSame($conversation, $event->getConversation());
        $this->assertEquals('AI response', $event->getReply());
        $this->assertSame($originalMessage, $event->getOriginalMessage());
        $this->assertTrue($event->isComplete());
    }

    public function testEventShouldHandleIncompleteReply(): void
    {
        $conversation = new Conversation();
        $conversation->setConversationId('test-conv-123');

        $event = new DifyReplyEvent($conversation, 'Partial response...', null, false);

        $this->assertSame($conversation, $event->getConversation());
        $this->assertEquals('Partial response...', $event->getReply());
        $this->assertNull($event->getOriginalMessage());
        $this->assertFalse($event->isComplete());
    }

    public function testEventShouldHandleEmptyReply(): void
    {
        $conversation = new Conversation();
        $conversation->setConversationId('test-conv-123');

        $event = new DifyReplyEvent($conversation, '', null);

        $this->assertSame($conversation, $event->getConversation());
        $this->assertEquals('', $event->getReply());
        $this->assertNull($event->getOriginalMessage());
        $this->assertTrue($event->isComplete());
    }
}
