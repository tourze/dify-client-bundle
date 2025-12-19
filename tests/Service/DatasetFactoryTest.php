<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Clock\ClockInterface;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Service\DatasetFactory;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * DatasetFactory 测试类
 *
 * 测试数据集工厂的核心功能
 * @internal
 */
#[CoversClass(DatasetFactory::class)]
#[RunTestsInSeparateProcesses]
final class DatasetFactoryTest extends AbstractIntegrationTestCase
{
    private DatasetFactory $datasetFactory;

    protected function onSetUp(): void
    {
        // 从容器获取服务实例
        $this->datasetFactory = self::getService(DatasetFactory::class);
    }

    /**
     * 测试创建数据集
     */
    public function testCreateDataset(): void
    {
        $name = 'Test Dataset';
        $description = 'Test Description';
        $indexingTechnique = 'high_quality';

        $dataset = $this->datasetFactory->create($name, $description, $indexingTechnique);

        $this->assertInstanceOf(Dataset::class, $dataset);
        $this->assertEquals($name, $dataset->getName());
        $this->assertEquals($description, $dataset->getDescription());
        $this->assertEquals($indexingTechnique, $dataset->getIndexingTechnique());
        $this->assertEquals(0, $dataset->getDocumentCount());
        $this->assertEquals(0, $dataset->getWordCount());
        $this->assertNotNull($dataset->getCreateTime());
    }

    /**
     * 测试创建数据集（无描述）
     */
    public function testCreateDatasetWithoutDescription(): void
    {
        $name = 'Test Dataset';
        $indexingTechnique = 'economy';

        $dataset = $this->datasetFactory->create($name, null, $indexingTechnique);

        $this->assertInstanceOf(Dataset::class, $dataset);
        $this->assertEquals($name, $dataset->getName());
        $this->assertNull($dataset->getDescription());
        $this->assertEquals($indexingTechnique, $dataset->getIndexingTechnique());
        $this->assertEquals(0, $dataset->getDocumentCount());
        $this->assertEquals(0, $dataset->getWordCount());
        $this->assertNotNull($dataset->getCreateTime());
    }
}
