<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\DifyClientBundle\Entity\WorkflowExecution;
use Tourze\DifyClientBundle\Entity\WorkflowLog;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;
use Tourze\DifyClientBundle\Repository\WorkflowExecutionRepository;
use Tourze\DifyClientBundle\Repository\WorkflowLogRepository;
use Tourze\DifyClientBundle\Repository\WorkflowTaskRepository;
use Tourze\DifyClientBundle\Service\WorkflowService;

/**
 * WorkflowService 测试类
 *
 * 测试工作流服务的核心功能
 * @internal
 */
#[CoversClass(WorkflowService::class)]
class WorkflowServiceTest extends TestCase
{
    private WorkflowService $workflowService;

    private HttpClientInterface&MockObject $httpClient;

    private DifySettingRepository&MockObject $settingRepository;

    private WorkflowExecutionRepository&MockObject $executionRepository;

    private WorkflowTaskRepository&MockObject $taskRepository;

    private WorkflowLogRepository&MockObject $logRepository;

    private ClockInterface&MockObject $clock;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->settingRepository = $this->createMock(DifySettingRepository::class);
        $this->executionRepository = $this->createMock(WorkflowExecutionRepository::class);
        $this->taskRepository = $this->createMock(WorkflowTaskRepository::class);
        $this->logRepository = $this->createMock(WorkflowLogRepository::class);
        $this->clock = $this->createMock(ClockInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->workflowService = new WorkflowService(
            $this->httpClient,
            $this->settingRepository,
            $this->executionRepository,
            $this->taskRepository,
            $this->logRepository,
            $this->clock,
            $entityManager
        );
    }

    public function testGetExecutionStatusShouldReturnCorrectExecution(): void
    {
        $executionId = 'exec-123';
        $mockExecution = $this->createMock(WorkflowExecution::class);

        $this->executionRepository
            ->expects($this->once())
            ->method('find')
            ->with($executionId)
            ->willReturn($mockExecution)
        ;

        $result = $this->workflowService->getExecutionStatus($executionId);

        $this->assertSame($mockExecution, $result);
    }

    public function testGetExecutionStatusShouldReturnNullWhenNotFound(): void
    {
        $executionId = 'non-existent-execution';

        $this->executionRepository
            ->expects($this->once())
            ->method('find')
            ->with($executionId)
            ->willReturn(null)
        ;

        $result = $this->workflowService->getExecutionStatus($executionId);

        $this->assertNull($result);
    }

    public function testGetWorkflowLogsShouldReturnCorrectLogs(): void
    {
        $mockExecution = $this->createMock(WorkflowExecution::class);
        $limit = 50;
        $offset = 10;
        $mockLogs = [$this->createMock(WorkflowLog::class)];

        $this->logRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(
                ['execution' => $mockExecution],
                ['createdAt' => 'ASC'],
                $limit,
                $offset
            )
            ->willReturn($mockLogs)
        ;

        $result = $this->workflowService->getWorkflowLogs($mockExecution, $limit, $offset);

        $this->assertSame($mockLogs, $result);
    }

    public function testGetUserExecutionsShouldReturnUserExecutions(): void
    {
        $userId = 'user-123';
        $limit = 20;
        $offset = 5;
        $mockExecutions = [$this->createMock(WorkflowExecution::class)];

        $this->executionRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(
                ['userId' => $userId],
                ['createdAt' => 'DESC'],
                $limit,
                $offset
            )
            ->willReturn($mockExecutions)
        ;

        $result = $this->workflowService->getUserExecutions($userId, $limit, $offset);

        $this->assertSame($mockExecutions, $result);
    }

    public function testGetWorkflowExecutionsShouldReturnWorkflowExecutions(): void
    {
        $workflowId = 'workflow-123';
        $limit = 30;
        $offset = 0;
        $mockExecutions = [$this->createMock(WorkflowExecution::class)];

        $this->executionRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(
                ['workflowId' => $workflowId],
                ['createdAt' => 'DESC'],
                $limit,
                $offset
            )
            ->willReturn($mockExecutions)
        ;

        $result = $this->workflowService->getWorkflowExecutions($workflowId, $limit, $offset);

        $this->assertSame($mockExecutions, $result);
    }

    public function testStopWorkflowTaskShouldThrowExceptionWhenExecutionNotFound(): void
    {
        $taskId = 'non-existent-task';

        $this->executionRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['taskId' => $taskId])
            ->willReturn(null)
        ;

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
