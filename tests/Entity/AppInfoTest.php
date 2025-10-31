<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\DifyClientBundle\Entity\AppInfo;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AppInfo::class)]
final class AppInfoTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): AppInfo
    {
        return new AppInfo();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'appId' => ['appId', 'app-12345'];
        yield 'name' => ['name', '测试应用'];
        yield 'mode' => ['mode', 'chat'];
        yield 'description' => ['description', '这是一个测试应用描述'];
        yield 'iconBackground' => ['iconBackground', '#FF5733'];
        yield 'enableSite' => ['enableSite', false];
        yield 'enableApi' => ['enableApi', false];
    }

    public function testCreateAppInfoWithDefaultValuesShouldSucceed(): void
    {
        $appInfo = $this->createEntity();

        $this->assertNull($appInfo->getId());
        $this->assertNull($appInfo->getDescription());
        $this->assertNull($appInfo->getIcon());
        $this->assertNull($appInfo->getIconBackground());
        $this->assertTrue($appInfo->isEnableSite());
        $this->assertTrue($appInfo->isEnableApi());
        $this->assertNull($appInfo->getMetadata());
    }

    public function testSetAppIdShouldUpdateValue(): void
    {
        $appInfo = $this->createEntity();
        $appId = 'app-12345';

        $appInfo->setAppId($appId);

        $this->assertEquals($appId, $appInfo->getAppId());
    }

    public function testSetNameShouldUpdateValue(): void
    {
        $appInfo = $this->createEntity();
        $name = 'AI助手应用';

        $appInfo->setName($name);

        $this->assertEquals($name, $appInfo->getName());
    }

    public function testSetModeShouldUpdateValue(): void
    {
        $appInfo = $this->createEntity();
        $mode = 'agent';

        $appInfo->setMode($mode);

        $this->assertEquals($mode, $appInfo->getMode());
    }

    #[TestWith(['chat'], 'chat mode')]
    #[TestWith(['agent'], 'agent mode')]
    #[TestWith(['workflow'], 'workflow mode')]
    #[TestWith(['completion'], 'completion mode')]
    public function testSetModeWithValidValuesShouldSucceed(string $mode): void
    {
        $appInfo = $this->createEntity();

        $appInfo->setMode($mode);

        $this->assertEquals($mode, $appInfo->getMode());
    }

    public function testSetDescriptionShouldUpdateValue(): void
    {
        $appInfo = $this->createEntity();
        $description = '这是一个智能AI助手应用';

        $appInfo->setDescription($description);

        $this->assertEquals($description, $appInfo->getDescription());
    }

    public function testSetDescriptionWithNullShouldAcceptNull(): void
    {
        $appInfo = $this->createEntity();
        $appInfo->setDescription('原始描述');

        $appInfo->setDescription(null);

        $this->assertNull($appInfo->getDescription());
    }

    public function testSetIconShouldUpdateValue(): void
    {
        $appInfo = $this->createEntity();
        $icon = ['type' => 'emoji', 'value' => '🤖'];

        $appInfo->setIcon($icon);

        $this->assertEquals($icon, $appInfo->getIcon());
    }

    public function testSetIconWithNullShouldAcceptNull(): void
    {
        $appInfo = $this->createEntity();
        $appInfo->setIcon(['type' => 'emoji', 'value' => '🤖']);

        $appInfo->setIcon(null);

        $this->assertNull($appInfo->getIcon());
    }

    public function testSetIconBackgroundShouldUpdateValue(): void
    {
        $appInfo = $this->createEntity();
        $iconBackground = '#3B82F6';

        $appInfo->setIconBackground($iconBackground);

        $this->assertEquals($iconBackground, $appInfo->getIconBackground());
    }

    public function testSetIconBackgroundWithNullShouldAcceptNull(): void
    {
        $appInfo = $this->createEntity();
        $appInfo->setIconBackground('#FF5733');

        $appInfo->setIconBackground(null);

        $this->assertNull($appInfo->getIconBackground());
    }

    public function testSetEnableSiteShouldUpdateValue(): void
    {
        $appInfo = $this->createEntity();

        $appInfo->setEnableSite(false);

        $this->assertFalse($appInfo->isEnableSite());

        $appInfo->setEnableSite(true);
        $this->assertTrue($appInfo->isEnableSite());
    }

    public function testSetEnableApiShouldUpdateValue(): void
    {
        $appInfo = $this->createEntity();

        $appInfo->setEnableApi(false);

        $this->assertFalse($appInfo->isEnableApi());

        $appInfo->setEnableApi(true);
        $this->assertTrue($appInfo->isEnableApi());
    }

    public function testSetMetadataShouldUpdateValue(): void
    {
        $appInfo = $this->createEntity();
        $metadata = [
            'version' => '1.0.0',
            'category' => 'assistant',
            'tags' => ['AI', '助手'],
        ];

        $appInfo->setMetadata($metadata);

        $this->assertEquals($metadata, $appInfo->getMetadata());
    }

    public function testSetMetadataWithNullShouldAcceptNull(): void
    {
        $appInfo = $this->createEntity();
        $appInfo->setMetadata(['key' => 'value']);

        $appInfo->setMetadata(null);

        $this->assertNull($appInfo->getMetadata());
    }

    public function testSetCreateTimeShouldUpdateValue(): void
    {
        $appInfo = $this->createEntity();
        $createTime = new \DateTimeImmutable('2024-01-01 10:00:00');

        $appInfo->setCreateTime($createTime);

        $this->assertEquals($createTime, $appInfo->getCreateTime());
    }

    public function testToStringShouldReturnNameAndMode(): void
    {
        $appInfo = $this->createEntity();
        $appInfo->setName('AI助手');
        $appInfo->setMode('chat');

        $result = (string) $appInfo;

        $this->assertEquals('AI助手 (chat)', $result);
    }

    public function testAppInfoShouldAcceptLongDescription(): void
    {
        $appInfo = $this->createEntity();
        $longDescription = str_repeat('这是一个很长的应用描述。', 100);

        $appInfo->setDescription($longDescription);

        $this->assertEquals($longDescription, $appInfo->getDescription());
    }

    public function testAppInfoShouldAcceptComplexIcon(): void
    {
        $appInfo = $this->createEntity();
        $complexIcon = [
            'type' => 'custom',
            'url' => 'https://example.com/icon.png',
            'size' => 64,
            'format' => 'png',
        ];

        $appInfo->setIcon($complexIcon);

        $this->assertEquals($complexIcon, $appInfo->getIcon());
    }

    public function testAppInfoShouldAcceptComplexMetadata(): void
    {
        $appInfo = $this->createEntity();
        $complexMetadata = [
            'version' => '2.1.0',
            'author' => 'Test Author',
            'license' => 'MIT',
            'dependencies' => [
                'dify' => '>=1.0.0',
                'php' => '>=8.1',
            ],
            'config' => [
                'max_tokens' => 4000,
                'temperature' => 0.7,
            ],
            'features' => ['chat', 'rag', 'workflow'],
        ];

        $appInfo->setMetadata($complexMetadata);

        $this->assertEquals($complexMetadata, $appInfo->getMetadata());
    }
}
