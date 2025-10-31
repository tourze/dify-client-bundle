<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Entity\DatasetTag;
use Tourze\DifyClientBundle\Entity\Document;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Dataset::class)]
final class DatasetTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): Dataset
    {
        return new Dataset();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'datasetId' => ['datasetId', 'dataset-12345'];
        yield 'name' => ['name', '测试数据集'];
        yield 'description' => ['description', '这是一个测试数据集'];
        yield 'dataSourceType' => ['dataSourceType', 'qa'];
        yield 'indexingTechnique' => ['indexingTechnique', 'economy'];
        yield 'createdBy' => ['createdBy', 'user-123'];
        yield 'documentCount' => ['documentCount', 10];
        yield 'wordCount' => ['wordCount', 50000];
        yield 'embeddingModel' => ['embeddingModel', 'text-embedding-ada-002'];
        yield 'embeddingModelProvider' => ['embeddingModelProvider', 'openai'];
    }

    public function testCreateDatasetWithDefaultValuesShouldSucceed(): void
    {
        $dataset = $this->createEntity();

        $this->assertNull($dataset->getId());
        $this->assertNull($dataset->getDatasetId());
        $this->assertNull($dataset->getDescription());
        $this->assertEquals(0, $dataset->getDocumentCount());
        $this->assertEquals(0, $dataset->getWordCount());
        $this->assertNull($dataset->getEmbeddingModel());
        $this->assertNull($dataset->getEmbeddingModelProvider());
        $this->assertNull($dataset->getMetadata());
        $this->assertEmpty($dataset->getDocuments());
        $this->assertEmpty($dataset->getTags());
    }

    public function testSetDatasetIdShouldUpdateValue(): void
    {
        $dataset = $this->createEntity();
        $datasetId = 'dataset-12345';

        $dataset->setDatasetId($datasetId);

        $this->assertEquals($datasetId, $dataset->getDatasetId());
    }

    public function testSetNameShouldUpdateValue(): void
    {
        $dataset = $this->createEntity();
        $name = 'AI知识库';

        $dataset->setName($name);

        $this->assertEquals($name, $dataset->getName());
    }

    public function testAddDocumentShouldAddNewDocument(): void
    {
        $dataset = $this->createEntity();
        $document = $this->createMock(Document::class);
        $document->method('getDataset')->willReturn($dataset);

        $dataset->addDocument($document);

        $this->assertTrue($dataset->getDocuments()->contains($document));
    }

    public function testRemoveDocumentShouldRemoveExistingDocument(): void
    {
        $dataset = $this->createEntity();
        $document = $this->createMock(Document::class);
        $document->method('getDataset')->willReturn($dataset);

        $dataset->addDocument($document);
        $dataset->removeDocument($document);

        $this->assertFalse($dataset->getDocuments()->contains($document));
    }

    public function testAddTagShouldAddNewTag(): void
    {
        $dataset = $this->createEntity();
        $tag = new class extends DatasetTag {
            public function addDataset(Dataset $dataset): self
            {
                $this->getDatasets()->add($dataset);

                return $this;
            }

            public function removeDataset(Dataset $dataset): self
            {
                $this->getDatasets()->removeElement($dataset);

                return $this;
            }
        };

        $dataset->addTag($tag);

        $this->assertTrue($dataset->getTags()->contains($tag));
        $this->assertTrue($tag->getDatasets()->contains($dataset));
    }

    public function testRemoveTagShouldRemoveExistingTag(): void
    {
        $dataset = $this->createEntity();
        $tag = new class extends DatasetTag {
            public function addDataset(Dataset $dataset): self
            {
                $this->getDatasets()->add($dataset);

                return $this;
            }

            public function removeDataset(Dataset $dataset): self
            {
                $this->getDatasets()->removeElement($dataset);

                return $this;
            }
        };

        $dataset->addTag($tag);
        $this->assertTrue($dataset->getTags()->contains($tag));
        $this->assertTrue($tag->getDatasets()->contains($dataset));

        $dataset->removeTag($tag);

        $this->assertFalse($dataset->getTags()->contains($tag));
        $this->assertFalse($tag->getDatasets()->contains($dataset));
    }

    public function testToStringShouldReturnNameAndDocumentCount(): void
    {
        $dataset = $this->createEntity();
        $dataset->setName('AI知识库');
        $dataset->setDocumentCount(25);

        $result = (string) $dataset;

        $this->assertEquals('AI知识库 (25文档)', $result);
    }
}
