<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\WorkflowTask;
use Tourze\DifyClientBundle\Repository\WorkflowTaskRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(WorkflowTaskRepository::class)]
#[RunTestsInSeparateProcesses]
final class WorkflowTaskRepositoryTest extends AbstractRepositoryTestCase
{
    private WorkflowTaskRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(WorkflowTaskRepository::class);
    }

    protected function getRepository(): WorkflowTaskRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): WorkflowTask
    {
        $workflowTask = new WorkflowTask();
        $workflowTask->setTaskId('test-task-' . uniqid());
        $workflowTask->setWorkflowRunId('test-workflow-run-' . uniqid());
        $workflowTask->setTaskName('Test Task');
        $workflowTask->setTaskType('llm');
        $workflowTask->setStatus('running');
        $workflowTask->setInputData(['input' => 'test input']);
        $workflowTask->setUserId('test-user');

        // 设置必需的字段
        $workflowTask->setNodeId('node-' . uniqid());
        $workflowTask->setStepIndex(1);

        return $workflowTask;
    }

    public function testFindByTaskIdShouldReturnCorrectTask(): void
    {
        // Arrange: 创建并持久化工作流任务
        $taskId = 'test-task-id-' . uniqid();
        $workflowTask = new WorkflowTask();
        $workflowTask->setTaskId($taskId);
        $workflowTask->setWorkflowRunId('workflow-run-123');
        $workflowTask->setTaskName('LLM Task');
        $workflowTask->setTaskType('llm');
        $workflowTask->setStatus('completed');
        $workflowTask->setInputData(['prompt' => 'Hello AI']);
        $workflowTask->setOutputData(['response' => 'Hello Human']);
        $workflowTask->setUserId('user123');

        // 设置必需的字段
        $workflowTask->setNodeId('node-' . uniqid());
        $workflowTask->setStepIndex(1);
        $this->persistAndFlush($workflowTask);

        // Act: 根据任务ID查找
        $foundTask = $this->repository->findByTaskId($taskId);

        // Assert: 验证找到正确的任务
        $this->assertNotNull($foundTask);
        $this->assertSame($taskId, $foundTask->getTaskId());
        $this->assertSame('LLM Task', $foundTask->getNodeName());
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新工作流任务
        $workflowTask = $this->createNewEntity();

        // Act: 保存任务
        $this->repository->save($workflowTask);

        // Assert: 验证任务已持久化
        $this->assertNotNull($workflowTask->getId());
        $this->assertEntityPersisted($workflowTask);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化任务
        $workflowTask = $this->createNewEntity();
        $this->persistAndFlush($workflowTask);
        $taskId = $workflowTask->getId();

        // Act: 删除任务
        $this->repository->remove($workflowTask);

        // Assert: 验证任务已删除
        $this->assertEntityNotExists(WorkflowTask::class, $taskId);
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryHasCorrectEntityClass(): void
    {
        $this->assertSame(WorkflowTask::class, $this->repository->getClassName());
    }

    public function testGetEntityManagerShouldReturnEntityManagerInterface(): void
    {
        $em = self::getEntityManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $em);
    }

    public function testFindByNodeId(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByStatus(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByWorkflowExecution(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFlush(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
