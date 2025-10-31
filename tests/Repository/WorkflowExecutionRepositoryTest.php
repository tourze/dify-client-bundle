<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\WorkflowExecution;
use Tourze\DifyClientBundle\Enum\WorkflowStatus;
use Tourze\DifyClientBundle\Repository\WorkflowExecutionRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(WorkflowExecutionRepository::class)]
#[RunTestsInSeparateProcesses]
final class WorkflowExecutionRepositoryTest extends AbstractRepositoryTestCase
{
    private WorkflowExecutionRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(WorkflowExecutionRepository::class);
    }

    protected function getRepository(): WorkflowExecutionRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): WorkflowExecution
    {
        $workflowExecution = new WorkflowExecution();
        $workflowExecution->setWorkflowRunId('test-workflow-run-' . uniqid());
        $workflowExecution->setWorkflowId('test-workflow-' . uniqid());
        $workflowExecution->setStatus(WorkflowStatus::RUNNING);
        $workflowExecution->setUserId('test-user');
        $workflowExecution->setInputs(['input' => 'test input']);

        return $workflowExecution;
    }

    public function testFindByWorkflowRunIdShouldReturnCorrectExecution(): void
    {
        // Arrange: 创建并持久化工作流执行
        $workflowRunId = 'test-workflow-run-id-' . uniqid();
        $workflowExecution = new WorkflowExecution();
        $workflowExecution->setWorkflowRunId($workflowRunId);
        $workflowExecution->setWorkflowId('workflow-123');
        $workflowExecution->setStatus(WorkflowStatus::COMPLETED);
        $workflowExecution->setUserId('user123');
        $workflowExecution->setInputs(['query' => 'hello world']);
        $this->persistAndFlush($workflowExecution);

        // Act: 根据工作流运行ID查找
        $foundExecution = $this->repository->findByWorkflowRunId($workflowRunId);

        // Assert: 验证找到正确的执行记录
        $this->assertNotNull($foundExecution);
        $this->assertSame($workflowRunId, $foundExecution->getWorkflowRunId());
        $this->assertSame('workflow-123', $foundExecution->getWorkflowId());
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新工作流执行
        $workflowExecution = $this->createNewEntity();

        // Act: 保存执行记录
        $this->repository->save($workflowExecution);

        // Assert: 验证执行记录已持久化
        $this->assertNotNull($workflowExecution->getId());
        $this->assertEntityPersisted($workflowExecution);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化执行记录
        $workflowExecution = $this->createNewEntity();
        $this->persistAndFlush($workflowExecution);
        $executionId = $workflowExecution->getId();

        // Act: 删除执行记录
        $this->repository->remove($workflowExecution);

        // Assert: 验证执行记录已删除
        $this->assertEntityNotExists(WorkflowExecution::class, $executionId);
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryHasCorrectEntityClass(): void
    {
        $this->assertSame(WorkflowExecution::class, $this->repository->getClassName());
    }

    public function testGetEntityManagerShouldReturnEntityManagerInterface(): void
    {
        $em = self::getEntityManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $em);
    }

    public function testFindByStatus(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByTaskId(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindRunningWorkflows(): void
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
