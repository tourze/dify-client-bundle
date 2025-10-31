<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\FailedMessage;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\RequestTask;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(FailedMessage::class)]
final class FailedMessageTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): FailedMessage
    {
        return new FailedMessage();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'error' => ['error', 'Test error message'];
        yield 'retried' => ['retried', false];
        yield 'attempts' => ['attempts', 0];
    }

    private function createConversation(): Conversation
    {
        return new Conversation();
    }

    private function createMessage(): Message
    {
        return new Message();
    }

    private function createRequestTask(): RequestTask
    {
        return new RequestTask();
    }

    public function testCreateFailedMessageWithDefaultValuesShouldSucceed(): void
    {
        $failedMessage = $this->createEntity();

        $this->assertNull($failedMessage->getId());
        $this->assertNull($failedMessage->getConversation());
        $this->assertNull($failedMessage->getMessage());
        $this->assertNull($failedMessage->getRequestTask());
        $this->assertNull($failedMessage->getFailedAt());
        $this->assertNull($failedMessage->getContext());
        $this->assertFalse($failedMessage->isRetried());
        $this->assertNull($failedMessage->getTaskId());
        $this->assertNull($failedMessage->getRetryHistory());
    }

    public function testSetConversationShouldUpdateValue(): void
    {
        $failedMessage = $this->createEntity();
        $conversation = $this->createConversation();

        $failedMessage->setConversation($conversation);

        $this->assertSame($conversation, $failedMessage->getConversation());
    }

    public function testSetConversationWithNullShouldAcceptNull(): void
    {
        $failedMessage = $this->createEntity();
        $failedMessage->setConversation(new Conversation());

        $failedMessage->setConversation(null);

        $this->assertNull($failedMessage->getConversation());
    }

    public function testSetMessageShouldUpdateValue(): void
    {
        $failedMessage = $this->createEntity();
        $message = $this->createMessage();

        $failedMessage->setMessage($message);

        $this->assertSame($message, $failedMessage->getMessage());
    }

    public function testSetMessageWithNullShouldAcceptNull(): void
    {
        $failedMessage = $this->createEntity();
        $failedMessage->setMessage(new Message());

        $failedMessage->setMessage(null);

        $this->assertNull($failedMessage->getMessage());
    }

    public function testSetErrorShouldUpdateValue(): void
    {
        $failedMessage = $this->createEntity();
        $error = 'Connection timeout after 30 seconds';

        $failedMessage->setError($error);

        $this->assertEquals($error, $failedMessage->getError());
    }

    public function testSetErrorWithLongMessageShouldWork(): void
    {
        $failedMessage = $this->createEntity();
        $longError = str_repeat('很长的错误信息描述。', 500);

        $failedMessage->setError($longError);

        $this->assertEquals($longError, $failedMessage->getError());
    }

    public function testSetAttemptsShouldUpdateValue(): void
    {
        $failedMessage = $this->createEntity();
        $attempts = 3;

        $failedMessage->setAttempts($attempts);

        $this->assertEquals($attempts, $failedMessage->getAttempts());
    }

    public function testSetAttemptsWithZeroShouldWork(): void
    {
        $failedMessage = $this->createEntity();

        $failedMessage->setAttempts(0);

        $this->assertEquals(0, $failedMessage->getAttempts());
    }

    public function testSetFailedAtShouldUpdateValue(): void
    {
        $failedMessage = $this->createEntity();
        $failedAt = new \DateTimeImmutable('2024-01-01 10:00:00');

        $failedMessage->setFailedAt($failedAt);

        $this->assertEquals($failedAt, $failedMessage->getFailedAt());
    }

    public function testSetFailedAtWithNullShouldAcceptNull(): void
    {
        $failedMessage = $this->createEntity();
        $failedMessage->setFailedAt(new \DateTimeImmutable());

        $failedMessage->setFailedAt(null);

        $this->assertNull($failedMessage->getFailedAt());
    }

    public function testSetContextShouldUpdateValue(): void
    {
        $failedMessage = $this->createEntity();
        $context = [
            'exception_class' => 'TimeoutException',
            'exception_code' => 408,
            'request_url' => 'https://api.dify.ai/v1/chat-messages',
            'request_method' => 'POST',
        ];

        $failedMessage->setContext($context);

        $this->assertEquals($context, $failedMessage->getContext());
    }

    public function testSetContextWithNullShouldAcceptNull(): void
    {
        $failedMessage = $this->createEntity();
        $failedMessage->setContext(['test' => 'value']);

        $failedMessage->setContext(null);

        $this->assertNull($failedMessage->getContext());
    }

    public function testSetRetriedShouldUpdateValue(): void
    {
        $failedMessage = $this->createEntity();

        $failedMessage->setRetried(true);

        $this->assertTrue($failedMessage->isRetried());
    }

    public function testSetRetriedWithFalseShouldWork(): void
    {
        $failedMessage = $this->createEntity();
        $failedMessage->setRetried(true);

        $failedMessage->setRetried(false);

        $this->assertFalse($failedMessage->isRetried());
    }

    public function testSetTaskIdShouldUpdateValue(): void
    {
        $failedMessage = $this->createEntity();
        $taskId = 'task-12345-67890';

        $failedMessage->setTaskId($taskId);

        $this->assertEquals($taskId, $failedMessage->getTaskId());
    }

    public function testSetTaskIdWithNullShouldAcceptNull(): void
    {
        $failedMessage = $this->createEntity();
        $failedMessage->setTaskId('previous-task-id');

        $failedMessage->setTaskId(null);

        $this->assertNull($failedMessage->getTaskId());
    }

    public function testSetRetryHistoryShouldUpdateValue(): void
    {
        $failedMessage = $this->createEntity();
        $retryHistory = [
            [
                'timestamp' => '2024-01-01T10:00:00+00:00',
                'result' => 'failed: timeout',
            ],
            [
                'timestamp' => '2024-01-01T10:05:00+00:00',
                'result' => 'failed: connection refused',
            ],
        ];

        $failedMessage->setRetryHistory($retryHistory);

        $this->assertEquals($retryHistory, $failedMessage->getRetryHistory());
    }

    public function testSetRetryHistoryWithNullShouldAcceptNull(): void
    {
        $failedMessage = $this->createEntity();
        $failedMessage->setRetryHistory([['test' => 'data']]);

        $failedMessage->setRetryHistory(null);

        $this->assertNull($failedMessage->getRetryHistory());
    }

    public function testAddRetryAttemptShouldAppendToHistory(): void
    {
        $failedMessage = $this->createEntity();
        $retryTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $result = 'failed: connection timeout';

        $failedMessage->addRetryAttempt($retryTime, $result);

        $history = $failedMessage->getRetryHistory();
        $this->assertNotNull($history);
        $this->assertCount(1, $history);
        $this->assertEquals($retryTime->format(\DateTimeInterface::ATOM), $history[0]['timestamp']);
        $this->assertEquals($result, $history[0]['result']);
    }

    public function testAddRetryAttemptToExistingHistoryShouldAppend(): void
    {
        $failedMessage = $this->createEntity();
        $existingHistory = [
            [
                'timestamp' => '2024-01-01T09:00:00+00:00',
                'result' => 'failed: first attempt',
            ],
        ];
        $failedMessage->setRetryHistory($existingHistory);

        $retryTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $result = 'failed: second attempt';

        $failedMessage->addRetryAttempt($retryTime, $result);

        $history = $failedMessage->getRetryHistory();
        $this->assertNotNull($history);
        $this->assertCount(2, $history);
        $this->assertEquals('failed: first attempt', $history[0]['result']);
        $this->assertEquals('failed: second attempt', $history[1]['result']);
    }

    public function testSetRequestTaskShouldUpdateValue(): void
    {
        $failedMessage = $this->createEntity();
        $requestTask = $this->createRequestTask();

        $failedMessage->setRequestTask($requestTask);

        $this->assertSame($requestTask, $failedMessage->getRequestTask());
    }

    public function testSetRequestTaskWithNullShouldAcceptNull(): void
    {
        $failedMessage = $this->createEntity();
        $failedMessage->setRequestTask(new RequestTask());

        $failedMessage->setRequestTask(null);

        $this->assertNull($failedMessage->getRequestTask());
    }

    public function testToStringWithIdShouldReturnFormattedString(): void
    {
        $failedMessage = $this->createEntity();

        // 使用反射设置id
        $reflection = new \ReflectionClass($failedMessage);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($failedMessage, 'failed-msg-123');

        $failedMessage->setAttempts(3);

        $result = (string) $failedMessage;

        $this->assertEquals('失败消息 #failed-msg-123 (3次尝试)', $result);
    }

    public function testCompleteFailedMessageWorkflowShouldWork(): void
    {
        $failedMessage = $this->createEntity();
        $conversation = $this->createConversation();
        $message = $this->createMessage();
        $requestTask = $this->createRequestTask();

        $failedMessage->setConversation($conversation);
        $failedMessage->setMessage($message);
        $failedMessage->setRequestTask($requestTask);
        $failedMessage->setError('API connection failed');
        $failedMessage->setAttempts(2);
        $failedMessage->setFailedAt(new \DateTimeImmutable());
        $failedMessage->setContext([
            'exception_class' => 'ConnectionException',
            'http_code' => 500,
        ]);
        $failedMessage->setTaskId('messenger-task-123');
        $failedMessage->setRetried(false);

        // 添加重试记录
        $failedMessage->addRetryAttempt(
            new \DateTimeImmutable('2024-01-01 10:00:00'),
            'failed: timeout'
        );
        $failedMessage->addRetryAttempt(
            new \DateTimeImmutable('2024-01-01 10:05:00'),
            'failed: server error'
        );

        $this->assertSame($conversation, $failedMessage->getConversation());
        $this->assertSame($message, $failedMessage->getMessage());
        $this->assertSame($requestTask, $failedMessage->getRequestTask());
        $this->assertEquals('API connection failed', $failedMessage->getError());
        $this->assertEquals(2, $failedMessage->getAttempts());
        $this->assertFalse($failedMessage->isRetried());
        $this->assertEquals('messenger-task-123', $failedMessage->getTaskId());
        $history = $failedMessage->getRetryHistory();
        $this->assertNotNull($history);
        $this->assertCount(2, $history);
    }
}
