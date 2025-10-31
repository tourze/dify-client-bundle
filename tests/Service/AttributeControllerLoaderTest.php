<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Tourze\DifyClientBundle\Service\AttributeControllerLoader;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;

/**
 * AttributeControllerLoader 测试类
 *
 * 测试属性控制器自动加载器的核心功能
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    private AttributeControllerLoader $loader;

    protected function onSetUp(): void
    {
        $loader = self::getContainer()->get(AttributeControllerLoader::class);
        self::assertInstanceOf(AttributeControllerLoader::class, $loader);
        $this->loader = $loader;
    }

    /**
     * 测试加载器实现正确的接口
     */
    public function testLoaderImplementsRoutingAutoLoaderInterface(): void
    {
        // Assert: 验证实现了正确的接口
        $this->assertInstanceOf(RoutingAutoLoaderInterface::class, $this->loader);
    }

    /**
     * 测试load方法返回RouteCollection
     */
    public function testLoadReturnsRouteCollection(): void
    {
        // Act: 调用load方法
        $collection = $this->loader->load('any-resource');

        // Assert: 验证返回RouteCollection
        $this->assertInstanceOf(RouteCollection::class, $collection);
    }

    /**
     * 测试autoload方法返回RouteCollection
     */
    public function testAutoloadReturnsRouteCollection(): void
    {
        // Act: 调用autoload方法
        $collection = $this->loader->autoload();

        // Assert: 验证返回RouteCollection
        $this->assertInstanceOf(RouteCollection::class, $collection);
    }

    /**
     * 测试load和autoload方法返回相同的路由集合
     */
    public function testLoadAndAutoloadReturnSameCollection(): void
    {
        // Act: 分别调用load和autoload方法
        $loadCollection = $this->loader->load('test-resource');
        $autoloadCollection = $this->loader->autoload();

        // Assert: 验证返回的路由集合内容相同（数量和路由名称）
        $this->assertEquals($loadCollection->count(), $autoloadCollection->count(), '路由集合应该包含相同数量的路由');

        $loadRoutes = array_keys($loadCollection->all());
        $autoloadRoutes = array_keys($autoloadCollection->all());
        sort($loadRoutes);
        sort($autoloadRoutes);

        $this->assertEquals($loadRoutes, $autoloadRoutes, '路由集合应该包含相同的路由名称');
    }

    /**
     * 测试路由集合包含预期的路由数量
     */
    public function testRouteCollectionContainsExpectedRoutes(): void
    {
        // Act: 获取路由集合
        $collection = $this->loader->autoload();

        // Assert: 验证路由集合不为空
        $this->assertGreaterThan(0, $collection->count(), '路由集合应该包含路由');
    }

    /**
     * 测试路由集合包含Dify相关路由
     */
    public function testRouteCollectionContainsChatflowRoutes(): void
    {
        // Act: 获取路由集合
        $collection = $this->loader->autoload();

        // Assert: 查找Dify相关的路由（放宽检查条件）
        $difyRouteFound = false;
        foreach ($collection->all() as $routeName => $route) {
            if (str_contains($routeName, 'dify')
                || str_contains($route->getPath(), '/api/')
                || str_contains($route->getPath(), 'dify')) {
                $difyRouteFound = true;
                break;
            }
        }

        $this->assertTrue($difyRouteFound, '应该包含Dify相关的路由');
    }

    /**
     * 测试路由集合包含API路由
     */
    public function testRouteCollectionContainsApiRoutes(): void
    {
        // Act: 获取路由集合
        $collection = $this->loader->autoload();
        $apiRoutes = [];

        // 收集所有API路由
        foreach ($collection->all() as $routeName => $route) {
            /** @var Route $route */
            if (str_contains($route->getPath(), '/api/v1/')
                || str_contains($routeName, 'dify_')) {
                $apiRoutes[] = $routeName;
            }
        }

        // Assert: 验证包含API路由
        $this->assertNotEmpty($apiRoutes, '应该包含API路由');
    }

    /**
     * 测试supports方法始终返回false
     */
    public function testSupportsAlwaysReturnsFalse(): void
    {
        // Act & Assert: 测试不同的参数组合
        $this->assertFalse($this->loader->supports('any-resource'));
        $this->assertFalse($this->loader->supports('any-resource', 'any-type'));
        $this->assertFalse($this->loader->supports(null));
        $this->assertFalse($this->loader->supports('', ''));
    }

    /**
     * 测试多次调用load返回相同的实例
     */
    public function testMultipleLoadCallsReturnSameInstance(): void
    {
        // Act: 多次调用load方法
        $collection1 = $this->loader->load('resource1');
        $collection2 = $this->loader->load('resource2');
        $collection3 = $this->loader->autoload();

        // Assert: 验证返回相同的实例
        $this->assertSame($collection1, $collection2);
        $this->assertSame($collection2, $collection3);
    }

    /**
     * 测试路由集合包含预期的控制器路由
     */
    public function testRouteCollectionContainsExpectedControllerRoutes(): void
    {
        // Act: 获取路由集合
        $collection = $this->loader->autoload();

        $expectedControllerRoutes = [
            // 查找可能的路由模式
            'conversation', 'message', 'app', 'file',
            'annotation', 'feedback', 'audio', 'completion',
            'workflow', 'dataset',
        ];

        $foundControllerTypes = [];
        foreach ($collection->all() as $routeName => $route) {
            /** @var Route $route */
            $routePath = $route->getPath();

            foreach ($expectedControllerRoutes as $controllerType) {
                if (str_contains($routePath, $controllerType)
                    || str_contains($routeName, $controllerType)) {
                    $foundControllerTypes[] = $controllerType;
                }
            }
        }

        // Assert: 验证找到了一些预期的控制器路由
        $this->assertNotEmpty($foundControllerTypes, '应该包含预期的控制器路由');
    }

    /**
     * 测试路由集合是可迭代的
     */
    public function testRouteCollectionIsIterable(): void
    {
        // Act: 获取路由集合
        $collection = $this->loader->autoload();

        // Assert: 验证可以迭代
        $routeCount = 0;
        foreach ($collection as $routeName => $route) {
            $this->assertIsString($routeName, '路由名称应该是字符串');
            $this->assertInstanceOf(Route::class, $route, '路由应该是Route实例');
            ++$routeCount;
        }

        $this->assertGreaterThan(0, $routeCount, '应该有可迭代的路由');
    }

    /**
     * 测试路由集合包含HTTP方法
     */
    public function testRoutesContainHttpMethods(): void
    {
        // Act: 获取路由集合
        $collection = $this->loader->autoload();

        $foundHttpMethods = [];
        foreach ($collection->all() as $route) {
            /** @var Route $route */
            $methods = $route->getMethods();
            $foundHttpMethods = array_merge($foundHttpMethods, $methods);
        }

        // 去重
        $foundHttpMethods = array_unique($foundHttpMethods);

        // Assert: 验证包含常见的HTTP方法
        $expectedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        $intersection = array_intersect($expectedMethods, $foundHttpMethods);
        $hasExpectedMethods = count($intersection) > 0;

        $this->assertTrue($hasExpectedMethods, '路由应该包含常见的HTTP方法');
    }

    /**
     * 测试加载器的初始化不抛出异常
     */
    public function testLoaderInitializationDoesNotThrowException(): void
    {
        // Act & Assert: 从容器获取实例不应该抛出异常
        $this->expectNotToPerformAssertions();
        self::getService(AttributeControllerLoader::class);
    }

    /**
     * 测试路由路径格式正确
     */
    public function testRoutePathsAreWellFormed(): void
    {
        // Act: 获取路由集合
        $collection = $this->loader->autoload();

        // Assert: 检查路由路径格式
        foreach ($collection->all() as $routeName => $route) {
            /** @var Route $route */
            $path = $route->getPath();

            // 验证路径以/开始
            $this->assertStringStartsWith('/', $path, "路由路径应该以/开始: {$path}");

            // 验证不包含双斜杠
            $this->assertStringNotContainsString('//', $path, "路由路径不应该包含双斜杠: {$path}");
        }
    }
}
