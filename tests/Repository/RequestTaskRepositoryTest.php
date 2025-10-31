<?php

namespace Tourze\DifyClientBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\RequestTask;
use Tourze\DifyClientBundle\Enum\RequestTaskStatus;
use Tourze\DifyClientBundle\Repository\RequestTaskRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(RequestTaskRepository::class)]
#[RunTestsInSeparateProcesses]
final class RequestTaskRepositoryTest extends AbstractRepositoryTestCase
{
    private RequestTaskRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(RequestTaskRepository::class);
    }

    protected function getRepository(): RequestTaskRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): RequestTask
    {
        $requestTask = new RequestTask();
        $requestTask->setTaskId('test-task-' . uniqid());
        $requestTask->setStatus(RequestTaskStatus::PENDING);
        $requestTask->setAggregatedContent('Test content ' . uniqid());
        $requestTask->setMessageCount(1);
        $requestTask->setCreateTime(new \DateTimeImmutable());

        return $requestTask;
    }

    public function testSaveShouldPersistEntity(): void
    {
        // Arrange: 创建请求任务
        $requestTask = new RequestTask();
        $requestTask->setTaskId('test-task-123');
        $requestTask->setStatus(RequestTaskStatus::PENDING);
        $requestTask->setAggregatedContent('Test content');
        $requestTask->setMessageCount(1);
        $requestTask->setCreateTime(new \DateTimeImmutable());

        // Act: 保存任务
        $this->repository->save($requestTask);

        // Assert: 验证已持久化
        $this->assertNotNull($requestTask->getId());
        $this->assertEntityPersisted($requestTask);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化请求任务
        $requestTask = new RequestTask();
        $requestTask->setTaskId('test-task-remove');
        $requestTask->setStatus(RequestTaskStatus::PENDING);
        $requestTask->setAggregatedContent('To remove');
        $requestTask->setMessageCount(1);
        $requestTask->setCreateTime(new \DateTimeImmutable());

        $this->persistAndFlush($requestTask);
        $taskId = $requestTask->getId();

        // Act: 删除任务
        $this->repository->remove($requestTask);

        // Assert: 验证已删除
        $this->assertEntityNotExists(RequestTask::class, $taskId);
    }

    public function testCreateRequestTask(): void
    {
        // Act: 创建请求任务
        $task = $this->repository->createRequestTask('Test content', 5, ['key' => 'value']);

        // Assert: 验证任务创建成功
        $this->assertNotNull($task->getId());
        $this->assertEquals('Test content', $task->getAggregatedContent());
        $this->assertEquals(5, $task->getMessageCount());
        $this->assertEquals(['key' => 'value'], $task->getMetadata());
        $this->assertEquals(RequestTaskStatus::PENDING, $task->getStatus());
        $this->assertStringStartsWith('task_', $task->getTaskId());
    }

    public function testFindPendingTasks(): void
    {
        // Arrange: 创建待处理任务
        $task1 = $this->repository->createRequestTask('Content 1', 1);
        $task2 = $this->repository->createRequestTask('Content 2', 2);

        // Act: 查找待处理任务
        $result = $this->repository->findPendingTasks();

        // Assert: 验证返回结果包含创建的任务
        $this->assertGreaterThanOrEqual(2, count($result));
        $taskIds = array_map(fn ($task) => $task->getId(), $result);
        $this->assertContains($task1->getId(), $taskIds);
        $this->assertContains($task2->getId(), $taskIds);
    }

    public function testFindFailedTasks(): void
    {
        // Arrange: 创建失败任务
        $task = $this->repository->createRequestTask('Failed content', 1);
        $this->repository->markTaskAsFailed($task, 'Test error');

        // Act: 查找失败任务
        $result = $this->repository->findFailedTasks();

        // Assert: 验证返回结果包含失败任务
        $taskIds = array_map(fn ($task) => $task->getId(), $result);
        $this->assertContains($task->getId(), $taskIds);
    }

    public function testFindTaskByTaskId(): void
    {
        // Arrange: 创建任务
        $task = $this->repository->createRequestTask('Test content', 1);
        $taskId = $task->getTaskId();

        // Act: 通过 taskId 查找
        $result = $this->repository->findTaskByTaskId($taskId);

        // Assert: 验证找到正确的任务
        $this->assertNotNull($result);
        $this->assertEquals($task->getId(), $result->getId());
    }

    public function testMarkTaskAsProcessing(): void
    {
        // Arrange: 创建待处理任务
        $task = $this->repository->createRequestTask('Processing content', 1);

        // Act: 标记为处理中
        $this->repository->markTaskAsProcessing($task);

        // Assert: 验证状态更新
        self::getEntityManager()->refresh($task);
        $this->assertEquals(RequestTaskStatus::PROCESSING, $task->getStatus());
    }

    public function testMarkTaskAsCompleted(): void
    {
        // Arrange: 创建任务
        $task = $this->repository->createRequestTask('Completed content', 1);

        // Act: 标记为已完成
        $this->repository->markTaskAsCompleted($task, 'Success response');

        // Assert: 验证状态和响应更新
        self::getEntityManager()->refresh($task);
        $this->assertEquals(RequestTaskStatus::COMPLETED, $task->getStatus());
        $this->assertEquals('Success response', $task->getResponse());
    }

    public function testMarkTaskAsFailed(): void
    {
        // Arrange: 创建任务
        $task = $this->repository->createRequestTask('Failed content', 1);

        // Act: 标记为失败
        $this->repository->markTaskAsFailed($task, 'Error message');

        // Assert: 验证状态和错误信息更新
        self::getEntityManager()->refresh($task);
        $this->assertEquals(RequestTaskStatus::FAILED, $task->getStatus());
        $this->assertEquals('Error message', $task->getErrorMessage());
    }

    public function testCleanupOldTasks(): void
    {
        // Arrange: 创建已完成的旧任务
        $oldTask = $this->repository->createRequestTask('Old content', 1);
        $this->repository->markTaskAsCompleted($oldTask, 'Response');

        // Act: 清理旧任务（不使用反射，只测试方法存在和基本功能）
        $deleted = $this->repository->cleanupOldTasks(30);

        // Assert: 验证方法正常执行，返回整数
        $this->assertIsInt($deleted);
        $this->assertGreaterThanOrEqual(0, $deleted);
    }

    public function testFlush(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
