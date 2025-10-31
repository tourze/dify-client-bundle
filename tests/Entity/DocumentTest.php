<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Entity\Document;
use Tourze\DifyClientBundle\Entity\DocumentChunk;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Document::class)]
final class DocumentTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): Document
    {
        return new Document();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'documentId' => ['documentId', 'doc-12345'];
        yield 'name' => ['name', 'API文档.pdf'];
        yield 'text' => ['text', '这是文档的文本内容'];
        yield 'originalFilename' => ['originalFilename', 'api-documentation.pdf'];
        yield 'mimeType' => ['mimeType', 'application/pdf'];
        yield 'content' => ['content', '这是文档的完整内容'];
        yield 'dataSource' => ['dataSource', 'upload_file'];
        yield 'indexingTechnique' => ['indexingTechnique', 'high_quality'];
        yield 'indexingStatus' => ['indexingStatus', 'completed'];
        yield 'enabled' => ['enabled', false];
        yield 'createdBy' => ['createdBy', 'user-123'];
        yield 'characterCount' => ['characterCount', 1500];
        yield 'chunkCount' => ['chunkCount', 5];
        yield 'fileSize' => ['fileSize', 2048000];
        yield 'fileType' => ['fileType', 'pdf'];
        yield 'fileUrl' => ['fileUrl', 'https://example.com/docs/api.pdf'];
        yield 'errorMessage' => ['errorMessage', '索引过程中发生错误'];
        yield 'processingStatus' => ['processingStatus', 'completed'];
        yield 'wordCount' => ['wordCount', 300];
        yield 'tokens' => ['tokens', 2000];
        yield 'userId' => ['userId', 'user-456'];
    }

    public function testCreateDocumentWithDefaultValuesShouldSucceed(): void
    {
        $document = $this->createEntity();

        $this->assertNull($document->getId());
        $this->assertNull($document->getDocumentId());
        $this->assertNull($document->getDataset());
        $this->assertNull($document->getText());
        $this->assertNull($document->getOriginalFilename());
        $this->assertNull($document->getMimeType());
        $this->assertNull($document->getContent());
        $this->assertEquals('pending', $document->getIndexingStatus());
        $this->assertTrue($document->isEnabled());
        $this->assertNull($document->getCreatedBy());
        $this->assertEquals(0, $document->getCharacterCount());
        $this->assertEquals(0, $document->getChunkCount());
        $this->assertNull($document->getFileSize());
        $this->assertNull($document->getFileType());
        $this->assertNull($document->getFileUrl());
        $this->assertNull($document->getErrorMessage());
        $this->assertNull($document->getProcessingStatus());
        $this->assertEquals(0, $document->getWordCount());
        $this->assertEquals(0, $document->getTokens());
        $this->assertNull($document->getUserId());
        $this->assertNull($document->getMetadata());
        $this->assertNull($document->getIndexingStartedAt());
        $this->assertNull($document->getIndexingCompletedAt());
        $this->assertEmpty($document->getChunks());
    }

    public function testSetDocumentIdShouldUpdateValue(): void
    {
        $document = $this->createEntity();
        $documentId = 'doc-12345';

        $document->setDocumentId($documentId);

        $this->assertEquals($documentId, $document->getDocumentId());
    }

    public function testSetDocumentIdWithNullShouldAcceptNull(): void
    {
        $document = $this->createEntity();
        $document->setDocumentId('doc-123');

        $document->setDocumentId(null);

        $this->assertNull($document->getDocumentId());
    }

    public function testSetDatasetShouldUpdateValue(): void
    {
        $document = $this->createEntity();
        $dataset = $this->createMock(Dataset::class);

        $document->setDataset($dataset);

        $this->assertEquals($dataset, $document->getDataset());
    }

    public function testSetDatasetWithNullShouldAcceptNull(): void
    {
        $document = $this->createEntity();
        $dataset = $this->createMock(Dataset::class);
        $document->setDataset($dataset);

        $document->setDataset(null);

        $this->assertNull($document->getDataset());
    }

    public function testSetNameShouldUpdateValue(): void
    {
        $document = $this->createEntity();
        $name = 'API接口文档';

        $document->setName($name);

        $this->assertEquals($name, $document->getName());
    }

    public function testSetDataSourceShouldUpdateValue(): void
    {
        $document = $this->createEntity();
        $dataSource = 'notion_import';

        $document->setDataSource($dataSource);

        $this->assertEquals($dataSource, $document->getDataSource());
    }

    #[TestWith(['upload_file'], 'upload_file')]
    #[TestWith(['notion_import'], 'notion_import')]
    #[TestWith(['text_input'], 'text_input')]
    public function testSetDataSourceWithValidValuesShouldSucceed(string $dataSource): void
    {
        $document = $this->createEntity();

        $document->setDataSource($dataSource);

        $this->assertEquals($dataSource, $document->getDataSource());
    }

    public function testSetIndexingTechniqueShouldUpdateValue(): void
    {
        $document = $this->createEntity();
        $indexingTechnique = 'economy';

        $document->setIndexingTechnique($indexingTechnique);

        $this->assertEquals($indexingTechnique, $document->getIndexingTechnique());
    }

    #[TestWith(['economy'], 'economy')]
    #[TestWith(['high_quality'], 'high_quality')]
    public function testSetIndexingTechniqueWithValidValuesShouldSucceed(string $indexingTechnique): void
    {
        $document = $this->createEntity();

        $document->setIndexingTechnique($indexingTechnique);

        $this->assertEquals($indexingTechnique, $document->getIndexingTechnique());
    }

    public function testSetIndexingStatusShouldUpdateValue(): void
    {
        $document = $this->createEntity();
        $indexingStatus = 'completed';

        $document->setIndexingStatus($indexingStatus);

        $this->assertEquals($indexingStatus, $document->getIndexingStatus());
    }

    #[TestWith(['pending'], 'pending')]
    #[TestWith(['parsing'], 'parsing')]
    #[TestWith(['cleaning'], 'cleaning')]
    #[TestWith(['splitting'], 'splitting')]
    #[TestWith(['indexing'], 'indexing')]
    #[TestWith(['completed'], 'completed')]
    #[TestWith(['error'], 'error')]
    #[TestWith(['paused'], 'paused')]
    public function testSetIndexingStatusWithValidValuesShouldSucceed(string $indexingStatus): void
    {
        $document = $this->createEntity();

        $document->setIndexingStatus($indexingStatus);

        $this->assertEquals($indexingStatus, $document->getIndexingStatus());
    }

    public function testSetEnabledShouldUpdateValue(): void
    {
        $document = $this->createEntity();

        $document->setEnabled(false);

        $this->assertFalse($document->isEnabled());

        $document->setEnabled(true);
        $this->assertTrue($document->isEnabled());
    }

    public function testSetTextShouldUpdateValue(): void
    {
        $document = $this->createEntity();
        $text = '这是文档的文本内容';

        $document->setText($text);

        $this->assertEquals($text, $document->getText());
    }

    public function testSetTextWithNullShouldAcceptNull(): void
    {
        $document = $this->createEntity();
        $document->setText('原始文本');

        $document->setText(null);

        $this->assertNull($document->getText());
    }

    public function testSetContentShouldUpdateValue(): void
    {
        $document = $this->createEntity();
        $content = '这是文档的完整内容';

        $document->setContent($content);

        $this->assertEquals($content, $document->getContent());
    }

    public function testSetMetadataShouldUpdateValue(): void
    {
        $document = $this->createEntity();
        $metadata = [
            'author' => 'John Doe',
            'version' => '1.2.0',
            'tags' => ['api', 'documentation'],
            'language' => 'zh-CN',
        ];

        $document->setMetadata($metadata);

        $this->assertEquals($metadata, $document->getMetadata());
    }

    public function testSetMetadataWithNullShouldAcceptNull(): void
    {
        $document = $this->createEntity();
        $document->setMetadata(['key' => 'value']);

        $document->setMetadata(null);

        $this->assertNull($document->getMetadata());
    }

    public function testAddChunkShouldAddNewChunk(): void
    {
        $document = $this->createEntity();
        $chunk = new class extends DocumentChunk {
            private ?Document $document = null;

            public function setDocument(?Document $document): void
            {
                $this->document = $document;
            }

            public function getDocument(): ?Document
            {
                return $this->document;
            }
        };

        $document->addChunk($chunk);

        $this->assertTrue($document->getChunks()->contains($chunk));
        $this->assertSame($document, $chunk->getDocument());
    }

    public function testAddChunkShouldNotAddDuplicateChunk(): void
    {
        $document = $this->createEntity();
        $chunk = new class extends DocumentChunk {
            private ?Document $document = null;

            public function setDocument(?Document $document): void
            {
                $this->document = $document;
            }

            public function getDocument(): ?Document
            {
                return $this->document;
            }
        };

        $document->addChunk($chunk);
        $document->addChunk($chunk);

        $this->assertEquals(1, $document->getChunks()->count());
        $this->assertSame($document, $chunk->getDocument());
    }

    public function testRemoveChunkShouldRemoveExistingChunk(): void
    {
        $document = $this->createEntity();
        $chunk = new class extends DocumentChunk {
            private ?Document $document = null;

            public function setDocument(?Document $document): void
            {
                $this->document = $document;
            }

            public function getDocument(): ?Document
            {
                return $this->document;
            }
        };

        $document->addChunk($chunk);
        $this->assertTrue($document->getChunks()->contains($chunk));
        $this->assertSame($document, $chunk->getDocument());

        $document->removeChunk($chunk);

        $this->assertFalse($document->getChunks()->contains($chunk));
        $this->assertNull($chunk->getDocument());
    }

    public function testIsIndexingCompletedShouldReturnCorrectValue(): void
    {
        $document = $this->createEntity();

        $document->setIndexingStatus('pending');
        $this->assertFalse($document->isIndexingCompleted());

        $document->setIndexingStatus('completed');
        $this->assertTrue($document->isIndexingCompleted());

        $document->setIndexingStatus('error');
        $this->assertFalse($document->isIndexingCompleted());
    }

    public function testHasErrorShouldReturnCorrectValue(): void
    {
        $document = $this->createEntity();

        $document->setIndexingStatus('pending');
        $this->assertFalse($document->hasError());

        $document->setIndexingStatus('completed');
        $this->assertFalse($document->hasError());

        $document->setIndexingStatus('error');
        $this->assertTrue($document->hasError());
    }

    public function testSetIndexingStartedAtShouldUpdateValue(): void
    {
        $document = $this->createEntity();
        $startedAt = new \DateTimeImmutable('2024-01-01 10:00:00');

        $document->setIndexingStartedAt($startedAt);

        $this->assertEquals($startedAt, $document->getIndexingStartedAt());
    }

    public function testSetIndexingCompletedAtShouldUpdateValue(): void
    {
        $document = $this->createEntity();
        $completedAt = new \DateTimeImmutable('2024-01-01 10:30:00');

        $document->setIndexingCompletedAt($completedAt);

        $this->assertEquals($completedAt, $document->getIndexingCompletedAt());
    }

    public function testSetCreateTimeShouldUpdateValue(): void
    {
        $document = $this->createEntity();
        $createTime = new \DateTimeImmutable('2024-01-01 09:00:00');

        $document->setCreateTime($createTime);

        $this->assertEquals($createTime, $document->getCreateTime());
    }

    public function testToStringShouldReturnNameAndStatus(): void
    {
        $document = $this->createEntity();
        $document->setName('API文档');
        $document->setIndexingStatus('completed');

        $result = (string) $document;

        $this->assertEquals('API文档 (completed)', $result);
    }

    public function testDocumentShouldAcceptLongContent(): void
    {
        $document = $this->createEntity();
        $longContent = str_repeat('这是一个很长的文档内容。', 1000);

        $document->setContent($longContent);

        $this->assertEquals($longContent, $document->getContent());
    }

    public function testDocumentShouldAcceptComplexMetadata(): void
    {
        $document = $this->createEntity();
        $complexMetadata = [
            'document_info' => [
                'title' => 'API Documentation',
                'author' => 'Development Team',
                'version' => '2.1.0',
                'created_date' => '2024-01-01',
                'last_modified' => '2024-01-15',
            ],
            'processing_config' => [
                'chunk_size' => 1000,
                'overlap' => 200,
                'separator' => '\n\n',
            ],
            'statistics' => [
                'total_pages' => 45,
                'estimated_reading_time' => 25,
                'complexity_score' => 7.5,
            ],
            'tags' => ['api', 'reference', 'guide'],
            'categories' => ['technical', 'documentation'],
        ];

        $document->setMetadata($complexMetadata);

        $this->assertEquals($complexMetadata, $document->getMetadata());
    }
}
