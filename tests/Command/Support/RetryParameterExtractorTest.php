<?php

namespace Tourze\DifyClientBundle\Tests\Command\Support;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Input\InputInterface;
use Tourze\DifyClientBundle\Command\Support\RetryParameterExtractor;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(RetryParameterExtractor::class)]
#[RunTestsInSeparateProcesses]
final class RetryParameterExtractorTest extends AbstractIntegrationTestCase
{
    private RetryParameterExtractor $extractor;

    protected function onSetUp(): void
    {
        $this->extractor = self::getService(RetryParameterExtractor::class);
    }

    public function testExtractWithValidParametersShouldReturnCorrectArray(): void
    {
        // Arrange: Mock 输入接口
        $input = $this->createMock(InputInterface::class);

        // 配置 Mock 返回值
        $input->method('getArgument')->willReturnMap([
            ['id', 'test-message-id-123'],
        ]);

        $input->method('getOption')->willReturnMap([
            ['all', true],
            ['batch', false],
            ['limit', 25],
            ['dry-run', true],
            ['request-task', 'task-456'],
        ]);

        // Act: 提取参数
        $result = $this->extractor->extract($input);

        // Assert: 验证返回结果
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('all', $result);
        $this->assertArrayHasKey('batch', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('dryRun', $result);
        $this->assertArrayHasKey('requestTask', $result);

        $this->assertEquals('test-message-id-123', $result['id']);
        $this->assertTrue($result['all']);
        $this->assertFalse($result['batch']);
        $this->assertEquals(25, $result['limit']);
        $this->assertTrue($result['dryRun']);
        $this->assertEquals('task-456', $result['requestTask']);
    }

    public function testExtractWithNullValuesShouldReturnDefaults(): void
    {
        // Arrange: Mock 输入接口返回 null 值
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturnMap([
            ['id', null],
        ]);

        $input->method('getOption')->willReturnMap([
            ['all', null],
            ['batch', null],
            ['limit', null],
            ['dry-run', null],
            ['request-task', null],
        ]);

        // Act: 提取参数
        $result = $this->extractor->extract($input);

        // Assert: 验证默认值
        $this->assertEquals('', $result['id']);
        $this->assertFalse($result['all']);
        $this->assertFalse($result['batch']);
        $this->assertEquals(10, $result['limit']); // 默认值
        $this->assertFalse($result['dryRun']);
        $this->assertEquals('', $result['requestTask']);
    }

    public function testExtractWithEmptyStringsShouldReturnEmptyStrings(): void
    {
        // Arrange: Mock 输入接口返回空字符串
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturnMap([
            ['id', ''],
        ]);

        $input->method('getOption')->willReturnMap([
            ['all', false],
            ['batch', false],
            ['limit', ''],
            ['dry-run', false],
            ['request-task', ''],
        ]);

        // Act: 提取参数
        $result = $this->extractor->extract($input);

        // Assert: 验证结果
        $this->assertEquals('', $result['id']);
        $this->assertEquals('', $result['requestTask']);
        $this->assertEquals(10, $result['limit']); // 非数字字符串使用默认值
    }

    public function testExtractWithNumericStringsShouldConvertToIntegers(): void
    {
        // Arrange: Mock 输入接口返回数字字符串
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturn('123');

        $input->method('getOption')->willReturnMap([
            ['all', false],
            ['batch', false],
            ['limit', '50'],
            ['dry-run', false],
            ['request-task', ''],
        ]);

        // Act: 提取参数
        $result = $this->extractor->extract($input);

        // Assert: 验证数字转换
        $this->assertEquals('123', $result['id']);
        $this->assertEquals(50, $result['limit']);
        $this->assertIsInt($result['limit']);
    }

    public function testExtractWithInvalidNumericValueShouldUseDefault(): void
    {
        // Arrange: Mock 输入接口返回无效数字值
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturn('id-123');

        $input->method('getOption')->willReturnMap([
            ['all', false],
            ['batch', false],
            ['limit', 'invalid-number'],
            ['dry-run', false],
            ['request-task', 'task'],
        ]);

        // Act: 提取参数
        $result = $this->extractor->extract($input);

        // Assert: 验证默认值被使用
        $this->assertEquals('id-123', $result['id']);
        $this->assertEquals(10, $result['limit']); // 使用默认值
        $this->assertEquals('task', $result['requestTask']);
    }

    public function testExtractWithZeroLimitShouldReturnZero(): void
    {
        // Arrange: Mock 输入接口返回 0 作为限制值
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturn('');

        $input->method('getOption')->willReturnMap([
            ['all', false],
            ['batch', false],
            ['limit', 0],
            ['dry-run', false],
            ['request-task', ''],
        ]);

        // Act: 提取参数
        $result = $this->extractor->extract($input);

        // Assert: 验证 0 值被正确处理
        $this->assertEquals(0, $result['limit']);
    }

    public function testExtractWithNegativeLimitShouldReturnNegativeValue(): void
    {
        // Arrange: Mock 输入接口返回负数
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturn('');

        $input->method('getOption')->willReturnMap([
            ['all', false],
            ['batch', false],
            ['limit', -5],
            ['dry-run', false],
            ['request-task', ''],
        ]);

        // Act: 提取参数
        $result = $this->extractor->extract($input);

        // Assert: 验证负值被保留
        $this->assertEquals(-5, $result['limit']);
    }

    public function testExtractWithBooleanTrueValuesShouldReturnTrue(): void
    {
        // Arrange: Mock 输入接口返回真值
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturn('msg-id');

        $input->method('getOption')->willReturnMap([
            ['all', 1],
            ['batch', 'true'],
            ['limit', 15],
            ['dry-run', 'yes'],
            ['request-task', ''],
        ]);

        // Act: 提取参数
        $result = $this->extractor->extract($input);

        // Assert: 验证布尔值转换
        $this->assertTrue($result['all']);
        $this->assertTrue($result['batch']);
        $this->assertTrue($result['dryRun']);
    }

    public function testExtractShouldReturnArrayWithCorrectStructure(): void
    {
        // Arrange: Mock 输入接口
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturn('test-id');
        $input->method('getOption')->willReturn(false);

        // Act: 提取参数
        $result = $this->extractor->extract($input);

        // Assert: 验证返回数组结构符合类型定义
        $this->assertIsArray($result);
        $this->assertCount(6, $result);

        // 验证所有键存在且类型正确
        $this->assertIsString($result['id']);
        $this->assertIsBool($result['all']);
        $this->assertIsBool($result['batch']);
        $this->assertIsInt($result['limit']);
        $this->assertIsBool($result['dryRun']);
        $this->assertIsString($result['requestTask']);
    }

    public function testClassCanBeInstantiated(): void
    {
        // Assert: 验证类可以被实例化
        $this->assertInstanceOf(RetryParameterExtractor::class, $this->extractor);
    }

    public function testClassHasRequiredMethods(): void
    {
        // Act: 获取类的反射信息
        $reflection = new \ReflectionClass($this->extractor);

        // Assert: 验证必需的公共方法存在
        $this->assertTrue($reflection->hasMethod('extract'));

        // 验证方法的可见性
        $extractMethod = $reflection->getMethod('extract');
        $this->assertTrue($extractMethod->isPublic());

        // 验证方法参数
        $parameters = $extractMethod->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('input', $parameters[0]->getName());
    }

    public function testExtractMethodReturnTypeAnnotation(): void
    {
        // Act: 获取方法的反射信息
        $reflection = new \ReflectionClass($this->extractor);
        $method = $reflection->getMethod('extract');

        // Assert: 验证方法有返回类型声明
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertEquals('array', $returnType->getName());
    }
}
