<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Throwable;
use Tourze\DifyClientBundle\Entity\Document;

/**
 * 文档操作失败事件
 */
final class DocumentOperationFailedEvent extends Event
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly Document $document,
        private readonly string $operation,
        private readonly string $errorMessage,
        private readonly ?\Throwable $exception = null,
        private readonly array $metadata = [],
    ) {
    }

    public function getDocument(): Document
    {
        return $this->document;
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
