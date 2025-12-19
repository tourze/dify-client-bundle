<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Throwable;
use Tourze\DifyClientBundle\Entity\AudioTranscription;

/**
 * 音频转录失败事件
 */
final class AudioTranscriptionFailedEvent extends Event
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly AudioTranscription $transcription,
        private readonly string $operation,
        private readonly string $errorMessage,
        private readonly ?\Throwable $exception = null,
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

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function getException(): ?\Throwable
    {
        return $this->exception;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
