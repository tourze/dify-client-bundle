<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Service\AdminMenu;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private LinkGeneratorInterface&MockObject $linkGenerator;

    protected function onSetUp(): void
    {
        $this->linkGenerator = $this->createMock(LinkGeneratorInterface::class);
    }

    protected function onTearDown(): void
    {
    }

    public function testServiceCanBeInstantiated(): void
    {
        // Arrange: 注入Mock到容器
        self::getContainer()->set(LinkGeneratorInterface::class, $this->linkGenerator);

        // Act: 从容器获取服务实例
        $adminMenu = self::getService(AdminMenu::class);

        // Assert
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);
    }

    public function testInvokeCreatesMenuStructure(): void
    {
        // Arrange: 准备Mock对象
        $rootItem = $this->createMock(ItemInterface::class);
        $difyMenu = $this->createMock(ItemInterface::class);

        // Mock URL生成器返回值
        $this->linkGenerator
            ->method('getCurdListPage')
            ->with(DifySetting::class)
            ->willReturn('/admin/dify-setting')
        ;

        // Mock菜单项添加
        $rootItem
            ->method('getChild')
            ->with('Dify AI管理')
            ->willReturn(null)
        ;

        $rootItem
            ->expects($this->once())
            ->method('addChild')
            ->with('Dify AI管理')
            ->willReturn($difyMenu)
        ;

        // Mock各个子菜单项的添加
        $difyMenu
            ->expects($this->exactly(1))
            ->method('addChild')
            ->willReturnCallback(function (string $name): ItemInterface {
                return $this->createMock(ItemInterface::class);
            })
        ;

        // Arrange: 注入Mock到容器
        self::getContainer()->set(LinkGeneratorInterface::class, $this->linkGenerator);

        // Act: 调用菜单构建方法
        $adminMenu = self::getService(AdminMenu::class);
        ($adminMenu)($rootItem);

        // Assert: 验证调用次数已在expect中验证
        $this->assertTrue(true); // 测试通过表示所有期望都满足
    }

    public function testInvokeUsesExistingDifyMenuWhenPresent(): void
    {
        // Arrange: 准备Mock对象，模拟已存在Dify菜单的情况
        $rootItem = $this->createMock(ItemInterface::class);
        $existingDifyMenu = $this->createMock(ItemInterface::class);

        // Mock URL生成器
        $this->linkGenerator
            ->method('getCurdListPage')
            ->with(DifySetting::class)
            ->willReturn('/admin/dify-setting')
        ;

        // Mock已存在的菜单
        $rootItem
            ->method('getChild')
            ->with('Dify AI管理')
            ->willReturn($existingDifyMenu)
        ;

        // 不应该调用addChild来创建新菜单
        $rootItem
            ->expects($this->never())
            ->method('addChild')
            ->with('Dify AI管理')
        ;

        // 应该在现有菜单上添加子项
        $existingDifyMenu
            ->expects($this->exactly(1))
            ->method('addChild')
            ->willReturnCallback(function (string $name): ItemInterface {
                return $this->createMock(ItemInterface::class);
            })
        ;

        // Arrange: 注入Mock到容器
        self::getContainer()->set(LinkGeneratorInterface::class, $this->linkGenerator);

        // Act: 调用菜单构建方法
        $adminMenu = self::getService(AdminMenu::class);
        ($adminMenu)($rootItem);

        // Assert: 验证调用次数已在expect中验证
        $this->assertTrue(true);
    }

    public function testMenuItemsHaveCorrectEntityClasses(): void
    {
        // Arrange: 验证实体类存在
        $expectedEntityClasses = [
            DifySetting::class,
        ];

        // Assert: 验证所有实体类都可实例化
        foreach ($expectedEntityClasses as $entityClass) {
            $instance = new $entityClass();
            $this->assertInstanceOf($entityClass, $instance);
        }
    }

    public function testAdminMenuImplementsCorrectInterface(): void
    {
        // Assert: 验证AdminMenu实现了正确的接口
        $interfaces = class_implements(AdminMenu::class);
        $this->assertContains('Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface', $interfaces);
    }
}
