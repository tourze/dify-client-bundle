<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tourze\DifyClientBundle\Entity\Dataset;

/**
 * 数据集操作成功事件
 */
class DatasetOperationSuccessEvent extends Event
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly Dataset $dataset,
        private readonly string $operation,
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

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
