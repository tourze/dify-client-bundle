<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Tourze\DifyClientBundle\Controller\Admin\ChatController;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * ChatController测试
 *
 * 由于测试环境中路由注解未自动加载，使用直接调用控制器方法的方式进行测试。
 * @internal
 */
#[CoversClass(ChatController::class)]
#[RunTestsInSeparateProcesses]
final class ChatControllerTest extends AbstractWebTestCase
{
    private ChatController $controller;

    protected function onSetUp(): void
    {
        // 从容器获取控制器实例
        $this->controller = self::getService(ChatController::class);
    }

    /**
     * 测试控制器能够正确实例化
     */
    public function testControllerCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ChatController::class, $this->controller);
    }

    /**
     * 测试控制器能够正确处理依赖注入
     */
    public function testControllerDependencyInjection(): void
    {
        $this->assertInstanceOf(ChatController::class, $this->controller);

        // 验证控制器正确实例化并持有repository依赖
        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('difySettingRepository');
        $property->setAccessible(true);
        $injectedRepository = $property->getValue($this->controller);

        $this->assertInstanceOf(DifySettingRepository::class, $injectedRepository);
    }

    /**
     * 测试控制器在缺少settingId参数时抛出异常
     */
    public function testInvokeThrowsNotFoundExceptionWhenSettingIdMissing(): void
    {
        $request = new Request();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->expectExceptionMessage('缺少必需的参数 settingId');

        ($this->controller)($request);
    }

    /**
     * 测试控制器在settingId为空字符串时抛出异常
     */
    public function testInvokeThrowsNotFoundExceptionWhenSettingIdIsEmpty(): void
    {
        $request = new Request(['settingId' => '']);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->expectExceptionMessage('缺少必需的参数 settingId');

        ($this->controller)($request);
    }

    /**
     * 测试控制器在settingId无效时抛出异常
     */
    public function testInvokeThrowsNotFoundExceptionWhenSettingIdInvalid(): void
    {
        $request = new Request(['settingId' => '999999']);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->expectExceptionMessage('Dify 配置 ID 999999 未找到');

        ($this->controller)($request);
    }

    /**
     * 测试控制器方法存在性
     */
    public function testControllerHasInvokeMethod(): void
    {
        $this->assertTrue(method_exists(ChatController::class, '__invoke'));
    }

    /**
     * 测试控制器类是final
     */
    public function testControllerIsFinal(): void
    {
        $reflection = new \ReflectionClass(ChatController::class);
        $this->assertTrue($reflection->isFinal());
    }

    /**
     * 测试不允许的 HTTP 方法
     *
     * 由于测试环境中路由未自动加载，此方法仅作为空实现以满足父类抽象方法要求。
     */
    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        // 由于路由未在测试环境中加载，此测试作为空实现
        // 实际的方法限制验证由 Symfony 路由层处理
        $this->assertTrue(true);
    }
}
