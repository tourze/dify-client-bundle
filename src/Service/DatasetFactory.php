<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Psr\Clock\ClockInterface;
use Tourze\DifyClientBundle\Entity\Dataset;

/**
 * 数据集工厂 - 专门处理数据集实体的创建
 */
readonly class DatasetFactory
{
    public function __construct(private ClockInterface $clock)
    {
    }

    /**
     * 创建数据集
     */
    public function create(string $name, ?string $description, string $indexingTechnique): Dataset
    {
        $dataset = new Dataset();
        $dataset->setName($name);
        $dataset->setDescription($description);
        $dataset->setIndexingTechnique($indexingTechnique);
        $dataset->setDocumentCount(0);
        $dataset->setWordCount(0);
        $dataset->setCreateTime($this->clock->now());

        return $dataset;
    }
}
