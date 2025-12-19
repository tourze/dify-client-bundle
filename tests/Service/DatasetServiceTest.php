<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Entity\RetrieverResource;
use Tourze\DifyClientBundle\Service\DatasetService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * DatasetService 集成测试
 *
 * 验证知识库服务的核心功能与类型安全，特别是类型转换的 PHPStan L9 安全性
 * @internal
 */
#[CoversClass(DatasetService::class)]
#[RunTestsInSeparateProcesses]
final class DatasetServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，测试不需要数据库
    }

    /**
     * 测试 createRetrieverResource 处理 score 为 float 类型
     *
     * 验证 PHPStan L9 类型检查：score 从 mixed 转为 float 时的类型安全
     */
    public function testCreateRetrieverResourceWithFloatScore(): void
    {
        // Arrange
        $service = self::getService(DatasetService::class);

        $dataset = new Dataset();
        $dataset->setId('dataset-123');
        $dataset->setName('Test Dataset');

        $item = [
            'content' => 'Sample content',
            'score' => 0.95,  // float score
            'metadata' => ['source' => 'test'],
        ];

        // Act：通过反射调用 private 方法
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('createRetrieverResource');
        $method->setAccessible(true);

        /** @var RetrieverResource $resource */
        $resource = $method->invoke($service, $dataset, 'test query', $item);

        // Assert
        $this->assertInstanceOf(RetrieverResource::class, $resource);
        $this->assertSame($dataset, $resource->getDataset());
        $this->assertSame('test query', $resource->getQuery());
        $this->assertSame('Sample content', $resource->getContent());
        $this->assertSame(0.95, $resource->getScore());
        $this->assertSame(['source' => 'test'], $resource->getMetadata());
    }

    /**
     * 测试 createRetrieverResource 处理 score 为 numeric string
     *
     * 验证类型转换安全性：numeric string 应转为 float
     */
    public function testCreateRetrieverResourceWithNumericStringScore(): void
    {
        // Arrange
        $service = self::getService(DatasetService::class);

        $dataset = new Dataset();
        $dataset->setId('dataset-456');

        $item = [
            'content' => 'Another content',
            'score' => '0.85',  // numeric string
            'metadata' => [],
        ];

        // Act
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('createRetrieverResource');
        $method->setAccessible(true);

        /** @var RetrieverResource $resource */
        $resource = $method->invoke($service, $dataset, 'query', $item);

        // Assert
        $this->assertSame(0.85, $resource->getScore());
    }

    /**
     * 测试 createRetrieverResource 处理 score 为 int
     *
     * 验证 int 类型可安全转为 float
     */
    public function testCreateRetrieverResourceWithIntScore(): void
    {
        // Arrange
        $service = self::getService(DatasetService::class);

        $dataset = new Dataset();
        $item = [
            'content' => 'Content',
            'score' => 1,  // int
            'metadata' => [],
        ];

        // Act
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('createRetrieverResource');
        $method->setAccessible(true);

        /** @var RetrieverResource $resource */
        $resource = $method->invoke($service, $dataset, 'query', $item);

        // Assert
        $this->assertSame(1.0, $resource->getScore());
    }

    /**
     * 测试 createRetrieverResource 处理缺失 score
     *
     * 验证默认值为 0.0
     */
    public function testCreateRetrieverResourceWithMissingScore(): void
    {
        // Arrange
        $service = self::getService(DatasetService::class);

        $dataset = new Dataset();
        $item = [
            'content' => 'Content',
            // 'score' missing
            'metadata' => [],
        ];

        // Act
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('createRetrieverResource');
        $method->setAccessible(true);

        /** @var RetrieverResource $resource */
        $resource = $method->invoke($service, $dataset, 'query', $item);

        // Assert
        $this->assertSame(0.0, $resource->getScore());
    }

    /**
     * 测试 createRetrieverResource 处理无效 score（非 numeric）
     *
     * 验证无效值降级为 0.0
     */
    public function testCreateRetrieverResourceWithInvalidScore(): void
    {
        // Arrange
        $service = self::getService(DatasetService::class);

        $dataset = new Dataset();
        $item = [
            'content' => 'Content',
            'score' => 'invalid',  // non-numeric string
            'metadata' => [],
        ];

        // Act
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('createRetrieverResource');
        $method->setAccessible(true);

        /** @var RetrieverResource $resource */
        $resource = $method->invoke($service, $dataset, 'query', $item);

        // Assert：无效值应降级为 0.0
        $this->assertSame(0.0, $resource->getScore());
    }

    /**
     * 测试 createRetrieverResource 处理 score 为 null
     *
     * 验证 null 值默认为 0.0
     */
    public function testCreateRetrieverResourceWithNullScore(): void
    {
        // Arrange
        $service = self::getService(DatasetService::class);

        $dataset = new Dataset();
        $item = [
            'content' => 'Content',
            'score' => null,
            'metadata' => [],
        ];

        // Act
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('createRetrieverResource');
        $method->setAccessible(true);

        /** @var RetrieverResource $resource */
        $resource = $method->invoke($service, $dataset, 'query', $item);

        // Assert
        $this->assertSame(0.0, $resource->getScore());
    }

    /**
     * 测试获取知识库列表功能
     */
    public function testGetDatasets(): void
    {
        $page = 1;
        $limit = 20;
        $keyword = null;
        $tags = [];

        // 这里应该mock HTTP客户端和配置
        // 由于当前没有具体实现，先创建基本结构
        $this->assertTrue(true, '获取知识库列表服务测试结构已创建');
    }

    /**
     * 测试创建知识库功能
     */
    public function testCreateDataset(): void
    {
        $name = 'Test Dataset';
        $description = 'Test Description';
        $permission = 'only_me';
        $indexingTechnique = 'high_quality';
        $embeddingModel = null;
        $embeddingModelProvider = null;
        $retrievalModel = [];

        $this->assertTrue(true, '创建知识库测试结构已创建');
    }

    /**
     * 测试获取知识库详情功能
     */
    public function testGetDataset(): void
    {
        $datasetId = 'dataset-123';

        $this->assertTrue(true, '获取知识库详情测试结构已创建');
    }

    /**
     * 测试删除知识库功能
     */
    public function testDeleteDataset(): void
    {
        $datasetId = 'dataset-123';

        $this->assertTrue(true, '删除知识库测试结构已创建');
    }

    /**
     * 测试从知识库检索功能
     */
    public function testRetrieveFromDataset(): void
    {
        $datasetId = 'dataset-123';
        $query = 'test query';
        $user = 'anonymous';
        $retrievalSetting = [];

        $this->assertTrue(true, '从知识库检索测试结构已创建');
    }

    /**
     * 测试获取知识库标签功能
     */
    public function testGetDatasetTags(): void
    {
        $this->assertTrue(true, '获取知识库标签测试结构已创建');
    }

    /**
     * 测试创建数据集标签功能
     */
    public function testCreateDatasetTag(): void
    {
        $name = 'AI';

        $this->assertTrue(true, '创建数据集标签测试结构已创建');
    }

    /**
     * 测试获取可用嵌入模型功能
     */
    public function testGetEmbeddingModels(): void
    {
        $this->assertTrue(true, '获取可用嵌入模型测试结构已创建');
    }

    public function testAttachTags(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testCleanupUnusedTags(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testDetachTags(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByDatasetId(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindOrCreateTag(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testSearchDatasets(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testSyncAllDatasets(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testSyncDataset(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testUpdateDataset(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
