<?php

namespace Tourze\DifyClientBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Service\MessageAggregator;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(MessageAggregator::class)]
#[RunTestsInSeparateProcesses]
final class MessageAggregatorTest extends AbstractIntegrationTestCase
{
    private MessageAggregator $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(MessageAggregator::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(MessageAggregator::class, $this->service);
    }

    public function testAddMessageShouldAcceptString(): void
    {
        // Arrange: 期望不执行任何断言
        $this->expectNotToPerformAssertions();

        // Act: 验证方法可以被调用而不抛出异常
        $this->service->addMessage('Test message');
    }

    public function testServiceHasCorrectMethods(): void
    {
        // Act: 获取服务的反射类
        $reflection = new \ReflectionClass($this->service);

        // Assert: 验证关键方法存在
        $this->assertTrue($reflection->hasMethod('forceProcess'));
        $this->assertTrue($reflection->hasMethod('reset'));
        $this->assertTrue($reflection->hasMethod('addMessage'));
    }

    public function testForceProcessShouldNotThrowException(): void
    {
        // Arrange: 期望不执行任何断言
        $this->expectNotToPerformAssertions();

        // Act: 验证方法可以被调用而不抛出异常
        $this->service->forceProcess();
    }

    public function testResetShouldNotThrowException(): void
    {
        // Arrange: 期望不执行任何断言
        $this->expectNotToPerformAssertions();

        // Act: 验证方法可以被调用而不抛出异常
        $this->service->reset();
    }
}
