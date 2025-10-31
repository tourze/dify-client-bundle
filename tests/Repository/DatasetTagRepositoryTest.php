<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\DatasetTag;
use Tourze\DifyClientBundle\Repository\DatasetTagRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(DatasetTagRepository::class)]
#[RunTestsInSeparateProcesses]
final class DatasetTagRepositoryTest extends AbstractRepositoryTestCase
{
    private DatasetTagRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(DatasetTagRepository::class);
    }

    protected function getRepository(): DatasetTagRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): DatasetTag
    {
        $datasetTag = new DatasetTag();
        $datasetTag->setTagId('test-tag-' . uniqid());
        $datasetTag->setName('Test Tag ' . uniqid());
        $datasetTag->setColor('#FF5733');
        $datasetTag->setCreatedBy('test-user');

        return $datasetTag;
    }

    public function testFindByTagIdShouldReturnCorrectTag(): void
    {
        // Arrange: 创建并持久化数据集标签
        $tagId = 'test-tag-id-' . uniqid();
        $datasetTag = new DatasetTag();
        $datasetTag->setTagId($tagId);
        $datasetTag->setName('AI Knowledge');
        $datasetTag->setColor('#00FF00');
        $datasetTag->setCreatedBy('admin');
        $this->persistAndFlush($datasetTag);

        // Act: 根据标签ID查找
        $foundTag = $this->repository->findByTagId($tagId);

        // Assert: 验证找到正确的标签
        $this->assertNotNull($foundTag);
        $this->assertSame($tagId, $foundTag->getTagId());
        $this->assertSame('AI Knowledge', $foundTag->getName());
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新数据集标签
        $datasetTag = new DatasetTag();
        $datasetTag->setTagId('test-save-' . uniqid());
        $datasetTag->setName('Save Test Tag');
        $datasetTag->setColor('#0000FF');
        $datasetTag->setCreatedBy('test-user');

        // Act: 保存标签
        $this->repository->save($datasetTag);

        // Assert: 验证标签已持久化
        $this->assertNotNull($datasetTag->getId());
        $this->assertEntityPersisted($datasetTag);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化标签
        $datasetTag = new DatasetTag();
        $datasetTag->setTagId('test-remove-' . uniqid());
        $datasetTag->setName('Remove Test Tag');
        $datasetTag->setColor('#FF0000');
        $datasetTag->setCreatedBy('test-user');
        $this->persistAndFlush($datasetTag);

        $tagId = $datasetTag->getId();

        // Act: 删除标签
        $this->repository->remove($datasetTag);

        // Assert: 验证标签已删除
        $this->assertEntityNotExists(DatasetTag::class, $tagId);
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryHasCorrectEntityClass(): void
    {
        $this->assertSame(DatasetTag::class, $this->repository->getClassName());
    }

    public function testFindActiveTagsOrderByUsage(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByName(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindUnusedTags(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFlush(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testIncrementUsageCount(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
