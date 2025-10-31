<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\AppInfo;
use Tourze\DifyClientBundle\Repository\AppInfoRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AppInfoRepository::class)]
#[RunTestsInSeparateProcesses]
final class AppInfoRepositoryTest extends AbstractRepositoryTestCase
{
    private AppInfoRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(AppInfoRepository::class);
    }

    protected function getRepository(): AppInfoRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): AppInfo
    {
        $appInfo = new AppInfo();
        $appInfo->setAppId('test-app-' . uniqid());
        $appInfo->setName('Test App');
        $appInfo->setMode('chat');
        $appInfo->setEnableApi(true);

        return $appInfo;
    }

    public function testFindByAppIdShouldReturnCorrectApp(): void
    {
        // Arrange: 创建并持久化应用信息
        $appId = 'test-find-by-app-id-' . uniqid();
        $appInfo = new AppInfo();
        $appInfo->setAppId($appId);
        $appInfo->setName('Dify Chat App');
        $appInfo->setMode('chat');
        $appInfo->setDescription('A chatbot application');
        $appInfo->setEnableApi(true);
        $this->persistAndFlush($appInfo);

        // Act: 根据应用ID查找
        $foundApp = $this->repository->findByAppId($appId);

        // Assert: 验证找到正确的应用
        $this->assertNotNull($foundApp);
        $this->assertSame($appId, $foundApp->getAppId());
        $this->assertSame('Dify Chat App', $foundApp->getName());
        $this->assertSame('chat', $foundApp->getMode());
        $this->assertSame('A chatbot application', $foundApp->getDescription());
        $this->assertTrue($foundApp->isEnableApi());
    }

    public function testFindByAppIdWithNonExistentIdShouldReturnNull(): void
    {
        // Act: 查找不存在的应用ID
        $foundApp = $this->repository->findByAppId('non-existent-app-id');

        // Assert: 应该返回null
        $this->assertNull($foundApp);
    }

    public function testFindByModeShouldReturnAppsWithSpecificMode(): void
    {
        // Arrange: 清理现有数据并创建不同模式的应用
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM dify_app_info');
        self::getEntityManager()->clear();

        $chatApp1 = new AppInfo();
        $chatApp1->setAppId('chat-app-1');
        $chatApp1->setName('Chat App 1');
        $chatApp1->setMode('chat');
        $chatApp1->setEnableApi(true);

        $chatApp2 = new AppInfo();
        $chatApp2->setAppId('chat-app-2');
        $chatApp2->setName('Chat App 2');
        $chatApp2->setMode('chat');
        $chatApp2->setEnableApi(false);

        $completionApp = new AppInfo();
        $completionApp->setAppId('completion-app-1');
        $completionApp->setName('Completion App');
        $completionApp->setMode('completion');
        $completionApp->setEnableApi(true);

        $workflowApp = new AppInfo();
        $workflowApp->setAppId('workflow-app-1');
        $workflowApp->setName('Workflow App');
        $workflowApp->setMode('workflow');
        $workflowApp->setEnableApi(true);

        $this->persistAndFlush($chatApp1);
        $this->persistAndFlush($chatApp2);
        $this->persistAndFlush($completionApp);
        $this->persistAndFlush($workflowApp);

        // Act: 查找聊天模式的应用
        $chatApps = $this->repository->findByMode('chat');

        // Assert: 只返回聊天模式的应用
        $this->assertCount(2, $chatApps);

        $appIds = array_map(fn ($app) => $app->getAppId(), $chatApps);
        $this->assertContains('chat-app-1', $appIds);
        $this->assertContains('chat-app-2', $appIds);
        $this->assertNotContains('completion-app-1', $appIds);
        $this->assertNotContains('workflow-app-1', $appIds);
    }

    public function testFindByModeWithNonExistentModeShouldReturnEmptyArray(): void
    {
        // Act: 查找不存在的模式
        $apps = $this->repository->findByMode('non-existent-mode');

        // Assert: 返回空数组
        $this->assertEmpty($apps);
    }

    public function testFindEnabledAppsShouldReturnOnlyEnabledApps(): void
    {
        // Arrange: 清理现有数据并创建不同API状态的应用
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM dify_app_info');
        self::getEntityManager()->clear();

        $enabledApp1 = new AppInfo();
        $enabledApp1->setAppId('enabled-app-1');
        $enabledApp1->setName('Enabled App 1');
        $enabledApp1->setMode('chat');
        $enabledApp1->setEnableApi(true);

        $enabledApp2 = new AppInfo();
        $enabledApp2->setAppId('enabled-app-2');
        $enabledApp2->setName('Enabled App 2');
        $enabledApp2->setMode('completion');
        $enabledApp2->setEnableApi(true);

        $disabledApp = new AppInfo();
        $disabledApp->setAppId('disabled-app-1');
        $disabledApp->setName('Disabled App');
        $disabledApp->setMode('chat');
        $disabledApp->setEnableApi(false);

        $this->persistAndFlush($enabledApp1);
        $this->persistAndFlush($enabledApp2);
        $this->persistAndFlush($disabledApp);

        // Act: 查找启用API的应用
        $enabledApps = $this->repository->findEnabledApps();

        // Assert: 只返回启用API的应用
        $this->assertCount(2, $enabledApps);

        $appIds = array_map(fn ($app) => $app->getAppId(), $enabledApps);
        $this->assertContains('enabled-app-1', $appIds);
        $this->assertContains('enabled-app-2', $appIds);
        $this->assertNotContains('disabled-app-1', $appIds);
    }

    public function testFindEnabledAppsWithNoEnabledAppsShouldReturnEmptyArray(): void
    {
        // Arrange: 清理数据并创建禁用API的应用
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM dify_app_info');
        self::getEntityManager()->clear();

        $disabledApp = new AppInfo();
        $disabledApp->setAppId('disabled-only');
        $disabledApp->setName('Disabled App');
        $disabledApp->setMode('chat');
        $disabledApp->setEnableApi(false);
        $this->persistAndFlush($disabledApp);

        // Act: 查找启用API的应用
        $enabledApps = $this->repository->findEnabledApps();

        // Assert: 返回空数组
        $this->assertEmpty($enabledApps);
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新应用信息（未持久化）
        $appInfo = new AppInfo();
        $appInfo->setAppId('test-save-' . uniqid());
        $appInfo->setName('Save Test App');
        $appInfo->setMode('completion');
        $appInfo->setDescription('Test app for save functionality');
        $appInfo->setEnableApi(true);

        // Act: 保存应用信息
        $this->repository->save($appInfo);

        // Assert: 验证应用信息已持久化
        $this->assertNotNull($appInfo->getId());
        $this->assertEntityPersisted($appInfo);
    }

    public function testSaveShouldUpdateExistingEntity(): void
    {
        // Arrange: 创建并持久化应用信息
        $appInfo = new AppInfo();
        $appInfo->setAppId('test-update-save-' . uniqid());
        $appInfo->setName('Original App Name');
        $appInfo->setMode('chat');
        $appInfo->setEnableApi(false);
        $this->persistAndFlush($appInfo);

        // Act: 修改并保存
        $appInfo->setName('Updated App Name');
        $appInfo->setEnableApi(true);
        $this->repository->save($appInfo);

        // Assert: 验证更新已持久化
        self::getEntityManager()->refresh($appInfo);
        $this->assertSame('Updated App Name', $appInfo->getName());
        $this->assertTrue($appInfo->isEnableApi());
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化应用信息
        $appInfo = new AppInfo();
        $appInfo->setAppId('test-remove-' . uniqid());
        $appInfo->setName('Remove Test App');
        $appInfo->setMode('workflow');
        $appInfo->setEnableApi(true);
        $this->persistAndFlush($appInfo);

        $appInfoId = $appInfo->getId();

        // Act: 删除应用信息
        $this->repository->remove($appInfo);

        // Assert: 验证应用信息已删除
        $this->assertEntityNotExists(AppInfo::class, $appInfoId);
    }

    public function testRemoveWithoutFlushShouldNotDeleteImmediately(): void
    {
        // Arrange: 创建并持久化应用信息
        $appInfo = new AppInfo();
        $appInfo->setAppId('test-remove-no-flush-' . uniqid());
        $appInfo->setName('Remove No Flush Test');
        $appInfo->setMode('chat');
        $appInfo->setEnableApi(true);
        $this->persistAndFlush($appInfo);

        $appInfoId = $appInfo->getId();

        // Act: 删除应用信息但不刷新
        $this->repository->remove($appInfo, false);

        // Assert: 验证应用信息仍然存在（在数据库中）
        $em = self::getEntityManager();
        $qb = $this->repository->createQueryBuilder('a');
        $qb->select('COUNT(a.id)')
            ->where('a.id = :id')
            ->setParameter('id', $appInfoId)
        ;

        $count = (int) $qb->getQuery()->getSingleScalarResult();
        $this->assertSame(1, $count, '删除未flush时，实体应该仍在数据库中');

        // 手动刷新后应该被删除
        $em->flush();

        $count = (int) $qb->getQuery()->getSingleScalarResult();
        $this->assertSame(0, $count, 'flush后，实体应该被删除');
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
        $this->assertSame(AppInfo::class, $this->repository->getClassName());
    }

    public function testFindByModeWithMultipleModesShouldReturnCorrectApps(): void
    {
        // Arrange: 创建多种模式的应用
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM dify_app_info');
        self::getEntityManager()->clear();

        $completionApp1 = new AppInfo();
        $completionApp1->setAppId('completion-1');
        $completionApp1->setName('Completion App 1');
        $completionApp1->setMode('completion');
        $completionApp1->setEnableApi(true);

        $completionApp2 = new AppInfo();
        $completionApp2->setAppId('completion-2');
        $completionApp2->setName('Completion App 2');
        $completionApp2->setMode('completion');
        $completionApp2->setEnableApi(false);

        $this->persistAndFlush($completionApp1);
        $this->persistAndFlush($completionApp2);

        // Act: 查找completion模式的应用
        $completionApps = $this->repository->findByMode('completion');

        // Assert: 返回所有completion模式的应用
        $this->assertCount(2, $completionApps);

        foreach ($completionApps as $app) {
            $this->assertSame('completion', $app->getMode());
        }
    }

    public function testFlush(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
