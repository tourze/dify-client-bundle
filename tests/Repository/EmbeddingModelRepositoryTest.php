<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\EmbeddingModel;
use Tourze\DifyClientBundle\Repository\EmbeddingModelRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(EmbeddingModelRepository::class)]
#[RunTestsInSeparateProcesses]
final class EmbeddingModelRepositoryTest extends AbstractRepositoryTestCase
{
    private EmbeddingModelRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(EmbeddingModelRepository::class);
    }

    protected function getRepository(): EmbeddingModelRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): EmbeddingModel
    {
        $embeddingModel = new EmbeddingModel();
        $embeddingModel->setModelName('test-model-' . uniqid());
        $embeddingModel->setProvider('openai');
        $embeddingModel->setDisplayName('Test Model');
        $embeddingModel->setDimensions(1536);
        $embeddingModel->setMaxTokens(8192);
        $embeddingModel->setAvailable(true);

        return $embeddingModel;
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新嵌入模型
        $embeddingModel = $this->createNewEntity();

        // Act: 保存模型
        $this->repository->save($embeddingModel);

        // Assert: 验证模型已持久化
        $this->assertNotNull($embeddingModel->getId());
        $this->assertEntityPersisted($embeddingModel);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化模型
        $embeddingModel = $this->createNewEntity();
        $this->persistAndFlush($embeddingModel);
        $modelId = $embeddingModel->getId();

        // Act: 删除模型
        $this->repository->remove($embeddingModel);

        // Assert: 验证模型已删除
        $this->assertEntityNotExists(EmbeddingModel::class, $modelId);
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryHasCorrectEntityClass(): void
    {
        $this->assertSame(EmbeddingModel::class, $this->repository->getClassName());
    }

    public function testGetEntityManagerShouldReturnEntityManagerInterface(): void
    {
        $em = self::getEntityManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $em);
    }

    public function testFindAvailableModels(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByModelName(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByProvider(): void
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
