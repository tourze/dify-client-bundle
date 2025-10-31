<?php

namespace Tourze\DifyClientBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\Document;
use Tourze\DifyClientBundle\Event\DocumentOperationSuccessEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(DocumentOperationSuccessEvent::class)]
final class DocumentOperationSuccessEventTest extends AbstractEventTestCase
{
    public function testEventShouldStoreAllProperties(): void
    {
        $document = new Document();
        $operation = 'create';
        $metadata = ['result' => 'success', 'id' => 123];

        $event = new DocumentOperationSuccessEvent($document, $operation, $metadata);

        $this->assertSame($document, $event->getDocument());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals($metadata, $event->getMetadata());
    }

    public function testEventShouldWorkWithoutMetadata(): void
    {
        $document = new Document();
        $operation = 'update';

        $event = new DocumentOperationSuccessEvent($document, $operation);

        $this->assertSame($document, $event->getDocument());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals([], $event->getMetadata());
    }

    public function testEventShouldWorkWithEmptyMetadata(): void
    {
        $document = new Document();
        $operation = 'delete';
        $metadata = [];

        $event = new DocumentOperationSuccessEvent($document, $operation, $metadata);

        $this->assertSame($document, $event->getDocument());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals([], $event->getMetadata());
    }
}
