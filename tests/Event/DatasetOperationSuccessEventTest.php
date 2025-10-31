<?php

namespace Tourze\DifyClientBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Event\DatasetOperationSuccessEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(DatasetOperationSuccessEvent::class)]
final class DatasetOperationSuccessEventTest extends AbstractEventTestCase
{
    public function testEventShouldStoreAllProperties(): void
    {
        $dataset = new Dataset();
        $operation = 'create';
        $metadata = ['result' => 'success', 'id' => 123];

        $event = new DatasetOperationSuccessEvent($dataset, $operation, $metadata);

        $this->assertSame($dataset, $event->getDataset());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals($metadata, $event->getMetadata());
    }

    public function testEventShouldWorkWithoutMetadata(): void
    {
        $dataset = new Dataset();
        $operation = 'update';

        $event = new DatasetOperationSuccessEvent($dataset, $operation);

        $this->assertSame($dataset, $event->getDataset());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals([], $event->getMetadata());
    }

    public function testEventShouldWorkWithEmptyMetadata(): void
    {
        $dataset = new Dataset();
        $operation = 'delete';
        $metadata = [];

        $event = new DatasetOperationSuccessEvent($dataset, $operation, $metadata);

        $this->assertSame($dataset, $event->getDataset());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals([], $event->getMetadata());
    }
}
