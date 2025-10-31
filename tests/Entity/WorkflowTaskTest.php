<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\DifyClientBundle\Entity\WorkflowExecution;
use Tourze\DifyClientBundle\Entity\WorkflowTask;
use Tourze\DifyClientBundle\Enum\WorkflowStatus;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(WorkflowTask::class)]
final class WorkflowTaskTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): WorkflowTask
    {
        return new WorkflowTask();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'nodeId' => ['nodeId', 'node-12345'];
        yield 'taskId' => ['taskId', 'task-12345'];
        yield 'nodeName' => ['nodeName', 'LLM文本生成节点'];
        yield 'nodeType' => ['nodeType', 'llm'];
        yield 'status' => ['status', 'completed'];
        yield 'stepIndex' => ['stepIndex', 2];
        yield 'executionTime' => ['executionTime', 5.2];
        yield 'errorMessage' => ['errorMessage', '节点执行超时'];
        yield 'title' => ['title', '智能文本生成任务'];
        yield 'elapsedTime' => ['elapsedTime', 8.5];
        yield 'totalTokens' => ['totalTokens', 1200];
    }

    public function testCreateWorkflowTaskWithDefaultValuesShouldSucceed(): void
    {
        $task = $this->createEntity();

        $this->assertNull($task->getId());
        $this->assertNull($task->getWorkflowExecution());
        $this->assertNull($task->getTaskId());
        $this->assertEquals('pending', $task->getStatus());
        $this->assertNull($task->getInputs());
        $this->assertNull($task->getOutputs());
        $this->assertNull($task->getExecutionTime());
        $this->assertNull($task->getErrorMessage());
        $this->assertNull($task->getMetadata());
        $this->assertNull($task->getStartedAt());
        $this->assertNull($task->getCompletedAt());
        $this->assertNull($task->getTitle());
        $this->assertNull($task->getExecutionMetadata());
        $this->assertNull($task->getElapsedTime());
        $this->assertNull($task->getTotalTokens());
        $this->assertNull($task->getFinishedAt());
    }

    public function testSetWorkflowExecutionShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $execution = $this->createMock(WorkflowExecution::class);

        $task->setWorkflowExecution($execution);

        $this->assertEquals($execution, $task->getWorkflowExecution());
    }

    public function testSetWorkflowExecutionWithNullShouldAcceptNull(): void
    {
        $task = $this->createEntity();
        $execution = $this->createMock(WorkflowExecution::class);
        $task->setWorkflowExecution($execution);

        $task->setWorkflowExecution(null);

        $this->assertNull($task->getWorkflowExecution());
    }

    public function testSetNodeIdShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $nodeId = 'node-67890';

        $task->setNodeId($nodeId);

        $this->assertEquals($nodeId, $task->getNodeId());
    }

    public function testSetTaskIdShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $taskId = 'task-67890';

        $task->setTaskId($taskId);

        $this->assertEquals($taskId, $task->getTaskId());
    }

    public function testSetTaskIdWithNullShouldAcceptNull(): void
    {
        $task = $this->createEntity();
        $task->setTaskId('task-123');

        $task->setTaskId(null);

        $this->assertNull($task->getTaskId());
    }

    public function testSetNodeNameShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $nodeName = '数据处理节点';

        $task->setNodeName($nodeName);

        $this->assertEquals($nodeName, $task->getNodeName());
    }

    public function testSetNodeTypeShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $nodeType = 'data_processor';

        $task->setNodeType($nodeType);

        $this->assertEquals($nodeType, $task->getNodeType());
    }

    public function testSetStatusWithStringShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $status = 'running';

        $task->setStatus($status);

        $this->assertEquals($status, $task->getStatus());
    }

    public function testSetStatusWithEnumShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $status = WorkflowStatus::COMPLETED;

        $task->setStatus($status);

        $this->assertEquals($status->value, $task->getStatus());
    }

    #[TestWith(['pending'], 'pending')]
    #[TestWith(['running'], 'running')]
    #[TestWith(['completed'], 'completed')]
    #[TestWith(['failed'], 'failed')]
    #[TestWith(['stopped'], 'stopped')]
    public function testSetStatusWithValidValuesShouldSucceed(string $status): void
    {
        $task = $this->createEntity();

        $task->setStatus($status);

        $this->assertEquals($status, $task->getStatus());
    }

    public function testSetStepIndexShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $stepIndex = 5;

        $task->setStepIndex($stepIndex);

        $this->assertEquals($stepIndex, $task->getStepIndex());
    }

    public function testSetInputsShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $inputs = [
            'prompt' => '请分析这个数据集',
            'data' => ['item1', 'item2', 'item3'],
            'config' => ['temperature' => 0.7, 'max_tokens' => 1000],
        ];

        $task->setInputs($inputs);

        $this->assertEquals($inputs, $task->getInputs());
    }

    public function testSetInputsWithNullShouldAcceptNull(): void
    {
        $task = $this->createEntity();
        $task->setInputs(['key' => 'value']);

        $task->setInputs(null);

        $this->assertNull($task->getInputs());
    }

    public function testSetOutputsShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $outputs = [
            'result' => '分析结果显示数据呈现上升趋势',
            'confidence' => 0.85,
            'metrics' => ['accuracy' => 0.92, 'precision' => 0.88],
        ];

        $task->setOutputs($outputs);

        $this->assertEquals($outputs, $task->getOutputs());
    }

    public function testSetOutputsWithNullShouldAcceptNull(): void
    {
        $task = $this->createEntity();
        $task->setOutputs(['result' => 'test']);

        $task->setOutputs(null);

        $this->assertNull($task->getOutputs());
    }

    public function testSetExecutionTimeShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $executionTime = 12.5;

        $task->setExecutionTime($executionTime);

        $this->assertEquals($executionTime, $task->getExecutionTime());
    }

    public function testSetExecutionTimeWithNullShouldAcceptNull(): void
    {
        $task = $this->createEntity();
        $task->setExecutionTime(5.0);

        $task->setExecutionTime(null);

        $this->assertNull($task->getExecutionTime());
    }

    public function testSetErrorMessageShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $errorMessage = '节点执行失败：连接超时';

        $task->setErrorMessage($errorMessage);

        $this->assertEquals($errorMessage, $task->getErrorMessage());
    }

    public function testSetErrorMessageWithNullShouldAcceptNull(): void
    {
        $task = $this->createEntity();
        $task->setErrorMessage('原始错误信息');

        $task->setErrorMessage(null);

        $this->assertNull($task->getErrorMessage());
    }

    public function testSetMetadataShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $metadata = [
            'node_version' => '1.2.0',
            'retry_count' => 2,
            'priority' => 'high',
        ];

        $task->setMetadata($metadata);

        $this->assertEquals($metadata, $task->getMetadata());
    }

    public function testSetMetadataWithNullShouldAcceptNull(): void
    {
        $task = $this->createEntity();
        $task->setMetadata(['key' => 'value']);

        $task->setMetadata(null);

        $this->assertNull($task->getMetadata());
    }

    public function testSetStartedAtShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $startedAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $task->setStartedAt($startedAt);

        $this->assertEquals($startedAt, $task->getStartedAt());
    }

    public function testSetCompletedAtShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $completedAt = new \DateTimeImmutable('2024-01-15 10:30:00');

        $task->setCompletedAt($completedAt);

        $this->assertEquals($completedAt, $task->getCompletedAt());
    }

    public function testSetTitleShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $title = '智能数据分析任务';

        $task->setTitle($title);

        $this->assertEquals($title, $task->getTitle());
    }

    public function testSetExecutionMetadataShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $executionMetadata = [
            'memory_usage' => '256MB',
            'cpu_time' => 15.2,
            'api_calls' => 8,
        ];

        $task->setExecutionMetadata($executionMetadata);

        $this->assertEquals($executionMetadata, $task->getExecutionMetadata());
    }

    public function testSetElapsedTimeShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $elapsedTime = 25.8;

        $task->setElapsedTime($elapsedTime);

        $this->assertEquals($elapsedTime, $task->getElapsedTime());
    }

    public function testSetTotalTokensShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $totalTokens = 2500;

        $task->setTotalTokens($totalTokens);

        $this->assertEquals($totalTokens, $task->getTotalTokens());
    }

    public function testSetFinishedAtShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $finishedAt = new \DateTimeImmutable('2024-01-15 10:45:00');

        $task->setFinishedAt($finishedAt);

        $this->assertEquals($finishedAt, $task->getFinishedAt());
    }

    public function testIsCompletedShouldReturnCorrectValue(): void
    {
        $task = $this->createEntity();

        $task->setStatus('pending');
        $this->assertFalse($task->isCompleted());

        $task->setStatus('completed');
        $this->assertTrue($task->isCompleted());

        $task->setStatus('failed');
        $this->assertFalse($task->isCompleted());
    }

    public function testIsFailedShouldReturnCorrectValue(): void
    {
        $task = $this->createEntity();

        $task->setStatus('pending');
        $this->assertFalse($task->isFailed());

        $task->setStatus('completed');
        $this->assertFalse($task->isFailed());

        $task->setStatus('failed');
        $this->assertTrue($task->isFailed());
    }

    public function testIsRunningShouldReturnCorrectValue(): void
    {
        $task = $this->createEntity();

        $task->setStatus('pending');
        $this->assertFalse($task->isRunning());

        $task->setStatus('running');
        $this->assertTrue($task->isRunning());

        $task->setStatus('completed');
        $this->assertFalse($task->isRunning());
    }

    public function testSetCreateTimeShouldUpdateValue(): void
    {
        $task = $this->createEntity();
        $createTime = new \DateTimeImmutable('2024-01-15 09:00:00');

        $task->setCreateTime($createTime);

        $this->assertEquals($createTime, $task->getCreateTime());
    }

    public function testSetExecutionAliasShouldWork(): void
    {
        $task = $this->createEntity();
        $execution = $this->createMock(WorkflowExecution::class);

        $task->setExecution($execution);

        $this->assertEquals($execution, $task->getWorkflowExecution());
    }

    public function testToStringShouldReturnNodeNameStepAndStatus(): void
    {
        $task = $this->createEntity();
        $task->setNodeName('LLM节点');
        $task->setStepIndex(3);
        $task->setStatus('running');

        $result = (string) $task;

        $this->assertStringContainsString('LLM节点', $result);
        $this->assertStringContainsString('步骤3', $result);
        $this->assertStringContainsString('running', $result);
    }

    public function testWorkflowTaskShouldAcceptComplexInputsAndOutputs(): void
    {
        $task = $this->createEntity();
        $complexInputs = [
            'model_config' => [
                'name' => 'gpt-4',
                'temperature' => 0.8,
                'max_tokens' => 2000,
                'top_p' => 0.95,
            ],
            'prompt_template' => '根据以下数据生成分析报告：{data}',
            'data_sources' => [
                'sales_data' => ['q1' => 100000, 'q2' => 120000, 'q3' => 115000],
                'user_feedback' => ['positive' => 85, 'negative' => 15],
            ],
            'output_format' => ['type' => 'markdown', 'include_charts' => true],
        ];

        $complexOutputs = [
            'generated_report' => 'Q1-Q3销售分析报告...',
            'analysis_insights' => [
                '销售趋势整体向上',
                'Q2表现最佳，增长20%',
                '用户满意度较高，达到85%',
            ],
            'recommendations' => [
                '继续优化产品功能',
                '加强市场推广力度',
                '关注客户反馈改进',
            ],
            'generated_files' => ['report.md', 'chart1.png', 'chart2.png'],
            'metrics' => [
                'processing_time' => 12.5,
                'token_usage' => 1800,
                'confidence_score' => 0.92,
            ],
        ];

        $task->setInputs($complexInputs);
        $task->setOutputs($complexOutputs);

        $this->assertEquals($complexInputs, $task->getInputs());
        $this->assertEquals($complexOutputs, $task->getOutputs());
    }
}
