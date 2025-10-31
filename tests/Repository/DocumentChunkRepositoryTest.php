<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Entity\Document;
use Tourze\DifyClientBundle\Entity\DocumentChunk;
use Tourze\DifyClientBundle\Repository\DocumentChunkRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(DocumentChunkRepository::class)]
#[RunTestsInSeparateProcesses]
final class DocumentChunkRepositoryTest extends AbstractRepositoryTestCase
{
    private DocumentChunkRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(DocumentChunkRepository::class);
    }

    protected function getRepository(): DocumentChunkRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): DocumentChunk
    {
        // 创建测试数据集
        $dataset = new Dataset();
        $dataset->setDatasetId('test-dataset-' . uniqid());
        $dataset->setName('Test Dataset');
        $dataset->setDataSourceType('upload_file');
        $dataset->setIndexingTechnique('high_quality');
        $dataset->setCreatedBy('test-user');
        $this->persistAndFlush($dataset);

        // 创建测试文档
        $document = new Document();
        $document->setDocumentId('test-doc-' . uniqid());
        $document->setDataset($dataset);
        $document->setName('Test Document');
        $document->setDataSource('upload_file');
        $document->setIndexingTechnique('high_quality');
        $document->setCreatedBy('test-user');
        $this->persistAndFlush($document);

        $documentChunk = new DocumentChunk();
        $documentChunk->setSegmentId('test-chunk-' . uniqid());
        $documentChunk->setDocument($document);
        $documentChunk->setContent('Test chunk content');
        $documentChunk->setPosition(0);
        $documentChunk->setCharacterCount(100);
        $documentChunk->setWordCount(20);
        $documentChunk->setTokenCount(25);

        return $documentChunk;
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新文档块
        $documentChunk = $this->createNewEntity();

        // Act: 保存文档块
        $this->repository->save($documentChunk);

        // Assert: 验证文档块已持久化
        $this->assertNotNull($documentChunk->getId());
        $this->assertEntityPersisted($documentChunk);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化文档块
        $documentChunk = $this->createNewEntity();
        $this->persistAndFlush($documentChunk);
        $chunkId = $documentChunk->getId();

        // Act: 删除文档块
        $this->repository->remove($documentChunk);

        // Assert: 验证文档块已删除
        $this->assertEntityNotExists(DocumentChunk::class, $chunkId);
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryHasCorrectEntityClass(): void
    {
        $this->assertSame(DocumentChunk::class, $this->repository->getClassName());
    }

    public function testGetEntityManagerShouldReturnEntityManagerInterface(): void
    {
        $em = self::getEntityManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $em);
    }

    public function testFindByDocument(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindBySegmentId(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindEnabledChunks(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFlush(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
