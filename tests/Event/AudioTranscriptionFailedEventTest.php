<?php

namespace Tourze\DifyClientBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\AudioTranscription;
use Tourze\DifyClientBundle\Event\AudioTranscriptionFailedEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(AudioTranscriptionFailedEvent::class)]
final class AudioTranscriptionFailedEventTest extends AbstractEventTestCase
{
    public function testEventShouldStoreAllProperties(): void
    {
        $transcription = new AudioTranscription();
        $operation = 'transcribe';
        $errorMessage = 'Transcription failed';
        $exception = new \RuntimeException('Test exception');
        $metadata = ['key' => 'value'];

        $event = new AudioTranscriptionFailedEvent(
            $transcription,
            $operation,
            $errorMessage,
            $exception,
            $metadata
        );

        $this->assertSame($transcription, $event->getTranscription());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals($errorMessage, $event->getErrorMessage());
        $this->assertSame($exception, $event->getException());
        $this->assertEquals($metadata, $event->getMetadata());
    }

    public function testEventShouldWorkWithoutException(): void
    {
        $transcription = new AudioTranscription();
        $operation = 'transcribe';
        $errorMessage = 'Transcription failed without exception';

        $event = new AudioTranscriptionFailedEvent($transcription, $operation, $errorMessage);

        $this->assertSame($transcription, $event->getTranscription());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals($errorMessage, $event->getErrorMessage());
        $this->assertNull($event->getException());
        $this->assertEquals([], $event->getMetadata());
    }

    public function testEventShouldWorkWithoutMetadata(): void
    {
        $transcription = new AudioTranscription();
        $operation = 'transcribe';
        $errorMessage = 'Transcription failed without metadata';
        $exception = new \RuntimeException('Test exception');

        $event = new AudioTranscriptionFailedEvent($transcription, $operation, $errorMessage, $exception);

        $this->assertSame($transcription, $event->getTranscription());
        $this->assertEquals($operation, $event->getOperation());
        $this->assertEquals($errorMessage, $event->getErrorMessage());
        $this->assertSame($exception, $event->getException());
        $this->assertEquals([], $event->getMetadata());
    }
}
