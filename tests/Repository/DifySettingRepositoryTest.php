<?php

namespace Tourze\DifyClientBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(DifySettingRepository::class)]
#[RunTestsInSeparateProcesses]
final class DifySettingRepositoryTest extends AbstractRepositoryTestCase
{
    private DifySettingRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(DifySettingRepository::class);
    }

    protected function getRepository(): DifySettingRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): DifySetting
    {
        $setting = new DifySetting();
        $setting->setName('Test Setting ' . uniqid());
        $setting->setApiKey('test-key-' . uniqid());
        $setting->setBaseUrl('https://test.dify.ai');
        $setting->setActive(false);

        return $setting;
    }

    public function testFindActiveSettingShouldReturnActiveConfiguration(): void
    {
        // Arrange: 清理所有现有配置
        $em = self::getEntityManager();
        $em->getConnection()->executeStatement('DELETE FROM dify_setting');
        $em->clear();

        // 创建激活和非激活的配置
        $inactiveSetting = new DifySetting();
        $inactiveSetting->setName('Inactive Config');
        $inactiveSetting->setApiKey('inactive-key');
        $inactiveSetting->setBaseUrl('https://inactive.dify.ai');
        $inactiveSetting->setActive(false);

        $activeSetting = new DifySetting();
        $activeSetting->setName('Active Config');
        $activeSetting->setApiKey('active-key');
        $activeSetting->setBaseUrl('https://active.dify.ai');
        $activeSetting->setActive(true);

        $this->persistAndFlush($inactiveSetting);
        $this->persistAndFlush($activeSetting);

        // Act: 查找激活的配置
        $result = $this->repository->findActiveSetting();

        // Assert: 返回激活的配置
        $this->assertNotNull($result);
        $this->assertEquals('Active Config', $result->getName());
        $this->assertTrue($result->isActive());
    }

    public function testFindActiveSettingWithNoActiveConfigurationShouldReturnNull(): void
    {
        // Arrange: 清理所有现有配置
        $em = self::getEntityManager();
        $em->getConnection()->executeStatement('DELETE FROM dify_setting');
        $em->clear();

        // 创建非激活的配置
        $inactiveSetting = new DifySetting();
        $inactiveSetting->setName('Inactive Config');
        $inactiveSetting->setApiKey('inactive-key');
        $inactiveSetting->setBaseUrl('https://inactive.dify.ai');
        $inactiveSetting->setActive(false);

        $this->persistAndFlush($inactiveSetting);

        // Act: 查找激活的配置
        $result = $this->repository->findActiveSetting();

        // Assert: 返回 null
        $this->assertNull($result);
    }

    public function testSaveShouldPersistEntity(): void
    {
        // Arrange: 创建新配置
        $setting = new DifySetting();
        $setting->setName('Test Config');
        $setting->setApiKey('test-key');
        $setting->setBaseUrl('https://test.dify.ai');
        $setting->setActive(true);

        // Act: 保存配置
        $this->repository->save($setting);

        // Assert: 验证配置已持久化
        $this->assertNotNull($setting->getId());
        $this->assertEntityPersisted($setting);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化配置
        $setting = new DifySetting();
        $setting->setName('To Remove');
        $setting->setApiKey('remove-key');
        $setting->setBaseUrl('https://remove.dify.ai');
        $setting->setActive(false);

        $this->persistAndFlush($setting);
        $settingId = $setting->getId();

        // Act: 删除配置
        $this->repository->remove($setting);

        // Assert: 验证配置已删除
        $this->assertEntityNotExists(DifySetting::class, $settingId);
    }

    public function testFlush(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
