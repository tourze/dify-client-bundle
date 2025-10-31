<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\RetrieverResource;
use Tourze\DifyClientBundle\Repository\RetrieverResourceRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(RetrieverResourceRepository::class)]
#[RunTestsInSeparateProcesses]
final class RetrieverResourceRepositoryTest extends AbstractRepositoryTestCase
{
    private RetrieverResourceRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(RetrieverResourceRepository::class);
    }

    protected function getRepository(): RetrieverResourceRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): RetrieverResource
    {
        $retrieverResource = new RetrieverResource();
        $retrieverResource->setResourceId('test-resource-' . uniqid());
        $retrieverResource->setResourceType('dataset');
        $retrieverResource->setScore(0.85);
        $retrieverResource->setContent('Test content for retrieval');
        $retrieverResource->setPosition(1);
        $retrieverResource->setDatasetId('test-dataset-' . uniqid());
        $retrieverResource->setDatasetName('Test Dataset');
        $retrieverResource->setDocumentId('test-doc-' . uniqid());
        $retrieverResource->setDocumentName('Test Document');
        $retrieverResource->setSegmentId('test-segment-' . uniqid());

        return $retrieverResource;
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新检索资源
        $retrieverResource = $this->createNewEntity();

        // Act: 保存资源
        $this->repository->save($retrieverResource);

        // Assert: 验证资源已持久化
        $this->assertNotNull($retrieverResource->getId());
        $this->assertEntityPersisted($retrieverResource);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化资源
        $retrieverResource = $this->createNewEntity();
        $this->persistAndFlush($retrieverResource);
        $resourceId = $retrieverResource->getId();

        // Act: 删除资源
        $this->repository->remove($retrieverResource);

        // Assert: 验证资源已删除
        $this->assertEntityNotExists(RetrieverResource::class, $resourceId);
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryHasCorrectEntityClass(): void
    {
        $this->assertSame(RetrieverResource::class, $this->repository->getClassName());
    }

    public function testGetEntityManagerShouldReturnEntityManagerInterface(): void
    {
        $em = self::getEntityManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $em);
    }

    public function testFindByDatasetId(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByMessage(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindTopRelevantResources(): void
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
