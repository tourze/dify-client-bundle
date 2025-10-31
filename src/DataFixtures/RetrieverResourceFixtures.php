<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\RetrieverResource;

class RetrieverResourceFixtures extends Fixture
{
    public const RETRIEVER_RESOURCE_REFERENCE = 'retriever-resource-1';

    public function load(ObjectManager $manager): void
    {
        $resource = new RetrieverResource();
        $resource->setPosition(1); // 添加必需的位置字段
        $resource->setDatasetId('dataset-123'); // 添加必需的数据集ID
        $resource->setDatasetName('AI知识库'); // 添加必需的数据集名称
        $resource->setDocumentId('doc-456'); // 添加必需的文档ID
        $resource->setDocumentName('AI入门指南.pdf'); // 添加必需的文档名称
        $resource->setSegmentId('segment-789'); // 添加必需的段落ID
        $resource->setResourceId('resource-123');
        $resource->setResourceType('document');
        $resource->setResourceUrl('https://test.localhost/docs/ai-guide.pdf');
        $resource->setTitle('AI入门指南');
        $resource->setContent('人工智能入门知识点...');
        $resource->setScore(0.95);
        $resource->setMetadata([
            'source' => 'knowledge_base',
            'chunk_id' => 'chunk-456',
        ]);
        $resource->setRetrievedAt(new \DateTimeImmutable('2024-01-01 10:00:00'));

        $manager->persist($resource);
        $manager->flush();

        $this->addReference(self::RETRIEVER_RESOURCE_REFERENCE, $resource);
    }
}
