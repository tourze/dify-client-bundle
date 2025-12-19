<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\WorkflowLog;
use Tourze\DifyClientBundle\Repository\WorkflowLogRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(WorkflowLogRepository::class)]
#[RunTestsInSeparateProcesses]
final class WorkflowLogRepositoryTest extends AbstractRepositoryTestCase
{
    private WorkflowLogRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(WorkflowLogRepository::class);
    }

    protected function getRepository(): WorkflowLogRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): WorkflowLog
    {
        $workflowLog = new WorkflowLog();
        $workflowLog->setNodeId('test-node-' . uniqid());
        $workflowLog->setNodeName('Test Node');
        $workflowLog->setNodeType('llm');
        $workflowLog->setLogLevel('info');
        $workflowLog->setMessage('Node executed successfully');
        $workflowLog->setLoggedAt(new \DateTimeImmutable());

        return $workflowLog;
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新工作流日志
        $workflowLog = $this->createNewEntity();

        // Act: 保存日志
        $this->repository->save($workflowLog);

        // Assert: 验证日志已持久化
        $this->assertNotNull($workflowLog->getId());
        $this->assertEntityPersisted($workflowLog);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化日志
        $workflowLog = $this->createNewEntity();
        $this->persistAndFlush($workflowLog);
        $logId = $workflowLog->getId();

        // Act: 删除日志
        $this->repository->remove($workflowLog);

        // Assert: 验证日志已删除
        $this->assertEntityNotExists(WorkflowLog::class, $logId);
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryHasCorrectEntityClass(): void
    {
        $this->assertSame(WorkflowLog::class, $this->repository->getClassName());
    }

    public function testGetEntityManagerShouldReturnEntityManagerInterface(): void
    {
        $em = self::getEntityManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $em);
    }

    public function testFindByLogLevel(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByWorkflowExecution(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindErrorLogs(): void
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
