<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\FailedMessage;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\RequestTask;
use Tourze\DifyClientBundle\Enum\RequestTaskStatus;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(RequestTask::class)]
final class RequestTaskTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): RequestTask
    {
        return new RequestTask();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'status' => ['status', RequestTaskStatus::PENDING];
        yield 'messageCount' => ['messageCount', 5];
    }

    public function testCreateRequestTaskWithDefaultValuesShouldSucceed(): void
    {
        $requestTask = $this->createEntity();

        $this->assertNull($requestTask->getId());
        $this->assertEquals(RequestTaskStatus::PENDING, $requestTask->getStatus());
        $this->assertEquals(0, $requestTask->getMessageCount());
        $this->assertNull($requestTask->getMetadata());
        $this->assertNull($requestTask->getProcessedAt());
        $this->assertNull($requestTask->getCompletedAt());
        $this->assertNull($requestTask->getResponse());
        $this->assertNull($requestTask->getErrorMessage());
        $this->assertEmpty($requestTask->getMessages());
        $this->assertEmpty($requestTask->getFailedMessages());
    }

    public function testSetTaskIdShouldUpdateValue(): void
    {
        $requestTask = $this->createEntity();
        $taskId = 'task-12345-67890';

        $requestTask->setTaskId($taskId);

        $this->assertEquals($taskId, $requestTask->getTaskId());
    }

    public function testSetStatusShouldUpdateValue(): void
    {
        $requestTask = $this->createEntity();

        $requestTask->setStatus(RequestTaskStatus::PROCESSING);

        $this->assertEquals(RequestTaskStatus::PROCESSING, $requestTask->getStatus());
    }

    public function testSetStatusWithAllStatusesShouldWork(): void
    {
        $requestTask = $this->createEntity();

        foreach (RequestTaskStatus::cases() as $status) {
            $requestTask->setStatus($status);
            $this->assertEquals($status, $requestTask->getStatus());
        }
    }

    public function testSetAggregatedContentShouldUpdateValue(): void
    {
        $requestTask = $this->createEntity();
        $content = '这是聚合后的消息内容，包含了多条消息的合并文本';

        $requestTask->setAggregatedContent($content);

        $this->assertEquals($content, $requestTask->getAggregatedContent());
    }

    public function testSetAggregatedContentWithLongTextShouldWork(): void
    {
        $requestTask = $this->createEntity();
        $longContent = str_repeat('这是一个很长的聚合消息内容。', 1000);

        $requestTask->setAggregatedContent($longContent);

        $this->assertEquals($longContent, $requestTask->getAggregatedContent());
    }

    public function testSetMessageCountShouldUpdateValue(): void
    {
        $requestTask = $this->createEntity();
        $messageCount = 5;

        $requestTask->setMessageCount($messageCount);

        $this->assertEquals($messageCount, $requestTask->getMessageCount());
    }

    public function testSetMessageCountWithZeroShouldWork(): void
    {
        $requestTask = $this->createEntity();

        $requestTask->setMessageCount(0);

        $this->assertEquals(0, $requestTask->getMessageCount());
    }

    public function testSetMessageCountWithMaximumValueShouldWork(): void
    {
        $requestTask = $this->createEntity();

        $requestTask->setMessageCount(1000);

        $this->assertEquals(1000, $requestTask->getMessageCount());
    }

    public function testSetMetadataShouldUpdateValue(): void
    {
        $requestTask = $this->createEntity();
        $metadata = [
            'conversation_id' => 'conv-123',
            'window_start' => '2024-01-01T10:00:00Z',
            'aggregation_strategy' => 'time_based',
        ];

        $requestTask->setMetadata($metadata);

        $this->assertEquals($metadata, $requestTask->getMetadata());
    }

    public function testSetMetadataWithNullShouldAcceptNull(): void
    {
        $requestTask = $this->createEntity();
        $requestTask->setMetadata(['test' => 'value']);

        $requestTask->setMetadata(null);

        $this->assertNull($requestTask->getMetadata());
    }

    public function testSetProcessedAtShouldUpdateValue(): void
    {
        $requestTask = $this->createEntity();
        $processedAt = new \DateTimeImmutable('2024-01-01 10:00:00');

        $requestTask->setProcessedAt($processedAt);

        $this->assertEquals($processedAt, $requestTask->getProcessedAt());
    }

    public function testSetProcessedAtWithNullShouldAcceptNull(): void
    {
        $requestTask = $this->createEntity();
        $requestTask->setProcessedAt(new \DateTimeImmutable());

        $requestTask->setProcessedAt(null);

        $this->assertNull($requestTask->getProcessedAt());
    }

    public function testSetCompletedAtShouldUpdateValue(): void
    {
        $requestTask = $this->createEntity();
        $completedAt = new \DateTimeImmutable('2024-01-01 10:05:00');

        $requestTask->setCompletedAt($completedAt);

        $this->assertEquals($completedAt, $requestTask->getCompletedAt());
    }

    public function testSetCompletedAtWithNullShouldAcceptNull(): void
    {
        $requestTask = $this->createEntity();
        $requestTask->setCompletedAt(new \DateTimeImmutable());

        $requestTask->setCompletedAt(null);

        $this->assertNull($requestTask->getCompletedAt());
    }

    public function testSetResponseShouldUpdateValue(): void
    {
        $requestTask = $this->createEntity();
        $response = '这是Dify AI返回的响应内容，包含了回答用户问题的完整信息';

        $requestTask->setResponse($response);

        $this->assertEquals($response, $requestTask->getResponse());
    }

    public function testSetResponseWithNullShouldAcceptNull(): void
    {
        $requestTask = $this->createEntity();
        $requestTask->setResponse('Previous response');

        $requestTask->setResponse(null);

        $this->assertNull($requestTask->getResponse());
    }

    public function testSetErrorMessageShouldUpdateValue(): void
    {
        $requestTask = $this->createEntity();
        $errorMessage = 'API request failed: connection timeout after 30 seconds';

        $requestTask->setErrorMessage($errorMessage);

        $this->assertEquals($errorMessage, $requestTask->getErrorMessage());
    }

    public function testSetErrorMessageWithNullShouldAcceptNull(): void
    {
        $requestTask = $this->createEntity();
        $requestTask->setErrorMessage('Previous error');

        $requestTask->setErrorMessage(null);

        $this->assertNull($requestTask->getErrorMessage());
    }

    public function testAddMessageShouldAddNewMessage(): void
    {
        $requestTask = $this->createEntity();
        $message = new Message();

        $requestTask->addMessage($message);

        $this->assertTrue($requestTask->getMessages()->contains($message));
        $this->assertSame($requestTask, $message->getRequestTask());
    }

    public function testAddMessageWithExistingMessageShouldNotDuplicate(): void
    {
        $requestTask = $this->createEntity();
        $message = new Message();

        $requestTask->addMessage($message);
        $requestTask->addMessage($message);

        $this->assertCount(1, $requestTask->getMessages());
        $this->assertTrue($requestTask->getMessages()->contains($message));
    }

    public function testRemoveMessageShouldRemoveExistingMessage(): void
    {
        $requestTask = $this->createEntity();
        $message = new Message();

        $requestTask->addMessage($message);
        $requestTask->removeMessage($message);

        $this->assertFalse($requestTask->getMessages()->contains($message));
        $this->assertNull($message->getRequestTask());
    }

    public function testRemoveMessageWithNonExistingMessageShouldNotCauseError(): void
    {
        $requestTask = $this->createEntity();
        $message = new Message();

        $requestTask->removeMessage($message);

        $this->assertFalse($requestTask->getMessages()->contains($message));
    }

    public function testAddFailedMessageShouldAddNewFailedMessage(): void
    {
        $requestTask = $this->createEntity();
        $failedMessage = new FailedMessage();

        $requestTask->addFailedMessage($failedMessage);

        $this->assertTrue($requestTask->getFailedMessages()->contains($failedMessage));
        $this->assertSame($requestTask, $failedMessage->getRequestTask());
    }

    public function testAddFailedMessageWithExistingFailedMessageShouldNotDuplicate(): void
    {
        $requestTask = $this->createEntity();
        $failedMessage = new FailedMessage();

        $requestTask->addFailedMessage($failedMessage);
        $requestTask->addFailedMessage($failedMessage);

        $this->assertCount(1, $requestTask->getFailedMessages());
        $this->assertTrue($requestTask->getFailedMessages()->contains($failedMessage));
    }

    public function testRemoveFailedMessageShouldRemoveExistingFailedMessage(): void
    {
        $requestTask = $this->createEntity();
        $failedMessage = new FailedMessage();

        $requestTask->addFailedMessage($failedMessage);
        $requestTask->removeFailedMessage($failedMessage);

        $this->assertFalse($requestTask->getFailedMessages()->contains($failedMessage));
        $this->assertNull($failedMessage->getRequestTask());
    }

    public function testRemoveFailedMessageWithNonExistingFailedMessageShouldNotCauseError(): void
    {
        $requestTask = $this->createEntity();
        $failedMessage = new FailedMessage();

        $requestTask->removeFailedMessage($failedMessage);

        $this->assertFalse($requestTask->getFailedMessages()->contains($failedMessage));
    }

    public function testMarkAsProcessedShouldUpdateStatusAndTime(): void
    {
        $requestTask = $this->createEntity();

        $requestTask->markAsProcessed();

        $this->assertEquals(RequestTaskStatus::PROCESSING, $requestTask->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $requestTask->getProcessedAt());
    }

    public function testMarkAsCompletedShouldUpdateStatusTimeAndResponse(): void
    {
        $requestTask = $this->createEntity();
        $response = 'Task completed successfully with AI response';

        $requestTask->markAsCompleted($response);

        $this->assertEquals(RequestTaskStatus::COMPLETED, $requestTask->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $requestTask->getCompletedAt());
        $this->assertEquals($response, $requestTask->getResponse());
    }

    public function testMarkAsFailedShouldUpdateStatusTimeAndError(): void
    {
        $requestTask = $this->createEntity();
        $errorMessage = 'Task failed due to API timeout';

        $requestTask->markAsFailed($errorMessage);

        $this->assertEquals(RequestTaskStatus::FAILED, $requestTask->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $requestTask->getCompletedAt());
        $this->assertEquals($errorMessage, $requestTask->getErrorMessage());
    }

    public function testIsRetriableWithFailedStatusShouldReturnTrue(): void
    {
        $requestTask = $this->createEntity();
        $requestTask->setStatus(RequestTaskStatus::FAILED);

        $result = $requestTask->isRetriable();

        $this->assertTrue($result);
    }

    public function testIsRetriableWithTimeoutStatusShouldReturnTrue(): void
    {
        $requestTask = $this->createEntity();
        $requestTask->setStatus(RequestTaskStatus::TIMEOUT);

        $result = $requestTask->isRetriable();

        $this->assertTrue($result);
    }

    public function testIsRetriableWithCompletedStatusShouldReturnFalse(): void
    {
        $requestTask = $this->createEntity();
        $requestTask->setStatus(RequestTaskStatus::COMPLETED);

        $result = $requestTask->isRetriable();

        $this->assertFalse($result);
    }

    public function testIsRetriableWithProcessingStatusShouldReturnFalse(): void
    {
        $requestTask = $this->createEntity();
        $requestTask->setStatus(RequestTaskStatus::PROCESSING);

        $result = $requestTask->isRetriable();

        $this->assertFalse($result);
    }

    public function testToStringShouldReturnFormattedString(): void
    {
        $requestTask = $this->createEntity();
        $requestTask->setTaskId('task-abc-123');
        $requestTask->setMessageCount(5);

        $result = (string) $requestTask;

        $this->assertEquals('任务 task-abc-123 (5条消息)', $result);
    }

    public function testCompleteTaskWorkflowShouldWork(): void
    {
        $requestTask = $this->createEntity();

        // 创建任务
        $requestTask->setTaskId('workflow-test-task');
        $requestTask->setAggregatedContent('聚合的消息内容');
        $requestTask->setMessageCount(3);
        $requestTask->setMetadata([
            'conversation_id' => 'conv-test',
            'batch_id' => 'batch-001',
        ]);

        // 开始处理
        $requestTask->markAsProcessed();
        $this->assertEquals(RequestTaskStatus::PROCESSING, $requestTask->getStatus());

        // 完成任务
        $response = 'AI处理完成的响应内容';
        $requestTask->markAsCompleted($response);

        $this->assertEquals(RequestTaskStatus::COMPLETED, $requestTask->getStatus());
        $this->assertEquals($response, $requestTask->getResponse());
        $this->assertInstanceOf(\DateTimeImmutable::class, $requestTask->getCompletedAt());
        $this->assertFalse($requestTask->isRetriable());
    }

    public function testFailedTaskWorkflowShouldWork(): void
    {
        $requestTask = $this->createEntity();

        // 创建任务
        $requestTask->setTaskId('failed-workflow-task');
        $requestTask->setAggregatedContent('会失败的消息内容');
        $requestTask->setMessageCount(2);

        // 开始处理
        $requestTask->markAsProcessed();

        // 任务失败
        $errorMessage = '网络连接超时，无法完成请求';
        $requestTask->markAsFailed($errorMessage);

        $this->assertEquals(RequestTaskStatus::FAILED, $requestTask->getStatus());
        $this->assertEquals($errorMessage, $requestTask->getErrorMessage());
        $this->assertInstanceOf(\DateTimeImmutable::class, $requestTask->getCompletedAt());
        $this->assertTrue($requestTask->isRetriable());
    }
}
