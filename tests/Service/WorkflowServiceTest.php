<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\WorkflowExecution;
use Tourze\DifyClientBundle\Entity\WorkflowLog;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Repository\WorkflowExecutionRepository;
use Tourze\DifyClientBundle\Repository\WorkflowLogRepository;
use Tourze\DifyClientBundle\Service\WorkflowService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * WorkflowService 测试类
 *
 * 测试工作流服务的核心功能
 * @internal
 */
#[CoversClass(WorkflowService::class)]
#[RunTestsInSeparateProcesses]
final class WorkflowServiceTest extends AbstractIntegrationTestCase
{
    private WorkflowService $workflowService;

    private WorkflowExecutionRepository $executionRepository;

    private WorkflowLogRepository $logRepository;

    protected function onSetUp(): void
    {
        $this->workflowService = self::getService(WorkflowService::class);
        $this->executionRepository = self::getService(WorkflowExecutionRepository::class);
        $this->logRepository = self::getService(WorkflowLogRepository::class);
    }

    public function testGetExecutionStatusShouldReturnCorrectExecution(): void
    {
        $em = self::getEntityManager();

        // 创建真实的 WorkflowExecution 实体
        $execution = new WorkflowExecution();
        $execution->setWorkflowRunId('run-' . uniqid());
        $execution->setWorkflowId('workflow-123');
        $execution->setUserId('test-user');
        $execution->setInputs([]);
        $execution->setBlocking(true);

        $em->persist($execution);
        $em->flush();

        $executionId = $execution->getId();

        $result = $this->workflowService->getExecutionStatus($executionId);

        $this->assertInstanceOf(WorkflowExecution::class, $result);
        $this->assertSame($executionId, $result->getId());
    }

    public function testGetExecutionStatusShouldReturnNullWhenNotFound(): void
    {
        $executionId = 'non-existent-execution';

        $result = $this->workflowService->getExecutionStatus($executionId);

        $this->assertNull($result);
    }

    public function testGetWorkflowLogsShouldReturnCorrectLogs(): void
    {
        $em = self::getEntityManager();

        // 创建真实的 WorkflowExecution 和 WorkflowLog
        $execution = new WorkflowExecution();
        $execution->setWorkflowRunId('run-' . uniqid());
        $execution->setWorkflowId('workflow-123');
        $execution->setUserId('test-user');
        $execution->setInputs([]);
        $execution->setBlocking(true);

        $em->persist($execution);

        $log = new WorkflowLog();
        $log->setExecution($execution);
        $log->setLevel('info');
        $log->setMessage('workflow.started');
        $log->setCategory('workflow');
        $log->setLoggedAt(new \DateTimeImmutable());

        $em->persist($log);
        $em->flush();

        $result = $this->workflowService->getWorkflowLogs($execution, 50, 0);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(WorkflowLog::class, $result[0]);
    }

    public function testGetUserExecutionsShouldReturnUserExecutions(): void
    {
        $em = self::getEntityManager();

        $userId = 'test-user-123';

        // 创建用户的执行记录
        $execution = new WorkflowExecution();
        $execution->setWorkflowRunId('run-' . uniqid());
        $execution->setWorkflowId('workflow-456');
        $execution->setUserId($userId);
        $execution->setInputs([]);
        $execution->setBlocking(true);

        $em->persist($execution);
        $em->flush();

        $result = $this->workflowService->getUserExecutions($userId, 20, 0);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertInstanceOf(WorkflowExecution::class, $result[0]);
        $this->assertSame($userId, $result[0]->getUserId());
    }

    public function testGetWorkflowExecutionsShouldReturnWorkflowExecutions(): void
    {
        $em = self::getEntityManager();

        $workflowId = 'workflow-789';

        // 创建工作流执行记录
        $execution = new WorkflowExecution();
        $execution->setWorkflowRunId('run-' . uniqid());
        $execution->setWorkflowId($workflowId);
        $execution->setUserId('test-user');
        $execution->setInputs([]);
        $execution->setBlocking(true);

        $em->persist($execution);
        $em->flush();

        $result = $this->workflowService->getWorkflowExecutions($workflowId, 30, 0);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertInstanceOf(WorkflowExecution::class, $result[0]);
        $this->assertSame($workflowId, $result[0]->getWorkflowId());
    }

    public function testStopWorkflowTaskShouldThrowExceptionWhenExecutionNotFound(): void
    {
        $taskId = 'non-existent-task';

        $this->expectException(DifyException::class);
        $this->expectExceptionMessage('Workflow execution not found for task: ' . $taskId);

        $this->workflowService->stopWorkflowTask($taskId);
    }

    public function testAddWorkflowLogMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->workflowService);
        $this->assertTrue($reflection->hasMethod('addWorkflowLog'));
        $method = $reflection->getMethod('addWorkflowLog');
        $this->assertTrue($method->isPublic());
    }

    public function testExecuteWorkflowMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->workflowService);
        $this->assertTrue($reflection->hasMethod('executeWorkflow'));
        $method = $reflection->getMethod('executeWorkflow');
        $this->assertTrue($method->isPublic());
    }

    public function testExecuteWorkflowStreamMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->workflowService);
        $this->assertTrue($reflection->hasMethod('executeWorkflowStream'));
        $method = $reflection->getMethod('executeWorkflowStream');
        $this->assertTrue($method->isPublic());
    }

    public function testRunWorkflowMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->workflowService);
        $this->assertTrue($reflection->hasMethod('runWorkflow'));
        $method = $reflection->getMethod('runWorkflow');
        $this->assertTrue($method->isPublic());
    }

    public function testRetryExecutionMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->workflowService);
        $this->assertTrue($reflection->hasMethod('retryExecution'));
        $method = $reflection->getMethod('retryExecution');
        $this->assertTrue($method->isPublic());
    }

    public function testCleanupOldExecutionsMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->workflowService);
        $this->assertTrue($reflection->hasMethod('cleanupOldExecutions'));
        $method = $reflection->getMethod('cleanupOldExecutions');
        $this->assertTrue($method->isPublic());
    }
}
