<?php

namespace Tourze\DifyClientBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Event\DifyErrorEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(DifyErrorEvent::class)]
final class DifyErrorEventTest extends AbstractEventTestCase
{
    public function testEventShouldStoreConversationAndMessage(): void
    {
        $conversation = new Conversation();
        $message = new Message();
        $errorMessage = 'Test error message';

        $event = new DifyErrorEvent($conversation, $message, $errorMessage);

        $this->assertSame($conversation, $event->getConversation());
        $this->assertSame($message, $event->getMessage());
        $this->assertEquals($errorMessage, $event->getErrorMessage());
        $this->assertNull($event->getException());
    }

    public function testEventShouldAcceptException(): void
    {
        $conversation = new Conversation();
        $errorMessage = 'Test error with exception';
        $exception = new \RuntimeException('Test exception');

        $event = new DifyErrorEvent($conversation, null, $errorMessage, $exception);

        $this->assertSame($conversation, $event->getConversation());
        $this->assertNull($event->getMessage());
        $this->assertEquals($errorMessage, $event->getErrorMessage());
        $this->assertSame($exception, $event->getException());
    }

    public function testEventShouldWorkWithNullMessage(): void
    {
        $conversation = new Conversation();
        $errorMessage = 'Test error without message';

        $event = new DifyErrorEvent($conversation, null, $errorMessage);

        $this->assertSame($conversation, $event->getConversation());
        $this->assertNull($event->getMessage());
        $this->assertEquals($errorMessage, $event->getErrorMessage());
        $this->assertNull($event->getException());
    }

    public function testEventShouldWorkWithNullException(): void
    {
        $conversation = new Conversation();
        $message = new Message();
        $errorMessage = 'Test error without exception';

        $event = new DifyErrorEvent($conversation, $message, $errorMessage);

        $this->assertSame($conversation, $event->getConversation());
        $this->assertSame($message, $event->getMessage());
        $this->assertEquals($errorMessage, $event->getErrorMessage());
        $this->assertNull($event->getException());
    }
}
