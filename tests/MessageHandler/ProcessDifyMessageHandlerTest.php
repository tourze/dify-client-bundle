<?php

namespace Tourze\DifyClientBundle\Tests\MessageHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Tourze\DifyClientBundle\MessageHandler\ProcessDifyMessageHandler;
use Tourze\DifyClientBundle\Service\DifyMessengerService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * ProcessDifyMessageHandler 测试
 *
 * 验证消息处理器正确配置和实例化
 * @internal
 */
#[CoversClass(ProcessDifyMessageHandler::class)]
#[RunTestsInSeparateProcesses]
final class ProcessDifyMessageHandlerTest extends AbstractIntegrationTestCase
{
    private ProcessDifyMessageHandler $handler;

    protected function onSetUp(): void
    {
        $this->handler = self::getService(ProcessDifyMessageHandler::class);
    }

    public function testHandlerCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ProcessDifyMessageHandler::class, $this->handler);
    }

    public function testHandlerHasCorrectMessageHandlerAttribute(): void
    {
        $reflection = new \ReflectionClass(ProcessDifyMessageHandler::class);
        $attributes = $reflection->getAttributes(AsMessageHandler::class);

        $this->assertNotEmpty($attributes, 'ProcessDifyMessageHandler should have AsMessageHandler attribute');
    }

    public function testHandlerConstructorDependencies(): void
    {
        $reflection = new \ReflectionClass(ProcessDifyMessageHandler::class);

        // 验证构造函数参数
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);

        $parameters = $constructor->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('difyMessengerService', $parameters[0]->getName());
        $type = $parameters[0]->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertEquals(DifyMessengerService::class, $type->getName());
    }

    public function testHandlerHasInvokeMethod(): void
    {
        $this->assertTrue(method_exists(ProcessDifyMessageHandler::class, '__invoke'));
    }

    public function testHandlerIsFinal(): void
    {
        $reflection = new \ReflectionClass(ProcessDifyMessageHandler::class);
        $this->assertTrue($reflection->isFinal());
    }
}
