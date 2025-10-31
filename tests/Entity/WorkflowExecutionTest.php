<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\WorkflowExecution;
use Tourze\DifyClientBundle\Entity\WorkflowTask;
use Tourze\DifyClientBundle\Enum\WorkflowStatus;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(WorkflowExecution::class)]
final class WorkflowExecutionTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): WorkflowExecution
    {
        return new WorkflowExecution();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'workflowRunId' => ['workflowRunId', 'run-12345'];
        yield 'taskId' => ['taskId', 'task-12345'];
        yield 'workflowId' => ['workflowId', 'workflow-12345'];
        yield 'blocking' => ['blocking', true];
        yield 'executionId' => ['executionId', 'exec-12345'];
        yield 'userId' => ['userId', 'user-123'];
        yield 'totalSteps' => ['totalSteps', 5];
        yield 'completedSteps' => ['completedSteps', 3];
        yield 'elapsedTime' => ['elapsedTime', 12.5];
        yield 'errorMessage' => ['errorMessage', '工作流执行失败：步骤2超时'];
        yield 'totalTokens' => ['totalTokens', 1500];
    }

    public function testCreateWorkflowExecutionWithDefaultValuesShouldSucceed(): void
    {
        $execution = $this->createEntity();

        $this->assertNull($execution->getId());
        $this->assertNull($execution->getTaskId());
        $this->assertNull($execution->getWorkflowId());
        $this->assertFalse($execution->isBlocking());
        $this->assertNull($execution->getExecutionId());
        $this->assertEquals(WorkflowStatus::PENDING, $execution->getStatus());
        $this->assertNull($execution->getInputs());
        $this->assertNull($execution->getOutputs());
        $this->assertNull($execution->getUserId());
        $this->assertNull($execution->getTotalSteps());
        $this->assertEquals(0, $execution->getCompletedSteps());
        $this->assertNull($execution->getElapsedTime());
        $this->assertNull($execution->getErrorMessage());
        $this->assertNull($execution->getMetadata());
        $this->assertNull($execution->getStartedAt());
        $this->assertNull($execution->getFinishedAt());
        $this->assertNull($execution->getTotalTokens());
        $this->assertEmpty($execution->getTasks());
    }

    public function testSetWorkflowRunIdShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $workflowRunId = 'run-67890';

        $execution->setWorkflowRunId($workflowRunId);

        $this->assertEquals($workflowRunId, $execution->getWorkflowRunId());
    }

    public function testSetTaskIdShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $taskId = 'task-67890';

        $execution->setTaskId($taskId);

        $this->assertEquals($taskId, $execution->getTaskId());
    }

    public function testSetTaskIdWithNullShouldAcceptNull(): void
    {
        $execution = $this->createEntity();
        $execution->setTaskId('task-123');

        $execution->setTaskId(null);

        $this->assertNull($execution->getTaskId());
    }

    public function testSetWorkflowIdShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $workflowId = 'workflow-67890';

        $execution->setWorkflowId($workflowId);

        $this->assertEquals($workflowId, $execution->getWorkflowId());
    }

    public function testSetBlockingShouldUpdateValue(): void
    {
        $execution = $this->createEntity();

        $execution->setBlocking(true);

        $this->assertTrue($execution->isBlocking());

        $execution->setBlocking(false);
        $this->assertFalse($execution->isBlocking());
    }

    public function testSetExecutionIdShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $executionId = 'exec-67890';

        $execution->setExecutionId($executionId);

        $this->assertEquals($executionId, $execution->getExecutionId());
    }

    public function testSetStatusShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $status = WorkflowStatus::RUNNING;

        $execution->setStatus($status);

        $this->assertEquals($status, $execution->getStatus());
    }

    public function testSetInputsShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $inputs = [
            'user_query' => '请帮我分析这个数据',
            'data_source' => 'database',
            'output_format' => 'json',
        ];

        $execution->setInputs($inputs);

        $this->assertEquals($inputs, $execution->getInputs());
    }

    public function testSetInputsWithNullShouldAcceptNull(): void
    {
        $execution = $this->createEntity();
        $execution->setInputs(['key' => 'value']);

        $execution->setInputs(null);

        $this->assertNull($execution->getInputs());
    }

    public function testSetOutputsShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $outputs = [
            'result' => '分析结果显示数据趋势向上',
            'confidence' => 0.89,
            'charts' => ['chart1.png', 'chart2.png'],
        ];

        $execution->setOutputs($outputs);

        $this->assertEquals($outputs, $execution->getOutputs());
    }

    public function testSetOutputsWithNullShouldAcceptNull(): void
    {
        $execution = $this->createEntity();
        $execution->setOutputs(['result' => 'test']);

        $execution->setOutputs(null);

        $this->assertNull($execution->getOutputs());
    }

    public function testSetUserIdShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $userId = 'user-456';

        $execution->setUserId($userId);

        $this->assertEquals($userId, $execution->getUserId());
    }

    public function testSetTotalStepsShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $totalSteps = 8;

        $execution->setTotalSteps($totalSteps);

        $this->assertEquals($totalSteps, $execution->getTotalSteps());
    }

    public function testSetCompletedStepsShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $completedSteps = 5;

        $execution->setCompletedSteps($completedSteps);

        $this->assertEquals($completedSteps, $execution->getCompletedSteps());
    }

    public function testSetElapsedTimeShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $elapsedTime = 25.75;

        $execution->setElapsedTime($elapsedTime);

        $this->assertEquals($elapsedTime, $execution->getElapsedTime());
    }

    public function testSetErrorMessageShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $errorMessage = '工作流在第3步失败：API调用超时';

        $execution->setErrorMessage($errorMessage);

        $this->assertEquals($errorMessage, $execution->getErrorMessage());
    }

    public function testSetMetadataShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $metadata = [
            'priority' => 'high',
            'retry_count' => 2,
            'environment' => 'production',
            'triggered_by' => 'schedule',
        ];

        $execution->setMetadata($metadata);

        $this->assertEquals($metadata, $execution->getMetadata());
    }

    public function testSetStartedAtShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $startedAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $execution->setStartedAt($startedAt);

        $this->assertEquals($startedAt, $execution->getStartedAt());
    }

    public function testSetFinishedAtShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $finishedAt = new \DateTimeImmutable('2024-01-15 10:30:00');

        $execution->setFinishedAt($finishedAt);

        $this->assertEquals($finishedAt, $execution->getFinishedAt());
    }

    public function testSetTotalTokensShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $totalTokens = 2500;

        $execution->setTotalTokens($totalTokens);

        $this->assertEquals($totalTokens, $execution->getTotalTokens());
    }

    public function testAddTaskShouldAddNewTask(): void
    {
        $execution = $this->createEntity();
        $task = new class extends WorkflowTask {
            private ?WorkflowExecution $workflowExecution = null;

            public function setWorkflowExecution(?WorkflowExecution $execution): void
            {
                $this->workflowExecution = $execution;
            }

            public function getWorkflowExecution(): ?WorkflowExecution
            {
                return $this->workflowExecution;
            }
        };

        $result = $execution->addTask($task);

        $this->assertTrue($execution->getTasks()->contains($task));
        $this->assertSame($execution, $task->getWorkflowExecution());
    }

    public function testAddTaskShouldNotAddDuplicateTask(): void
    {
        $execution = $this->createEntity();
        $task = new class extends WorkflowTask {
            private ?WorkflowExecution $workflowExecution = null;

            public function setWorkflowExecution(?WorkflowExecution $execution): void
            {
                $this->workflowExecution = $execution;
            }

            public function getWorkflowExecution(): ?WorkflowExecution
            {
                return $this->workflowExecution;
            }
        };

        $execution->addTask($task);
        $execution->addTask($task);

        $this->assertEquals(1, $execution->getTasks()->count());
        $this->assertSame($execution, $task->getWorkflowExecution());
    }

    public function testRemoveTaskShouldRemoveExistingTask(): void
    {
        $execution = $this->createEntity();
        $task = new class extends WorkflowTask {
            private ?WorkflowExecution $workflowExecution = null;

            public function setWorkflowExecution(?WorkflowExecution $execution): void
            {
                $this->workflowExecution = $execution;
            }

            public function getWorkflowExecution(): ?WorkflowExecution
            {
                return $this->workflowExecution;
            }
        };

        $execution->addTask($task);
        $this->assertTrue($execution->getTasks()->contains($task));
        $this->assertSame($execution, $task->getWorkflowExecution());

        $result = $execution->removeTask($task);

        $this->assertFalse($execution->getTasks()->contains($task));
        $this->assertNull($task->getWorkflowExecution());
    }

    public function testGetProgressPercentageShouldReturnCorrectValue(): void
    {
        $execution = $this->createEntity();

        // Test with no total steps
        $this->assertEquals(0.0, $execution->getProgressPercentage());

        // Test with total steps set
        $execution->setTotalSteps(10);
        $execution->setCompletedSteps(3);
        $this->assertEquals(30.0, $execution->getProgressPercentage());

        // Test with all steps completed
        $execution->setCompletedSteps(10);
        $this->assertEquals(100.0, $execution->getProgressPercentage());

        // Test with zero total steps
        $execution->setTotalSteps(0);
        $this->assertEquals(0.0, $execution->getProgressPercentage());
    }

    public function testSetCreateTimeShouldUpdateValue(): void
    {
        $execution = $this->createEntity();
        $createTime = new \DateTimeImmutable('2024-01-15 09:00:00');

        $execution->setCreateTime($createTime);

        $this->assertEquals($createTime, $execution->getCreateTime());
    }

    public function testToStringShouldReturnTaskIdAndStatus(): void
    {
        $execution = $this->createEntity();
        $execution->setTaskId('task-12345');
        $execution->setStatus(WorkflowStatus::RUNNING);

        $result = (string) $execution;

        $this->assertStringContainsString('工作流执行 task-12345', $result);
        $this->assertStringContainsString('(', $result);
        $this->assertStringContainsString(')', $result);
    }

    public function testWorkflowExecutionShouldAcceptComplexInputsAndOutputs(): void
    {
        $execution = $this->createEntity();
        $complexInputs = [
            'data_sources' => [
                'database' => ['host' => 'db.example.com', 'port' => 5432],
                'api' => ['endpoint' => 'https://api.example.com/v1'],
            ],
            'processing_options' => [
                'parallel' => true,
                'batch_size' => 100,
                'timeout' => 300,
            ],
            'output_config' => [
                'format' => 'json',
                'compression' => 'gzip',
                'encryption' => true,
            ],
        ];

        $complexOutputs = [
            'processed_records' => 15420,
            'execution_summary' => [
                'total_time' => 45.2,
                'memory_peak' => '256MB',
                'cpu_usage_avg' => 0.75,
            ],
            'results' => [
                'success_rate' => 0.98,
                'error_count' => 42,
                'warnings' => ['deprecated_api_usage', 'slow_query_detected'],
            ],
            'generated_files' => [
                'report.pdf',
                'data_export.csv',
                'error_log.txt',
            ],
        ];

        $execution->setInputs($complexInputs);
        $execution->setOutputs($complexOutputs);

        $this->assertEquals($complexInputs, $execution->getInputs());
        $this->assertEquals($complexOutputs, $execution->getOutputs());
    }
}
