<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tourze\DifyClientBundle\Entity\AudioTranscription;

/**
 * 音频转录成功事件
 */
final class AudioTranscriptionSuccessEvent extends Event
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly AudioTranscription $transcription,
        private readonly string $operation,
        private readonly array $metadata = [],
    ) {
    }

    public function getTranscription(): AudioTranscription
    {
        return $this->transcription;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
