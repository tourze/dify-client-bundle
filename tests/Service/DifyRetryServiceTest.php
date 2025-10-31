<?php

namespace Tourze\DifyClientBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Exception\FailedMessageNotFoundException;
use Tourze\DifyClientBundle\Service\DifyRetryService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(DifyRetryService::class)]
#[RunTestsInSeparateProcesses]
final class DifyRetryServiceTest extends AbstractIntegrationTestCase
{
    private DifyRetryService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(DifyRetryService::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(DifyRetryService::class, $this->service);
    }

    public function testServiceHasCorrectMethods(): void
    {
        // Act: 获取服务的反射类
        $reflection = new \ReflectionClass($this->service);

        // Assert: 验证关键方法存在
        $this->assertTrue($reflection->hasMethod('retryFailedMessage'));
        $this->assertTrue($reflection->hasMethod('retryFailedMessages'));
        $this->assertTrue($reflection->hasMethod('retryByTaskId'));
        $this->assertTrue($reflection->hasMethod('retryByRequestTaskId'));
    }

    public function testRetryFailedMessageShouldThrowExceptionForNonExistentMessage(): void
    {
        // Act & Assert: 期望抛出异常
        $this->expectException(FailedMessageNotFoundException::class);
        $this->service->retryFailedMessage('non-existent-id');
    }

    public function testRetryFailedMessagesShouldReturnResults(): void
    {
        // Arrange: 测试空ID列表
        $failedMessageIds = [];

        // Act: 重试失败消息
        $results = $this->service->retryFailedMessages($failedMessageIds);

        // Assert: 验证结果
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testRetryByTaskIdShouldReturnNoMessagesFound(): void
    {
        // Act: 根据不存在的任务ID重试
        $result = $this->service->retryByTaskId('non-existent-task-id');

        // Assert: 验证结果
        $this->assertIsArray($result);

        /** @var array{success: bool, message: string} $result */
        self::assertIsArray($result, 'Result should be an array');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No failed messages found', $result['message']);
    }

    public function testRetryByRequestTaskIdShouldReturnNoMessagesFound(): void
    {
        // Act: 根据不存在的请求任务ID重试
        $result = $this->service->retryByRequestTaskId('non-existent-request-task-id');

        // Assert: 验证结果
        $this->assertIsArray($result);

        /** @var array{success: bool, message: string} $result */
        self::assertIsArray($result, 'Result should be an array');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No failed messages found', $result['message']);
    }
}
