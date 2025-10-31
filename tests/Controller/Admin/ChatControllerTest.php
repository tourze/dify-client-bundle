<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Tourze\DifyClientBundle\Controller\Admin\ChatController;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * ChatController测试
 * @internal
 */
#[CoversClass(ChatController::class)]
#[RunTestsInSeparateProcesses]
final class ChatControllerTest extends AbstractWebTestCase
{
    /**
     * 测试控制器能够正确实例化
     */
    public function testControllerCanBeInstantiated(): void
    {
        $repository = self::getService(DifySettingRepository::class);
        $this->assertInstanceOf(DifySettingRepository::class, $repository);
        $controller = new ChatController($repository);

        $this->assertInstanceOf(ChatController::class, $controller);
    }

    /**
     * 测试聊天页面需要settingId参数
     */
    public function testChatPageRequiresSettingIdParameter(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 期望抛出NotFoundHttpException异常
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('缺少必需的参数 settingId');

        // 访问没有settingId参数的聊天页面
        $client->request('GET', '/admin/dify/chat');
    }

    /**
     * 测试聊天页面需要有效的settingId
     */
    public function testChatPageRequiresValidSettingId(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 期望抛出NotFoundHttpException异常
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Dify 配置 ID 999999 未找到');

        // 访问带有无效settingId的聊天页面
        $client->request('GET', '/admin/dify/chat', ['settingId' => '999999']);
    }

    /**
     * 测试控制器能够正确处理依赖注入
     */
    public function testControllerDependencyInjection(): void
    {
        $repository = self::getService(DifySettingRepository::class);
        $this->assertInstanceOf(DifySettingRepository::class, $repository);

        $controller = new ChatController($repository);
        $this->assertInstanceOf(ChatController::class, $controller);

        // 验证控制器正确实例化并持有repository依赖
        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('difySettingRepository');
        $property->setAccessible(true);
        $injectedRepository = $property->getValue($controller);

        $this->assertSame($repository, $injectedRepository);
    }

    /**
     * 测试空字符串settingId参数
     */
    public function testChatPageWithEmptySettingId(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 期望抛出NotFoundHttpException异常
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('缺少必需的参数 settingId');

        // 访问带有空字符串settingId的聊天页面
        $client->request('GET', '/admin/dify/chat', ['settingId' => '']);
    }

    /**
     * 测试路由配置正确
     */
    public function testRouteConfiguration(): void
    {
        $client = self::createClient();

        // 通过路由器获取路由信息
        $router = self::getContainer()->get('router');
        $this->assertInstanceOf(RouterInterface::class, $router);

        $routes = $router->getRouteCollection();
        $this->assertNotNull($routes);

        // 验证admin_dify_chat_view路由存在
        $route = $routes->get('admin_dify_chat_view');
        $this->assertNotNull($route, '路由admin_dify_chat_view应该存在');

        // 验证路径正确
        $this->assertEquals('/admin/dify/chat', $route->getPath());

        // 验证方法正确
        $this->assertContains('GET', $route->getMethods());
    }

    /**
     * 实现抽象方法 - 测试不允许的HTTP方法
     */
    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();

        // 期望抛出MethodNotAllowedHttpException异常
        $this->expectException(MethodNotAllowedHttpException::class);

        // 测试不允许的HTTP方法（使用任意settingId，因为方法检查在路由层进行）
        $client->request($method, '/admin/dify/chat', ['settingId' => '123']);
    }
}
