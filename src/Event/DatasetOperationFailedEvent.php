<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Throwable;
use Tourze\DifyClientBundle\Entity\Dataset;

/**
 * 数据集操作失败事件
 */
final class DatasetOperationFailedEvent extends Event
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly Dataset $dataset,
        private readonly string $operation,
        private readonly string $errorMessage,
        private readonly ?\Throwable $exception = null,
        private readonly array $metadata = [],
    ) {
    }

    public function getDataset(): Dataset
    {
        return $this->dataset;
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
