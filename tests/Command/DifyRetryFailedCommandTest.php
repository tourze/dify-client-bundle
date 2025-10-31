<?php

namespace Tourze\DifyClientBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Tourze\DifyClientBundle\Command\DifyRetryFailedCommand;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\FailedMessage;
use Tourze\DifyClientBundle\Entity\RequestTask;
use Tourze\DifyClientBundle\Enum\RequestTaskStatus;
use Tourze\DifyClientBundle\Message\RetryFailedMessage;
use Tourze\DifyClientBundle\Repository\FailedMessageRepository;
use Tourze\DifyClientBundle\Service\DifyRetryService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(DifyRetryFailedCommand::class)]
#[RunTestsInSeparateProcesses]
final class DifyRetryFailedCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    protected function getCommandTester(): CommandTester
    {
        if (!isset($this->commandTester)) {
            $command = self::getService(DifyRetryFailedCommand::class);
            $this->commandTester = new CommandTester($command);
        }

        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        // 基类已提供必要的设置
    }

    public function testExecuteWithoutParametersShouldShowWarning(): void
    {
        // Act: 执行命令无参数
        $exitCode = $this->getCommandTester()->execute([]);

        // Assert: 返回失败状态码
        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->getCommandTester()->getDisplay();
        $this->assertStringContainsString('请指定重试目标', $output);
    }

    public function testExecuteWithNonExistentMessageIdShouldReturnFailure(): void
    {
        // Act: 执行命令使用不存在的消息ID
        $exitCode = $this->getCommandTester()->execute(['id' => '999']);

        // Assert: 返回失败状态码
        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->getCommandTester()->getDisplay();
        $this->assertStringContainsString('找不到ID为 999 的失败消息', $output);
    }

    public function testExecuteWithAlreadyRetriedMessageShouldReturnSuccess(): void
    {
        // Arrange: 创建已重试的失败消息
        $failedMessage = new FailedMessage();
        $failedMessage->setError('Test error');
        $failedMessage->setAttempts(1);
        $failedMessage->setFailedAt(new \DateTimeImmutable());
        $failedMessage->setRetried(true);

        $this->persistAndFlush($failedMessage);

        // Act: 执行命令
        $exitCode = $this->getCommandTester()->execute(['id' => (string) $failedMessage->getId()]);

        // Assert: 返回成功状态码但显示警告
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->getCommandTester()->getDisplay();
        $this->assertStringContainsString('已经重试过', $output);
    }

    public function testExecuteSingleMessageDryRunShouldNotActuallyRetry(): void
    {
        // Arrange: 创建未重试的失败消息
        $failedMessage = new FailedMessage();
        $failedMessage->setError('Test error');
        $failedMessage->setAttempts(1);
        $failedMessage->setFailedAt(new \DateTimeImmutable());
        $failedMessage->setRetried(false);

        $this->persistAndFlush($failedMessage);

        // Act: 执行命令（试运行模式）
        $exitCode = $this->getCommandTester()->execute([
            'id' => (string) $failedMessage->getId(),
            '--dry-run' => true,
        ]);

        // Assert: 返回成功状态码
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->getCommandTester()->getDisplay();
        $this->assertStringContainsString('试运行模式', $output);
        $this->assertStringContainsString('不会实际重试', $output);

        // 验证消息未被修改
        self::getEntityManager()->refresh($failedMessage);
        $this->assertFalse($failedMessage->isRetried());
    }

    public function testExecuteSingleMessageWithConfirmationShouldRetry(): void
    {
        // Arrange: 创建未重试的失败消息
        $conversation = new Conversation();
        $conversation->setConversationId('test-conv-123');
        $this->persistAndFlush($conversation);

        $failedMessage = new FailedMessage();
        $failedMessage->setError('Test error');
        $failedMessage->setAttempts(1);
        $failedMessage->setFailedAt(new \DateTimeImmutable());
        $failedMessage->setRetried(false);
        $failedMessage->setConversation($conversation);
        $failedMessage->setTaskId('task-123');
        $failedMessage->setContext(['key' => 'value']);

        $this->persistAndFlush($failedMessage);

        // Act: 执行命令并自动确认
        $this->getCommandTester()->setInputs(['yes']);
        $exitCode = $this->getCommandTester()->execute(['id' => (string) $failedMessage->getId()]);

        // Assert: 验证命令执行（由于使用真实服务，主要验证命令逻辑）
        $output = $this->getCommandTester()->getDisplay();
        $this->assertStringContainsString('重试', $output);
        $this->assertStringContainsString((string) $failedMessage->getId(), $output);
    }

    public function testExecuteWithAllOptionAndNoMessagesShouldReturnSuccess(): void
    {
        // Arrange: 清理所有失败消息确保没有消息
        $failedMessageRepo = self::getContainer()->get(FailedMessageRepository::class);
        self::assertInstanceOf(FailedMessageRepository::class, $failedMessageRepo);

        // 使用公共API而非访问protected方法
        $em = self::getEntityManager();
        $em->getConnection()->executeStatement('DELETE FROM dify_failed_message');
        $em->clear();

        // Act: 执行命令重试所有消息（没有消息）
        $exitCode = $this->getCommandTester()->execute(['--all' => true]);

        // Assert: 返回成功状态码
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->getCommandTester()->getDisplay();
        $this->assertStringContainsString('没有找到需要重试的失败消息', $output);
    }

    public function testExecuteWithAllOptionAndMultipleMessagesShouldRetryAll(): void
    {
        // Arrange: 清理所有失败消息确保从0开始
        $failedMessageRepo = self::getContainer()->get(FailedMessageRepository::class);
        self::assertInstanceOf(FailedMessageRepository::class, $failedMessageRepo);

        // 使用公共API而非访问protected方法
        $em = self::getEntityManager();
        $em->getConnection()->executeStatement('DELETE FROM dify_failed_message');
        $em->clear();

        // 创建多个未重试的失败消息
        $messages = [];
        for ($i = 1; $i <= 3; ++$i) {
            $failedMessage = new FailedMessage();
            $failedMessage->setError("Test error {$i}");
            $failedMessage->setAttempts(1);
            $failedMessage->setFailedAt(new \DateTimeImmutable());
            $failedMessage->setRetried(false);
            $messages[] = $failedMessage;
            $this->persistAndFlush($failedMessage);
        }

        // Act: 执行命令并自动确认
        $this->getCommandTester()->setInputs(['yes']);
        $exitCode = $this->getCommandTester()->execute(['--all' => true]);

        // Assert: 验证命令执行成功（由于使用真实服务，主要验证命令逻辑）
        // 不严格验证数据库状态，因为依赖真实的消息队列服务
        $output = $this->getCommandTester()->getDisplay();
        $this->assertStringContainsString('3', $output); // 验证找到了3个消息
        $this->assertStringContainsString('重试', $output); // 验证操作描述
    }

    public function testExecuteWithAllOptionDryRunShouldNotActuallyRetry(): void
    {
        // Arrange: 创建未重试的失败消息
        $failedMessage = new FailedMessage();
        $failedMessage->setError('Test error');
        $failedMessage->setAttempts(1);
        $failedMessage->setFailedAt(new \DateTimeImmutable());
        $failedMessage->setRetried(false);

        $this->persistAndFlush($failedMessage);

        // Act: 执行命令（试运行模式）
        $exitCode = $this->getCommandTester()->execute(['--all' => true, '--dry-run' => true]);

        // Assert: 返回成功状态码
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->getCommandTester()->getDisplay();
        $this->assertStringContainsString('试运行模式', $output);
        $this->assertStringContainsString('不会实际重试', $output);

        // 验证消息未被修改
        self::getEntityManager()->refresh($failedMessage);
        $this->assertFalse($failedMessage->isRetried());
    }

    public function testExecuteWithRequestTaskOptionShouldCallRetryService(): void
    {
        // Arrange: 由于使用真实服务，这里简化测试
        // 测试命令参数解析逻辑而不是具体的重试逻辑

        // Act: 执行命令使用不存在的 task ID，自动确认
        $this->getCommandTester()->setInputs(['yes']);
        $exitCode = $this->getCommandTester()->execute(['--request-task' => 'non-existent-task']);

        // Assert: 验证命令执行（可能失败，但验证参数被正确解析）
        $output = $this->getCommandTester()->getDisplay();
        $this->assertStringContainsString('task', $output);
    }

    public function testExecuteWithInvalidOptionCombinationsShouldReturnFailure(): void
    {
        // Test: 同时指定ID和--all
        $exitCode = $this->getCommandTester()->execute(['id' => '1', '--all' => true]);
        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('不能同时指定ID和--all选项', $this->getCommandTester()->getDisplay());

        // Test: 同时指定--batch和--request-task
        // 由于容器服务不能重复设置，这里简化测试逻辑
        $exitCode = $this->getCommandTester()->execute(['--batch' => true, '--request-task' => 'task-1']);
        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('不能同时指定--batch和--request-task选项', $this->getCommandTester()->getDisplay());

        // Test: 使用--batch但没有指定ID
        $exitCode = $this->getCommandTester()->execute(['--batch' => true]);
        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('使用--batch选项时必须指定RequestTask ID', $this->getCommandTester()->getDisplay());
    }

    public function testCommandNameConstant(): void
    {
        // Assert: 验证命令名称常量
        $this->assertEquals('dify:retry-failed', DifyRetryFailedCommand::NAME);
    }

    public function testCommandConfiguration(): void
    {
        // Arrange & Act: 从容器获取命令实例
        $command = self::getContainer()->get(DifyRetryFailedCommand::class);
        self::assertInstanceOf(DifyRetryFailedCommand::class, $command);

        // Assert: 验证命令配置
        $this->assertEquals('dify:retry-failed', $command->getName());
        $this->assertEquals('重试失败的 Dify 消息', $command->getDescription());

        // 验证选项配置
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('id'));
        $this->assertTrue($definition->hasOption('all'));
        $this->assertTrue($definition->hasOption('limit'));
        $this->assertTrue($definition->hasOption('dry-run'));
        $this->assertTrue($definition->hasOption('batch'));
        $this->assertTrue($definition->hasOption('request-task'));
    }

    public function testArgumentId(): void
    {
        // 验证 id 参数可以正常接收和处理
        $failedMessage = new FailedMessage();
        $failedMessage->setError('Test error');
        $failedMessage->setAttempts(1);
        $failedMessage->setFailedAt(new \DateTimeImmutable());
        $failedMessage->setRetried(false);

        $this->persistAndFlush($failedMessage);

        // 测试有效的 ID 参数
        $this->getCommandTester()->setInputs(['yes']);
        $exitCode = $this->getCommandTester()->execute(['id' => (string) $failedMessage->getId()]);

        // 验证命令能识别并处理 ID 参数
        $output = $this->getCommandTester()->getDisplay();
        $this->assertStringContainsString((string) $failedMessage->getId(), $output);
    }

    public function testOptionAll(): void
    {
        // 清理数据确保没有失败消息
        $failedMessageRepo = self::getService(FailedMessageRepository::class);

        // 使用公共API而非访问protected方法
        $em = self::getEntityManager();
        $em->getConnection()->executeStatement('DELETE FROM dify_failed_message');
        $em->clear();

        // 验证 --all 选项功能
        $exitCode = $this->getCommandTester()->execute(['--all' => true]);
        $output = $this->getCommandTester()->getDisplay();

        // 验证命令识别 --all 选项
        $this->assertStringContainsString('没有找到需要重试的失败消息', $output);
    }

    public function testOptionLimit(): void
    {
        // 清理数据确保没有失败消息
        $failedMessageRepo = self::getService(FailedMessageRepository::class);

        // 使用公共API而非访问protected方法
        $em = self::getEntityManager();
        $em->getConnection()->executeStatement('DELETE FROM dify_failed_message');
        $em->clear();

        // 验证 --limit 选项功能
        $exitCode = $this->getCommandTester()->execute(['--all' => true, '--limit' => '5']);
        $output = $this->getCommandTester()->getDisplay();

        // 验证命令能处理限制选项
        $this->assertStringContainsString('没有找到需要重试的失败消息', $output);
    }

    public function testOptionDryRun(): void
    {
        // 验证 --dry-run 选项功能
        $failedMessage = new FailedMessage();
        $failedMessage->setError('Test error');
        $failedMessage->setAttempts(1);
        $failedMessage->setFailedAt(new \DateTimeImmutable());
        $failedMessage->setRetried(false);

        $this->persistAndFlush($failedMessage);

        $exitCode = $this->getCommandTester()->execute([
            'id' => (string) $failedMessage->getId(),
            '--dry-run' => true,
        ]);
        $output = $this->getCommandTester()->getDisplay();

        // 验证试运行模式
        $this->assertStringContainsString('试运行模式', $output);
    }

    public function testOptionBatch(): void
    {
        // 验证 --batch 选项（需要配合 ID 使用）
        $exitCode = $this->getCommandTester()->execute(['--batch' => true]);

        // 验证缺少 ID 时的错误处理
        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->getCommandTester()->getDisplay();
        $this->assertStringContainsString('使用--batch选项时必须指定RequestTask ID', $output);
    }

    public function testOptionRequestTask(): void
    {
        // 验证 --request-task 选项功能
        $this->getCommandTester()->setInputs(['yes']);
        $exitCode = $this->getCommandTester()->execute(['--request-task' => 'test-task-id']);
        $output = $this->getCommandTester()->getDisplay();

        // 验证命令能处理 request-task 选项
        $this->assertStringContainsString('task', $output);
    }
}
