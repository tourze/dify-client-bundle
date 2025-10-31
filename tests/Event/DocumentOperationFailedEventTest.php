<?php

namespace Tourze\DifyClientBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\Document;
use Tourze\DifyClientBundle\Event\DocumentOperationFailedEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(DocumentOperationFailedEvent::class)]
final class DocumentOperationFailedEventTest extends AbstractEventTestCase
{
    public function testEventShouldStoreAllProperties(): void
    {
        $document = new Document();
        $operation = 'create';
        $errorMessage = 'Document creation failed';
        $exception = new \RuntimeException('Test exception');
        $metadata = ['key' => 'value'];

        $event = new DocumentOperationFailedEvent(
            $document,
            $operation,
            $errorMessage,
            $exception,
            $metadata
        );

        $this->assertSame($document, $event->getDocument());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals($errorMessage, $event->getErrorMessage());
        $this->assertSame($exception, $event->getException());
        $this->assertEquals($metadata, $event->getMetadata());
    }

    public function testEventShouldWorkWithoutException(): void
    {
        $document = new Document();
        $operation = 'update';
        $errorMessage = 'Document update failed without exception';

        $event = new DocumentOperationFailedEvent($document, $operation, $errorMessage);

        $this->assertSame($document, $event->getDocument());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals($errorMessage, $event->getErrorMessage());
        $this->assertNull($event->getException());
        $this->assertEquals([], $event->getMetadata());
    }

    public function testEventShouldWorkWithoutMetadata(): void
    {
        $document = new Document();
        $operation = 'delete';
        $errorMessage = 'Document deletion failed without metadata';
        $exception = new \RuntimeException('Test exception');

        $event = new DocumentOperationFailedEvent($document, $operation, $errorMessage, $exception);

        $this->assertSame($document, $event->getDocument());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals($errorMessage, $event->getErrorMessage());
        $this->assertSame($exception, $event->getException());
        $this->assertEquals([], $event->getMetadata());
    }
}
