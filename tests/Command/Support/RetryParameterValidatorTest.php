<?php

namespace Tourze\DifyClientBundle\Tests\Command\Support;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Input\InputInterface;
use Tourze\DifyClientBundle\Command\Support\RetryParameterValidator;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(RetryParameterValidator::class)]
#[RunTestsInSeparateProcesses]
final class RetryParameterValidatorTest extends AbstractIntegrationTestCase
{
    private RetryParameterValidator $validator;

    protected function onSetUp(): void
    {
        $this->validator = self::getService(RetryParameterValidator::class);
    }

    public function testValidateWithValidParametersShouldReturnEmptyArray(): void
    {
        // Arrange: Mock 输入接口，配置有效参数
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturnMap([
            ['id', 'message-123'],
        ]);

        $input->method('getOption')->willReturnMap([
            ['batch', false],
            ['request-task', false],
            ['all', false],
        ]);

        // Act: 验证参数
        $errors = $this->validator->validate($input);

        // Assert: 应该没有错误
        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    public function testValidateWithBatchAndRequestTaskShouldReturnError(): void
    {
        // Arrange: Mock 输入接口，同时指定 batch 和 request-task
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturnMap([
            ['id', 'task-123'],
        ]);

        $input->method('getOption')->willReturnMap([
            ['batch', true],
            ['request-task', true],
            ['all', false],
        ]);

        // Act: 验证参数
        $errors = $this->validator->validate($input);

        // Assert: 应该有错误
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
        $this->assertContains('不能同时指定--batch和--request-task选项', $errors);
    }

    public function testValidateWithBatchButNoIdShouldReturnError(): void
    {
        // Arrange: Mock 输入接口，指定 batch 但没有 ID
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturnMap([
            ['id', null],
        ]);

        $input->method('getOption')->willReturnMap([
            ['batch', true],
            ['request-task', false],
            ['all', false],
        ]);

        // Act: 验证参数
        $errors = $this->validator->validate($input);

        // Assert: 应该有错误
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
        $this->assertContains('使用--batch选项时必须指定RequestTask ID', $errors);
    }

    public function testValidateWithIdAndAllShouldReturnError(): void
    {
        // Arrange: Mock 输入接口，同时指定 ID 和 all
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturnMap([
            ['id', 'message-123'],
        ]);

        $input->method('getOption')->willReturnMap([
            ['batch', false],
            ['request-task', false],
            ['all', true],
        ]);

        // Act: 验证参数
        $errors = $this->validator->validate($input);

        // Assert: 应该有错误
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
        $this->assertContains('不能同时指定ID和--all选项', $errors);
    }

    public function testValidateWithMultipleErrorsShouldReturnAllErrors(): void
    {
        // Arrange: Mock 输入接口，配置多个冲突参数
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturnMap([
            ['id', 'message-123'],
        ]);

        $input->method('getOption')->willReturnMap([
            ['batch', true],
            ['request-task', true],
            ['all', true],
        ]);

        // Act: 验证参数
        $errors = $this->validator->validate($input);

        // Assert: 应该有多个错误
        $this->assertIsArray($errors);
        $this->assertCount(2, $errors); // 应该有两个错误
        $this->assertContains('不能同时指定--batch和--request-task选项', $errors);
        $this->assertContains('不能同时指定ID和--all选项', $errors);
    }

    public function testValidateWithBatchAndValidIdShouldReturnNoErrors(): void
    {
        // Arrange: Mock 输入接口，指定 batch 和有效 ID
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturnMap([
            ['id', 'request-task-123'],
        ]);

        $input->method('getOption')->willReturnMap([
            ['batch', true],
            ['request-task', false],
            ['all', false],
        ]);

        // Act: 验证参数
        $errors = $this->validator->validate($input);

        // Assert: 应该没有错误
        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    public function testValidateWithRequestTaskOnlyShouldReturnNoErrors(): void
    {
        // Arrange: Mock 输入接口，只指定 request-task
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturnMap([
            ['id', null],
        ]);

        $input->method('getOption')->willReturnMap([
            ['batch', false],
            ['request-task', true],
            ['all', false],
        ]);

        // Act: 验证参数
        $errors = $this->validator->validate($input);

        // Assert: 应该没有错误
        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    public function testValidateWithAllOnlyShouldReturnNoErrors(): void
    {
        // Arrange: Mock 输入接口，只指定 all
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturnMap([
            ['id', null],
        ]);

        $input->method('getOption')->willReturnMap([
            ['batch', false],
            ['request-task', false],
            ['all', true],
        ]);

        // Act: 验证参数
        $errors = $this->validator->validate($input);

        // Assert: 应该没有错误
        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    public function testValidateWithEmptyIdStringShouldBeTreatedAsNoId(): void
    {
        // Arrange: Mock 输入接口，ID 为空字符串
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturnMap([
            ['id', ''],
        ]);

        $input->method('getOption')->willReturnMap([
            ['batch', true],
            ['request-task', false],
            ['all', false],
        ]);

        // Act: 验证参数
        $errors = $this->validator->validate($input);

        // Assert: 空字符串应该被当作没有 ID，产生错误
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
        $this->assertContains('使用--batch选项时必须指定RequestTask ID', $errors);
    }

    public function testValidateWithWhitespaceIdShouldBeTreatedAsHasId(): void
    {
        // Arrange: Mock 输入接口，ID 为空白字符串
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturnMap([
            ['id', '   '],
        ]);

        $input->method('getOption')->willReturnMap([
            ['batch', true],
            ['request-task', false],
            ['all', true],
        ]);

        // Act: 验证参数
        $errors = $this->validator->validate($input);

        // Assert: 空白字符串应该被当作有 ID
        $this->assertIsArray($errors);
        $this->assertCount(1, $errors); // 只有 ID + all 冲突的错误
        $this->assertContains('不能同时指定ID和--all选项', $errors);
    }

    public function testValidateWithAllFalseOptionsShouldReturnNoErrors(): void
    {
        // Arrange: Mock 输入接口，所有选项都为 false
        $input = $this->createMock(InputInterface::class);

        $input->method('getArgument')->willReturnMap([
            ['id', null],
        ]);

        $input->method('getOption')->willReturnMap([
            ['batch', false],
            ['request-task', false],
            ['all', false],
        ]);

        // Act: 验证参数
        $errors = $this->validator->validate($input);

        // Assert: 应该没有错误
        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    public function testClassCanBeInstantiated(): void
    {
        // Assert: 验证类可以被实例化
        $this->assertInstanceOf(RetryParameterValidator::class, $this->validator);
    }

    public function testClassHasRequiredMethods(): void
    {
        // Act: 获取类的反射信息
        $reflection = new \ReflectionClass($this->validator);

        // Assert: 验证必需的公共方法存在
        $this->assertTrue($reflection->hasMethod('validate'));

        // 验证方法的可见性
        $validateMethod = $reflection->getMethod('validate');
        $this->assertTrue($validateMethod->isPublic());

        // 验证方法参数
        $parameters = $validateMethod->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('input', $parameters[0]->getName());
    }

    public function testValidateMethodReturnTypeAnnotation(): void
    {
        // Act: 获取方法的反射信息
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('validate');

        // Assert: 验证方法有返回类型声明
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    public function testValidateMethodAlwaysReturnsStringArray(): void
    {
        // Arrange: 多种不同的输入情况
        $testCases = [
            // 情况1：没有错误
            [null, false, false, false],
            // 情况2：有错误
            ['id', true, true, false],
            // 情况3：多个错误
            ['id', true, true, true],
        ];

        foreach ($testCases as [$id, $batch, $requestTask, $all]) {
            $input = $this->createMock(InputInterface::class);
            $input->method('getArgument')->willReturnMap([['id', $id]]);
            $input->method('getOption')->willReturnMap([
                ['batch', $batch],
                ['request-task', $requestTask],
                ['all', $all],
            ]);

            // Act: 验证参数
            $errors = $this->validator->validate($input);

            // Assert: 总是返回字符串数组
            $this->assertIsArray($errors);
            foreach ($errors as $error) {
                $this->assertIsString($error);
            }
        }
    }
}
