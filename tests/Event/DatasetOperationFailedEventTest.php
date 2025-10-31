<?php

namespace Tourze\DifyClientBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Event\DatasetOperationFailedEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(DatasetOperationFailedEvent::class)]
final class DatasetOperationFailedEventTest extends AbstractEventTestCase
{
    public function testEventShouldStoreAllProperties(): void
    {
        $dataset = new Dataset();
        $operation = 'create';
        $errorMessage = 'Dataset creation failed';
        $exception = new \RuntimeException('Test exception');
        $metadata = ['key' => 'value'];

        $event = new DatasetOperationFailedEvent(
            $dataset,
            $operation,
            $errorMessage,
            $exception,
            $metadata
        );

        $this->assertSame($dataset, $event->getDataset());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals($errorMessage, $event->getErrorMessage());
        $this->assertSame($exception, $event->getException());
        $this->assertEquals($metadata, $event->getMetadata());
    }

    public function testEventShouldWorkWithoutException(): void
    {
        $dataset = new Dataset();
        $operation = 'update';
        $errorMessage = 'Dataset update failed without exception';

        $event = new DatasetOperationFailedEvent($dataset, $operation, $errorMessage);

        $this->assertSame($dataset, $event->getDataset());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals($errorMessage, $event->getErrorMessage());
        $this->assertNull($event->getException());
        $this->assertEquals([], $event->getMetadata());
    }

    public function testEventShouldWorkWithoutMetadata(): void
    {
        $dataset = new Dataset();
        $operation = 'delete';
        $errorMessage = 'Dataset deletion failed without metadata';
        $exception = new \RuntimeException('Test exception');

        $event = new DatasetOperationFailedEvent($dataset, $operation, $errorMessage, $exception);

        $this->assertSame($dataset, $event->getDataset());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals($errorMessage, $event->getErrorMessage());
        $this->assertSame($exception, $event->getException());
        $this->assertEquals([], $event->getMetadata());
    }
}
