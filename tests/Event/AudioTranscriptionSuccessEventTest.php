<?php

namespace Tourze\DifyClientBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\AudioTranscription;
use Tourze\DifyClientBundle\Event\AudioTranscriptionSuccessEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(AudioTranscriptionSuccessEvent::class)]
final class AudioTranscriptionSuccessEventTest extends AbstractEventTestCase
{
    public function testEventShouldStoreAllProperties(): void
    {
        $transcription = new AudioTranscription();
        $operation = 'transcribe';
        $metadata = ['result' => 'success', 'duration' => 120];

        $event = new AudioTranscriptionSuccessEvent($transcription, $operation, $metadata);

        $this->assertSame($transcription, $event->getTranscription());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals($metadata, $event->getMetadata());
    }

    public function testEventShouldWorkWithoutMetadata(): void
    {
        $transcription = new AudioTranscription();
        $operation = 'transcribe';

        $event = new AudioTranscriptionSuccessEvent($transcription, $operation);

        $this->assertSame($transcription, $event->getTranscription());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals([], $event->getMetadata());
    }

    public function testEventShouldWorkWithEmptyMetadata(): void
    {
        $transcription = new AudioTranscription();
        $operation = 'transcribe';
        $metadata = [];

        $event = new AudioTranscriptionSuccessEvent($transcription, $operation, $metadata);

        $this->assertSame($transcription, $event->getTranscription());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals([], $event->getMetadata());
    }
}
