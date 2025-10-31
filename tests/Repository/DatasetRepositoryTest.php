<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Repository\DatasetRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(DatasetRepository::class)]
#[RunTestsInSeparateProcesses]
final class DatasetRepositoryTest extends AbstractRepositoryTestCase
{
    private DatasetRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(DatasetRepository::class);
    }

    protected function getRepository(): DatasetRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): Dataset
    {
        $dataset = new Dataset();
        $dataset->setDatasetId('test-dataset-' . uniqid());
        $dataset->setName('Test Dataset');
        $dataset->setDataSourceType('upload_file');
        $dataset->setIndexingTechnique('high_quality');
        $dataset->setCreatedBy('test-user');

        return $dataset;
    }

    public function testFindByDatasetIdShouldReturnCorrectDataset(): void
    {
        // Arrange: 创建并持久化数据集
        $datasetId = 'test-dataset-id-' . uniqid();
        $dataset = new Dataset();
        $dataset->setDatasetId($datasetId);
        $dataset->setName('Knowledge Base');
        $dataset->setDataSourceType('upload_file');
        $dataset->setIndexingTechnique('high_quality');
        $dataset->setCreatedBy('user123');
        $this->persistAndFlush($dataset);

        // Act: 根据数据集ID查找
        $foundDataset = $this->repository->findByDatasetId($datasetId);

        // Assert: 验证找到正确的数据集
        $this->assertNotNull($foundDataset);
        $this->assertSame($datasetId, $foundDataset->getDatasetId());
        $this->assertSame('Knowledge Base', $foundDataset->getName());
    }

    public function testFindByDatasetIdWithNonExistentIdShouldReturnNull(): void
    {
        // Act: 查找不存在的数据集ID
        $foundDataset = $this->repository->findByDatasetId('non-existent-id');

        // Assert: 应该返回null
        $this->assertNull($foundDataset);
    }

    public function testFindByDataSourceTypeShouldReturnCorrectDatasets(): void
    {
        // Arrange: 清理现有数据并创建不同数据源类型的数据集
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM dify_dataset');
        self::getEntityManager()->clear();

        $uploadDataset1 = new Dataset();
        $uploadDataset1->setDatasetId('upload-1');
        $uploadDataset1->setName('Upload Dataset 1');
        $uploadDataset1->setDataSourceType('upload_file');
        $uploadDataset1->setIndexingTechnique('high_quality');
        $uploadDataset1->setCreatedBy('user1');

        $webDataset = new Dataset();
        $webDataset->setDatasetId('web-1');
        $webDataset->setName('Web Dataset');
        $webDataset->setDataSourceType('website_crawl');
        $webDataset->setIndexingTechnique('economy');
        $webDataset->setCreatedBy('user2');

        $this->persistAndFlush($uploadDataset1);
        $this->persistAndFlush($webDataset);

        // Act: 查找上传文件类型的数据集
        $uploadDatasets = $this->repository->findByDataSourceType('upload_file');

        // Assert: 只返回上传文件类型的数据集
        $this->assertCount(1, $uploadDatasets);
        $this->assertSame('upload-1', $uploadDatasets[0]->getDatasetId());
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新数据集（未持久化）
        $dataset = new Dataset();
        $dataset->setDatasetId('test-save-' . uniqid());
        $dataset->setName('Save Test Dataset');
        $dataset->setDataSourceType('upload_file');
        $dataset->setIndexingTechnique('high_quality');
        $dataset->setCreatedBy('test-user');

        // Act: 保存数据集
        $this->repository->save($dataset);

        // Assert: 验证数据集已持久化
        $this->assertNotNull($dataset->getId());
        $this->assertEntityPersisted($dataset);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化数据集
        $dataset = new Dataset();
        $dataset->setDatasetId('test-remove-' . uniqid());
        $dataset->setName('Remove Test Dataset');
        $dataset->setDataSourceType('upload_file');
        $dataset->setIndexingTechnique('high_quality');
        $dataset->setCreatedBy('test-user');
        $this->persistAndFlush($dataset);

        $datasetId = $dataset->getId();

        // Act: 删除数据集
        $this->repository->remove($dataset);

        // Assert: 验证数据集已删除
        $this->assertEntityNotExists(Dataset::class, $datasetId);
    }

    public function testGetEntityManagerShouldReturnEntityManagerInterface(): void
    {
        // Act: 获取实体管理器
        $em = self::getEntityManager();

        // Assert: 验证返回类型
        $this->assertInstanceOf(EntityManagerInterface::class, $em);
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        // Assert: 验证继承关系
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryHasCorrectEntityClass(): void
    {
        // Assert: 验证实体类
        $this->assertSame(Dataset::class, $this->repository->getClassName());
    }

    public function testFindByCreatedBy(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByIndexingTechnique(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByNameContaining(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByTag(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindMostUsed(): void
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
