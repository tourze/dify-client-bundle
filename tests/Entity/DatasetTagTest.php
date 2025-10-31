<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Entity\DatasetTag;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(DatasetTag::class)]
final class DatasetTagTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): DatasetTag
    {
        return new DatasetTag();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'tagId' => ['tagId', 'tag-12345'];
        yield 'name' => ['name', '技术文档'];
        yield 'description' => ['description', '用于技术相关的文档标签'];
        yield 'color' => ['color', '#FF5733'];
        yield 'createdBy' => ['createdBy', 'user-123'];
        yield 'usageCount' => ['usageCount', 5];
    }

    public function testCreateDatasetTagWithDefaultValuesShouldSucceed(): void
    {
        $tag = $this->createEntity();

        $this->assertNull($tag->getId());
        $this->assertNull($tag->getDescription());
        $this->assertNull($tag->getColor());
        $this->assertNull($tag->getCreatedBy());
        $this->assertEquals(0, $tag->getUsageCount());
        $this->assertNull($tag->getMetadata());
        $this->assertEmpty($tag->getDatasets());
    }

    public function testSetTagIdShouldUpdateValue(): void
    {
        $tag = $this->createEntity();
        $tagId = 'tag-12345';

        $tag->setTagId($tagId);

        $this->assertEquals($tagId, $tag->getTagId());
    }

    public function testSetNameShouldUpdateValue(): void
    {
        $tag = $this->createEntity();
        $name = '技术文档';

        $tag->setName($name);

        $this->assertEquals($name, $tag->getName());
    }

    public function testSetDescriptionShouldUpdateValue(): void
    {
        $tag = $this->createEntity();
        $description = '这是用于技术文档的标签';

        $tag->setDescription($description);

        $this->assertEquals($description, $tag->getDescription());
    }

    public function testSetDescriptionWithNullShouldAcceptNull(): void
    {
        $tag = $this->createEntity();
        $tag->setDescription('原始描述');

        $tag->setDescription(null);

        $this->assertNull($tag->getDescription());
    }

    public function testSetColorShouldUpdateValue(): void
    {
        $tag = $this->createEntity();
        $color = '#3B82F6';

        $tag->setColor($color);

        $this->assertEquals($color, $tag->getColor());
    }

    public function testSetColorWithNullShouldAcceptNull(): void
    {
        $tag = $this->createEntity();
        $tag->setColor('#FF5733');

        $tag->setColor(null);

        $this->assertNull($tag->getColor());
    }

    #[TestWith(['#FF0000'], 'red')]
    #[TestWith(['#00FF00'], 'green')]
    #[TestWith(['#0000FF'], 'blue')]
    #[TestWith(['#000000'], 'black')]
    #[TestWith(['#FFFFFF'], 'white')]
    #[TestWith(['#ff5733'], 'lowercase')]
    public function testSetColorWithValidValuesShouldSucceed(string $color): void
    {
        $tag = $this->createEntity();

        $tag->setColor($color);

        $this->assertEquals($color, $tag->getColor());
    }

    public function testSetCreatedByShouldUpdateValue(): void
    {
        $tag = $this->createEntity();
        $createdBy = 'user-456';

        $tag->setCreatedBy($createdBy);

        $this->assertEquals($createdBy, $tag->getCreatedBy());
    }

    public function testSetCreatedByWithNullShouldAcceptNull(): void
    {
        $tag = $this->createEntity();
        $tag->setCreatedBy('user-123');

        $tag->setCreatedBy(null);

        $this->assertNull($tag->getCreatedBy());
    }

    public function testSetUsageCountShouldUpdateValue(): void
    {
        $tag = $this->createEntity();
        $usageCount = 10;

        $tag->setUsageCount($usageCount);

        $this->assertEquals($usageCount, $tag->getUsageCount());
    }

    public function testSetMetadataShouldUpdateValue(): void
    {
        $tag = $this->createEntity();
        $metadata = [
            'priority' => 'high',
            'category' => 'technical',
            'keywords' => ['api', 'documentation'],
        ];

        $tag->setMetadata($metadata);

        $this->assertEquals($metadata, $tag->getMetadata());
    }

    public function testSetMetadataWithNullShouldAcceptNull(): void
    {
        $tag = $this->createEntity();
        $tag->setMetadata(['key' => 'value']);

        $tag->setMetadata(null);

        $this->assertNull($tag->getMetadata());
    }

    public function testAddDatasetShouldAddNewDataset(): void
    {
        $tag = $this->createEntity();
        $dataset = $this->createMock(Dataset::class);

        $result = $tag->addDataset($dataset);

        $this->assertTrue($tag->getDatasets()->contains($dataset));
    }

    public function testAddDatasetShouldNotAddDuplicateDataset(): void
    {
        $tag = $this->createEntity();
        $dataset = $this->createMock(Dataset::class);

        $tag->addDataset($dataset);
        $tag->addDataset($dataset);

        $this->assertEquals(1, $tag->getDatasets()->count());
    }

    public function testRemoveDatasetShouldRemoveExistingDataset(): void
    {
        $tag = $this->createEntity();
        $dataset = $this->createMock(Dataset::class);

        $tag->addDataset($dataset);
        $result = $tag->removeDataset($dataset);

        $this->assertFalse($tag->getDatasets()->contains($dataset));
    }

    public function testSetCreateTimeShouldUpdateValue(): void
    {
        $tag = $this->createEntity();
        $createTime = new \DateTimeImmutable('2024-01-01 10:00:00');

        $tag->setCreateTime($createTime);

        $this->assertEquals($createTime, $tag->getCreateTime());
    }

    public function testToStringShouldReturnName(): void
    {
        $tag = $this->createEntity();
        $tag->setName('技术标签');

        $result = (string) $tag;

        $this->assertEquals('技术标签', $result);
    }

    public function testDatasetTagShouldAcceptLongDescription(): void
    {
        $tag = $this->createEntity();
        $longDescription = str_repeat('这是一个很长的标签描述。', 100);

        $tag->setDescription($longDescription);

        $this->assertEquals($longDescription, $tag->getDescription());
    }

    public function testDatasetTagShouldAcceptComplexMetadata(): void
    {
        $tag = $this->createEntity();
        $complexMetadata = [
            'priority' => 'high',
            'category' => 'technical',
            'subcategory' => 'api',
            'keywords' => ['documentation', 'guide', 'tutorial'],
            'config' => [
                'auto_apply' => true,
                'visibility' => 'public',
            ],
            'stats' => [
                'usage_count' => 25,
                'last_used' => '2024-01-01T10:00:00Z',
            ],
        ];

        $tag->setMetadata($complexMetadata);

        $this->assertEquals($complexMetadata, $tag->getMetadata());
    }
}
