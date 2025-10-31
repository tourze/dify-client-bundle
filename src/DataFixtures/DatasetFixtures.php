<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\Dataset;

class DatasetFixtures extends Fixture
{
    public const DATASET_REFERENCE = 'dataset-1';

    public function load(ObjectManager $manager): void
    {
        $dataset = new Dataset();
        $dataset->setDatasetId('dataset-123');
        $dataset->setName('知识库数据集');
        $dataset->setDescription('包含AI相关知识的数据集，用于RAG检索增强生成。');
        $dataset->setDataSourceType('text');
        $dataset->setIndexingTechnique('high_quality');
        $dataset->setCreatedBy('admin-user');
        $dataset->setDocumentCount(50);
        $dataset->setWordCount(125000);
        $dataset->setEmbeddingModel('text-embedding-ada-002');
        $dataset->setEmbeddingModelProvider('openai');
        $dataset->setMetadata([
            'version' => '1.0',
            'category' => 'knowledge-base',
            'language' => 'zh-CN',
            'domain' => 'AI',
        ]);

        $manager->persist($dataset);
        $manager->flush();

        $this->addReference(self::DATASET_REFERENCE, $dataset);
    }
}
