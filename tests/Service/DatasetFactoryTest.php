<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Service;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Service\DatasetFactory;

/**
 * DatasetFactory 测试类
 *
 * 测试数据集工厂的核心功能
 * @internal
 */
#[CoversClass(DatasetFactory::class)]
class DatasetFactoryTest extends TestCase
{
    private DatasetFactory $datasetFactory;

    private ClockInterface&MockObject $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clock = $this->createMock(ClockInterface::class);
        $this->datasetFactory = new DatasetFactory($this->clock);
    }

    /**
     * 测试创建数据集
     */
    public function testCreateDataset(): void
    {
        $name = 'Test Dataset';
        $description = 'Test Description';
        $indexingTechnique = 'high_quality';
        $now = new \DateTimeImmutable();

        $this->clock->expects($this->once())
            ->method('now')
            ->willReturn($now)
        ;

        $dataset = $this->datasetFactory->create($name, $description, $indexingTechnique);

        $this->assertInstanceOf(Dataset::class, $dataset);
        $this->assertEquals($name, $dataset->getName());
        $this->assertEquals($description, $dataset->getDescription());
        $this->assertEquals($indexingTechnique, $dataset->getIndexingTechnique());
        $this->assertEquals(0, $dataset->getDocumentCount());
        $this->assertEquals(0, $dataset->getWordCount());
        $this->assertEquals($now, $dataset->getCreateTime());
    }

    /**
     * 测试创建数据集（无描述）
     */
    public function testCreateDatasetWithoutDescription(): void
    {
        $name = 'Test Dataset';
        $indexingTechnique = 'economy';
        $now = new \DateTimeImmutable();

        $this->clock->expects($this->once())
            ->method('now')
            ->willReturn($now)
        ;

        $dataset = $this->datasetFactory->create($name, null, $indexingTechnique);

        $this->assertInstanceOf(Dataset::class, $dataset);
        $this->assertEquals($name, $dataset->getName());
        $this->assertNull($dataset->getDescription());
        $this->assertEquals($indexingTechnique, $dataset->getIndexingTechnique());
        $this->assertEquals(0, $dataset->getDocumentCount());
        $this->assertEquals(0, $dataset->getWordCount());
        $this->assertEquals($now, $dataset->getCreateTime());
    }
}
