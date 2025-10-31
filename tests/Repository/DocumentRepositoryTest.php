<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Entity\Document;
use Tourze\DifyClientBundle\Repository\DocumentRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(DocumentRepository::class)]
#[RunTestsInSeparateProcesses]
final class DocumentRepositoryTest extends AbstractRepositoryTestCase
{
    private DocumentRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(DocumentRepository::class);
    }

    protected function getRepository(): DocumentRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): Document
    {
        // 创建测试数据集
        $dataset = new Dataset();
        $dataset->setDatasetId('test-dataset-' . uniqid());
        $dataset->setName('Test Dataset');
        $dataset->setDataSourceType('upload_file');
        $dataset->setIndexingTechnique('high_quality');
        $dataset->setCreatedBy('test-user');
        $this->persistAndFlush($dataset);

        $document = new Document();
        $document->setDocumentId('test-doc-' . uniqid());
        $document->setDataset($dataset);
        $document->setName('Test Document');
        $document->setDataSource('upload_file');
        $document->setIndexingTechnique('high_quality');
        $document->setCreatedBy('test-user');

        return $document;
    }

    public function testFindByDocumentIdShouldReturnCorrectDocument(): void
    {
        // Arrange: 创建测试数据集
        $dataset = new Dataset();
        $dataset->setDatasetId('test-dataset-' . uniqid());
        $dataset->setName('Test Dataset');
        $dataset->setDataSourceType('upload_file');
        $dataset->setIndexingTechnique('high_quality');
        $dataset->setCreatedBy('test-user');
        $this->persistAndFlush($dataset);

        // 创建并持久化文档
        $documentId = 'test-document-id-' . uniqid();
        $document = new Document();
        $document->setDocumentId($documentId);
        $document->setDataset($dataset);
        $document->setName('Test Document');
        $document->setDataSource('upload_file');
        $document->setIndexingTechnique('high_quality');
        $document->setCreatedBy('user123');
        $this->persistAndFlush($document);

        // Act: 根据文档ID查找
        $foundDocument = $this->repository->findByDocumentId($documentId);

        // Assert: 验证找到正确的文档
        $this->assertNotNull($foundDocument);
        $this->assertSame($documentId, $foundDocument->getDocumentId());
        $this->assertSame('Test Document', $foundDocument->getName());
    }

    public function testFindByDocumentIdWithNonExistentIdShouldReturnNull(): void
    {
        // Act: 查找不存在的文档ID
        $foundDocument = $this->repository->findByDocumentId('non-existent-id');

        // Assert: 应该返回null
        $this->assertNull($foundDocument);
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新文档
        $document = $this->createNewEntity();

        // Act: 保存文档
        $this->repository->save($document);

        // Assert: 验证文档已持久化
        $this->assertNotNull($document->getId());
        $this->assertEntityPersisted($document);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化文档
        $document = $this->createNewEntity();
        $this->persistAndFlush($document);
        $documentId = $document->getId();

        // Act: 删除文档
        $this->repository->remove($document);

        // Assert: 验证文档已删除
        $this->assertEntityNotExists(Document::class, $documentId);
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryHasCorrectEntityClass(): void
    {
        $this->assertSame(Document::class, $this->repository->getClassName());
    }

    public function testGetEntityManagerShouldReturnEntityManagerInterface(): void
    {
        $em = self::getEntityManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $em);
    }

    public function testFindByDataSource(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByDataset(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindFailedDocuments(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFlush(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testSearch(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
