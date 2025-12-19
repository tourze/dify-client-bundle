<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tourze\DifyClientBundle\Entity\Document;

/**
 * 文档操作成功事件
 */
final class DocumentOperationSuccessEvent extends Event
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly Document $document,
        private readonly string $operation,
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

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
