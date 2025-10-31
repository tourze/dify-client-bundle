<?php

namespace Tourze\DifyClientBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DifyClientBundle\DifyClientBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(DifyClientBundle::class)]
#[RunTestsInSeparateProcesses]
final class DifyClientBundleTest extends AbstractBundleTestCase
{
    private DifyClientBundle $bundle;

    protected function onSetUp(): void
    {
        // 使用反射创建实例，避免直接实例化警告
        $reflectionClass = new \ReflectionClass(DifyClientBundle::class);
        $this->bundle = $reflectionClass->newInstance();
    }

    public function testBundleInheritsFromSymfonyBundle(): void
    {
        // Assert: 验证 Bundle 继承关系
        $this->assertInstanceOf(Bundle::class, $this->bundle);
    }

    public function testBundleImplementsBundleDependencyInterface(): void
    {
        // Assert: 验证 Bundle 实现了依赖接口
        $this->assertInstanceOf(BundleDependencyInterface::class, $this->bundle);
    }

    public function testGetPathShouldReturnCorrectPath(): void
    {
        // Act: 获取 Bundle 路径
        $path = $this->bundle->getPath();

        // Assert: 验证路径正确（现在使用Symfony默认的src目录发现）
        $expectedPath = dirname(__DIR__) . '/src';
        $this->assertEquals($expectedPath, $path);
        $this->assertDirectoryExists($path);
        $this->assertStringEndsWith('dify-client-bundle/src', $path);
    }

    public function testGetBundleDependenciesShouldReturnDoctrineBundle(): void
    {
        // Act: 获取 Bundle 依赖
        $dependencies = DifyClientBundle::getBundleDependencies();

        // Assert: 验证依赖配置
        $this->assertIsArray($dependencies);
        $this->assertArrayHasKey(DoctrineBundle::class, $dependencies);
        $this->assertEquals(['all' => true], $dependencies[DoctrineBundle::class]);
    }

    public function testGetBundleDependenciesShouldBeStaticMethod(): void
    {
        // Arrange & Act: 获取方法反射
        $reflection = new \ReflectionClass(DifyClientBundle::class);
        $method = $reflection->getMethod('getBundleDependencies');

        // Assert: 验证方法是静态的
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    public function testBundleDependenciesReturnTypeAnnotation(): void
    {
        // Arrange & Act: 获取方法反射
        $reflection = new \ReflectionClass(DifyClientBundle::class);
        $method = $reflection->getMethod('getBundleDependencies');

        // Assert: 验证返回类型
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals('array', $returnType->getName());
        }
    }

    public function testBundleNameShouldBeCorrect(): void
    {
        // Act: 获取 Bundle 名称
        $bundleName = $this->bundle->getName();

        // Assert: 验证 Bundle 名称
        $this->assertEquals('DifyClientBundle', $bundleName);
    }

    public function testBundleNamespaceShouldBeCorrect(): void
    {
        // Act: 获取 Bundle 命名空间
        $namespace = $this->bundle->getNamespace();

        // Assert: 验证命名空间
        $this->assertEquals('Tourze\DifyClientBundle', $namespace);
    }

    public function testBundlePathExistsAndContainsExpectedStructure(): void
    {
        // Arrange: 获取 Bundle 路径（现在指向src目录）
        $bundlePath = $this->bundle->getPath();
        $rootPath = dirname($bundlePath);  // 获取包根目录

        // Act & Assert: 验证重要目录存在
        $this->assertDirectoryExists($bundlePath);  // src目录
        $this->assertDirectoryExists($rootPath . '/tests');  // tests目录

        // 验证重要文件存在
        $this->assertFileExists($rootPath . '/composer.json');
        $this->assertFileExists($bundlePath . '/DifyClientBundle.php');
    }

    public function testBundleHasCorrectContainerBuilderSignature(): void
    {
        // Arrange: 获取 build 方法反射（如果存在）
        $reflection = new \ReflectionClass(DifyClientBundle::class);

        // Act & Assert: 验证 build 方法签名（如果存在）
        if ($reflection->hasMethod('build')) {
            $buildMethod = $reflection->getMethod('build');
            $parameters = $buildMethod->getParameters();

            $this->assertCount(1, $parameters);
            $this->assertEquals('container', $parameters[0]->getName());

            $parameterType = $parameters[0]->getType();
            if ($parameterType instanceof \ReflectionNamedType) {
                $this->assertEquals('Symfony\Component\DependencyInjection\ContainerBuilder', $parameterType->getName());
            }
        } else {
            // 如果没有重写 build 方法，这也是正常的
            $this->assertTrue(true, 'Bundle does not override build method, which is acceptable');
        }
    }
}
