<?php

namespace Tourze\DifyClientBundle\Tests\Command\Support;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\Builder\InvocationMocker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\DifyClientBundle\Command\Support\FailedMessageDisplayer;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\FailedMessage;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\RequestTask;
use Tourze\DifyClientBundle\Enum\RequestTaskStatus;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(FailedMessageDisplayer::class)]
#[RunTestsInSeparateProcesses]
final class FailedMessageDisplayerTest extends AbstractIntegrationTestCase
{
    private FailedMessageDisplayer $displayer;

    private SymfonyStyle $io;

    protected function onSetUp(): void
    {
        // 从容器中获取服务实例，而不是直接实例化
        $this->displayer = self::getService(FailedMessageDisplayer::class);

        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $this->io = $this->createMock(SymfonyStyle::class);
    }

    /**
     * 验证表格行是否包含所有必需的字段
     *
     * @param mixed $rows
     */
    private function validateTableRows(mixed $rows): bool
    {
        if (!is_array($rows)) {
            return false;
        }

        $expectedFields = ['ID' => false, '错误信息' => false, '尝试次数' => false];

        foreach ($rows as $row) {
            if (!is_array($row) || !isset($row[0])) {
                continue;
            }

            $expectedFields = $this->markFieldIfMatches($expectedFields, $row);
        }

        return !in_array(false, $expectedFields, true);
    }

    /**
     * @param array<string, bool> $expectedFields
     * @param array<mixed> $row
     * @return array<string, bool>
     */
    private function markFieldIfMatches(array $expectedFields, array $row): array
    {
        $fieldName = $row[0];
        $fieldValue = $row[1] ?? null;

        if ('ID' === $fieldName) {
            $expectedFields['ID'] = true;
        }

        if ('错误信息' === $fieldName && 'Test error message' === $fieldValue) {
            $expectedFields['错误信息'] = true;
        }

        if ('尝试次数' === $fieldName && 3 === $fieldValue) {
            $expectedFields['尝试次数'] = true;
        }

        return $expectedFields;
    }

    /**
     * 验证RequestTask表格行
     *
     * @param mixed $rows
     */
    private function validateRequestTaskTableRows(mixed $rows): bool
    {
        if (!is_array($rows)) {
            return false;
        }

        $expectedFields = ['RequestTask ID' => false, '状态' => false, '消息数量' => false];

        foreach ($rows as $row) {
            if (!is_array($row) || !isset($row[0])) {
                continue;
            }

            $expectedFields = $this->markRequestTaskFieldIfMatches($expectedFields, $row);
        }

        return !in_array(false, $expectedFields, true);
    }

    /**
     * @param array<string, bool> $expectedFields
     * @param array<mixed> $row
     * @return array<string, bool>
     */
    private function markRequestTaskFieldIfMatches(array $expectedFields, array $row): array
    {
        $fieldName = $row[0];
        $fieldValue = $row[1] ?? null;

        if ('RequestTask ID' === $fieldName && 'task-123' === $fieldValue) {
            $expectedFields['RequestTask ID'] = true;
        }

        if ('状态' === $fieldName) {
            $expectedFields['状态'] = true;
        }

        if ('消息数量' === $fieldName && 5 === $fieldValue) {
            $expectedFields['消息数量'] = true;
        }

        return $expectedFields;
    }

    /**
     * 验证带会话ID的表格行
     *
     * @param mixed $rows
     */
    private function validateConversationTableRows(mixed $rows): bool
    {
        if (!is_array($rows)) {
            return false;
        }

        $hasId = false;
        $hasConversationId = false;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            if ('ID' === $row[0] && '123' === $row[1]) {
                $hasId = true;
            }
            if ('会话ID' === $row[0] && '456' === $row[1]) {
                $hasConversationId = true;
            }
        }

        return $hasId && $hasConversationId;
    }

    public function testDisplayMessageInfoShouldCallCorrectMethods(): void
    {
        // Arrange: 创建失败消息
        $failedMessage = new FailedMessage();
        $failedMessage->setError('Test error message');
        $failedMessage->setAttempts(3);
        $failedMessage->setFailedAt(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $failedMessage->setRetried(false);
        $failedMessage->setTaskId('task-123');

        // Mock SymfonyStyle 的方法调用
        /** @var InvocationMocker $expectation */
        $expectation = $this->io->expects($this->once());
        $expectation->method('horizontalTable')->with(
            ['属性', '值'],
            self::callback(fn ($rows) => $this->validateTableRows($rows))
        );

        // Act: 显示消息信息
        $this->displayer->displayMessageInfo($failedMessage, $this->io);
    }

    public function testDisplayMessageInfoWithRetryHistoryShouldShowHistory(): void
    {
        // Arrange: 创建有重试历史的失败消息
        $failedMessage = new FailedMessage();
        $failedMessage->setError('Test error');
        $failedMessage->setAttempts(2);
        $failedMessage->setRetryHistory([
            ['timestamp' => '2023-01-01 10:00:00', 'result' => 'failed'],
            ['timestamp' => '2023-01-01 11:00:00', 'result' => 'failed'],
        ]);

        // Mock SymfonyStyle 的方法调用
        /** @var InvocationMocker $horizontalTableExpectation */
        $horizontalTableExpectation = $this->io->expects($this->once());
        $horizontalTableExpectation->method('horizontalTable');

        /** @var InvocationMocker $sectionExpectation */
        $sectionExpectation = $this->io->expects($this->once());
        $sectionExpectation->method('section')->with('重试历史');

        /** @var InvocationMocker $textExpectation */
        $textExpectation = $this->io->expects($this->exactly(2));
        $textExpectation->method('text');

        // Act: 显示消息信息
        $this->displayer->displayMessageInfo($failedMessage, $this->io);
    }

    public function testDisplayMessageInfoWithoutRetryHistoryShouldNotShowHistory(): void
    {
        // Arrange: 创建没有重试历史的失败消息
        $failedMessage = new FailedMessage();
        $failedMessage->setError('Test error');
        $failedMessage->setAttempts(1);
        $failedMessage->setRetryHistory(null);

        // Mock SymfonyStyle 的方法调用
        /** @var InvocationMocker $horizontalTableExpectation */
        $horizontalTableExpectation = $this->io->expects($this->once());
        $horizontalTableExpectation->method('horizontalTable');

        /** @var InvocationMocker $sectionExpectation */
        $sectionExpectation = $this->io->expects($this->never());
        $sectionExpectation->method('section');

        // Act: 显示消息信息
        $this->displayer->displayMessageInfo($failedMessage, $this->io);
    }

    public function testDisplayRequestTaskInfoShouldCallCorrectMethods(): void
    {
        // Arrange: 创建请求任务
        $requestTask = new RequestTask();
        $requestTask->setTaskId('task-123');
        $requestTask->setStatus(RequestTaskStatus::COMPLETED);
        $requestTask->setMessageCount(5);
        $requestTask->setAggregatedContent('This is aggregated content for testing purposes');
        $requestTask->setCreateTime(new \DateTimeImmutable('2023-01-01 09:00:00'));
        $requestTask->setCompletedAt(new \DateTimeImmutable('2023-01-01 10:00:00'));

        // 创建空的消息和失败消息集合
        $messages = new ArrayCollection();
        $failedMessages = new ArrayCollection();

        // 使用反射设置私有属性（因为可能没有setter）
        $reflectionClass = new \ReflectionClass($requestTask);
        if ($reflectionClass->hasProperty('messages')) {
            $messagesProperty = $reflectionClass->getProperty('messages');
            $messagesProperty->setAccessible(true);
            $messagesProperty->setValue($requestTask, $messages);
        }
        if ($reflectionClass->hasProperty('failedMessages')) {
            $failedMessagesProperty = $reflectionClass->getProperty('failedMessages');
            $failedMessagesProperty->setAccessible(true);
            $failedMessagesProperty->setValue($requestTask, $failedMessages);
        }

        // Mock SymfonyStyle 的方法调用
        /** @var InvocationMocker $horizontalTableExpectation */
        $horizontalTableExpectation = $this->io->expects($this->once());
        $horizontalTableExpectation->method('horizontalTable')->with(
            ['属性', '值'],
            self::callback(fn ($rows) => $this->validateRequestTaskTableRows($rows))
        );

        // Act: 显示请求任务信息
        $this->displayer->displayRequestTaskInfo($requestTask, $this->io);
    }

    public function testDisplayRequestTaskInfoWithMessagesShouldShowMessages(): void
    {
        // Arrange: 创建带有消息的请求任务
        $requestTask = new RequestTask();
        $requestTask->setTaskId('task-with-messages');
        $requestTask->setStatus(RequestTaskStatus::COMPLETED);
        $requestTask->setMessageCount(2);
        $requestTask->setAggregatedContent('Test content');

        // 创建测试消息
        $message1 = new Message();
        $message1->setContent('This is the first message content for testing purposes');
        $message2 = new Message();
        $message2->setContent('This is the second message content for testing purposes');

        $messages = new ArrayCollection([$message1, $message2]);
        $failedMessages = new ArrayCollection();

        // 使用反射设置集合属性
        $reflectionClass = new \ReflectionClass($requestTask);
        if ($reflectionClass->hasProperty('messages')) {
            $messagesProperty = $reflectionClass->getProperty('messages');
            $messagesProperty->setAccessible(true);
            $messagesProperty->setValue($requestTask, $messages);
        }
        if ($reflectionClass->hasProperty('failedMessages')) {
            $failedMessagesProperty = $reflectionClass->getProperty('failedMessages');
            $failedMessagesProperty->setAccessible(true);
            $failedMessagesProperty->setValue($requestTask, $failedMessages);
        }

        // Mock SymfonyStyle 的方法调用
        /** @var InvocationMocker $horizontalTableExpectation */
        $horizontalTableExpectation = $this->io->expects($this->once());
        $horizontalTableExpectation->method('horizontalTable');

        /** @var InvocationMocker $sectionExpectation */
        $sectionExpectation = $this->io->expects($this->once());
        $sectionExpectation->method('section')->with('批次中的消息');

        /** @var InvocationMocker $textExpectation */
        $textExpectation = $this->io->expects($this->exactly(2));
        $textExpectation->method('text');

        // Act: 显示请求任务信息
        $this->displayer->displayRequestTaskInfo($requestTask, $this->io);
    }

    public function testDisplayRequestTaskInfoWithFailedMessagesShouldShowFailedMessages(): void
    {
        // Arrange: 创建带有失败消息的请求任务
        $requestTask = new RequestTask();
        $requestTask->setTaskId('task-with-failed');
        $requestTask->setStatus(RequestTaskStatus::FAILED);
        $requestTask->setMessageCount(0);
        $requestTask->setAggregatedContent('Test content');

        // 创建测试失败消息
        $failedMessage1 = new FailedMessage();
        $failedMessage1->setError('First error message occurred during processing');
        $failedMessage2 = new FailedMessage();
        $failedMessage2->setError('Second error message occurred during processing');

        // 使用反射设置ID（模拟从数据库加载的情况）
        $reflection1 = new \ReflectionClass($failedMessage1);
        if ($reflection1->hasProperty('id')) {
            $idProperty1 = $reflection1->getProperty('id');
            $idProperty1->setAccessible(true);
            $idProperty1->setValue($failedMessage1, 1);
        }

        $reflection2 = new \ReflectionClass($failedMessage2);
        if ($reflection2->hasProperty('id')) {
            $idProperty2 = $reflection2->getProperty('id');
            $idProperty2->setAccessible(true);
            $idProperty2->setValue($failedMessage2, 2);
        }

        $messages = new ArrayCollection();
        $failedMessages = new ArrayCollection([$failedMessage1, $failedMessage2]);

        // 使用反射设置集合属性
        $reflectionClass = new \ReflectionClass($requestTask);
        if ($reflectionClass->hasProperty('messages')) {
            $messagesProperty = $reflectionClass->getProperty('messages');
            $messagesProperty->setAccessible(true);
            $messagesProperty->setValue($requestTask, $messages);
        }
        if ($reflectionClass->hasProperty('failedMessages')) {
            $failedMessagesProperty = $reflectionClass->getProperty('failedMessages');
            $failedMessagesProperty->setAccessible(true);
            $failedMessagesProperty->setValue($requestTask, $failedMessages);
        }

        // Mock SymfonyStyle 的方法调用
        /** @var InvocationMocker $horizontalTableExpectation */
        $horizontalTableExpectation = $this->io->expects($this->once());
        $horizontalTableExpectation->method('horizontalTable');

        /** @var InvocationMocker $sectionExpectation */
        $sectionExpectation = $this->io->expects($this->once());
        $sectionExpectation->method('section')->with('关联的失败消息');

        /** @var InvocationMocker $textExpectation */
        $textExpectation = $this->io->expects($this->exactly(2));
        $textExpectation->method('text');

        // Act: 显示请求任务信息
        $this->displayer->displayRequestTaskInfo($requestTask, $this->io);
    }

    public function testDisplayMessageInfoWithConversationShouldShowConversationId(): void
    {
        // Arrange: 创建带有会话的失败消息
        $conversation = new Conversation();
        $conversation->setConversationId('conv-123');

        // 使用反射设置ID（Snowflake ID 是字符串类型）
        $reflection = new \ReflectionClass($conversation);
        if ($reflection->hasProperty('id')) {
            $idProperty = $reflection->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($conversation, '456');
        }

        $failedMessage = new FailedMessage();
        $failedMessage->setError('Test error');
        $failedMessage->setAttempts(1);
        $failedMessage->setConversation($conversation);

        // 使用反射设置 FailedMessage 的ID（Snowflake ID 是字符串类型）
        $failedReflection = new \ReflectionClass($failedMessage);
        if ($failedReflection->hasProperty('id')) {
            $failedIdProperty = $failedReflection->getProperty('id');
            $failedIdProperty->setAccessible(true);
            $failedIdProperty->setValue($failedMessage, '123');
        }

        // Mock SymfonyStyle 的方法调用
        /** @var InvocationMocker $horizontalTableExpectation */
        $horizontalTableExpectation = $this->io->expects($this->once());
        $horizontalTableExpectation->method('horizontalTable')->with(
            ['属性', '值'],
            self::callback(fn ($rows) => $this->validateConversationTableRows($rows))
        );

        // Act: 显示消息信息
        $this->displayer->displayMessageInfo($failedMessage, $this->io);
    }

    public function testClassCanBeInstantiated(): void
    {
        // Assert: 验证类可以被实例化
        $this->assertInstanceOf(FailedMessageDisplayer::class, $this->displayer);
    }

    public function testClassHasRequiredMethods(): void
    {
        // Act: 获取类的反射信息
        $reflection = new \ReflectionClass($this->displayer);

        // Assert: 验证所有必需的公共方法存在
        $this->assertTrue($reflection->hasMethod('displayMessageInfo'));
        $this->assertTrue($reflection->hasMethod('displayRequestTaskInfo'));

        // 验证方法的可见性
        $displayMessageMethod = $reflection->getMethod('displayMessageInfo');
        $displayRequestTaskMethod = $reflection->getMethod('displayRequestTaskInfo');

        $this->assertTrue($displayMessageMethod->isPublic());
        $this->assertTrue($displayRequestTaskMethod->isPublic());
    }
}
