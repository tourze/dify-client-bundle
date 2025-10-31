<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\DifyClientBundle\Entity\WorkflowExecution;
use Tourze\DifyClientBundle\Entity\WorkflowLog;
use Tourze\DifyClientBundle\Entity\WorkflowTask;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(WorkflowLog::class)]
final class WorkflowLogTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): WorkflowLog
    {
        return new WorkflowLog();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'logLevel' => ['logLevel', 'info'];
        yield 'message' => ['message', '节点执行成功，生成输出结果'];
        yield 'category' => ['category', 'node_execution'];
        yield 'nodeId' => ['nodeId', 'node-12345'];
        yield 'nodeName' => ['nodeName', 'LLM节点'];
        yield 'nodeType' => ['nodeType', 'llm'];
        yield 'title' => ['title', '文本生成任务'];
        yield 'elapsedTime' => ['elapsedTime', 2.5];
        yield 'stepIndex' => ['stepIndex', 2];
        yield 'stackTrace' => ['stackTrace', 'Exception in thread "main" java.lang.RuntimeException'];
        yield 'threadId' => ['threadId', 'thread-123'];
    }

    public function testCreateWorkflowLogWithDefaultValuesShouldSucceed(): void
    {
        $log = $this->createEntity();

        $this->assertNull($log->getId());
        $this->assertNull($log->getWorkflowExecution());
        $this->assertNull($log->getWorkflowTask());
        $this->assertNull($log->getCategory());
        $this->assertNull($log->getNodeId());
        $this->assertNull($log->getNodeName());
        $this->assertNull($log->getNodeType());
        $this->assertNull($log->getTitle());
        $this->assertNull($log->getInputs());
        $this->assertNull($log->getOutputs());
        $this->assertNull($log->getElapsedTime());
        $this->assertNull($log->getExecutionMetadata());
        $this->assertNull($log->getStepIndex());
        $this->assertNull($log->getContext());
        $this->assertNull($log->getStackTrace());
        $this->assertNull($log->getThreadId());
        $this->assertNull($log->getMetadata());
        $this->assertNull($log->getLoggedAt());
    }

    public function testSetWorkflowExecutionShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $execution = $this->createMock(WorkflowExecution::class);

        $log->setWorkflowExecution($execution);

        $this->assertEquals($execution, $log->getWorkflowExecution());
    }

    public function testSetWorkflowExecutionWithNullShouldAcceptNull(): void
    {
        $log = $this->createEntity();
        $execution = $this->createMock(WorkflowExecution::class);
        $log->setWorkflowExecution($execution);

        $log->setWorkflowExecution(null);

        $this->assertNull($log->getWorkflowExecution());
    }

    public function testSetWorkflowTaskShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $task = $this->createMock(WorkflowTask::class);

        $log->setWorkflowTask($task);

        $this->assertEquals($task, $log->getWorkflowTask());
    }

    public function testSetWorkflowTaskWithNullShouldAcceptNull(): void
    {
        $log = $this->createEntity();
        $task = $this->createMock(WorkflowTask::class);
        $log->setWorkflowTask($task);

        $log->setWorkflowTask(null);

        $this->assertNull($log->getWorkflowTask());
    }

    public function testSetLogLevelShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $logLevel = 'error';

        $log->setLogLevel($logLevel);

        $this->assertEquals($logLevel, $log->getLogLevel());
    }

    #[TestWith(['debug'], 'debug')]
    #[TestWith(['info'], 'info')]
    #[TestWith(['warning'], 'warning')]
    #[TestWith(['error'], 'error')]
    public function testSetLogLevelWithValidValuesShouldSucceed(string $logLevel): void
    {
        $log = $this->createEntity();

        $log->setLogLevel($logLevel);

        $this->assertEquals($logLevel, $log->getLogLevel());
    }

    public function testSetMessageShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $message = '工作流节点执行完成，处理了123条记录';

        $log->setMessage($message);

        $this->assertEquals($message, $log->getMessage());
    }

    public function testSetCategoryShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $category = 'data_processing';

        $log->setCategory($category);

        $this->assertEquals($category, $log->getCategory());
    }

    public function testSetNodeIdShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $nodeId = 'node-67890';

        $log->setNodeId($nodeId);

        $this->assertEquals($nodeId, $log->getNodeId());
    }

    public function testSetNodeNameShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $nodeName = '数据处理节点';

        $log->setNodeName($nodeName);

        $this->assertEquals($nodeName, $log->getNodeName());
    }

    public function testSetNodeTypeShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $nodeType = 'data_transformer';

        $log->setNodeType($nodeType);

        $this->assertEquals($nodeType, $log->getNodeType());
    }

    public function testSetTitleShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $title = '用户数据分析任务';

        $log->setTitle($title);

        $this->assertEquals($title, $log->getTitle());
    }

    public function testSetInputsShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $inputs = [
            'query' => '分析用户行为数据',
            'data_source' => 'user_events',
            'time_range' => ['start' => '2024-01-01', 'end' => '2024-01-31'],
        ];

        $log->setInputs($inputs);

        $this->assertEquals($inputs, $log->getInputs());
    }

    public function testSetOutputsShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $outputs = [
            'processed_records' => 1523,
            'insights' => ['用户活跃度提升15%', '转化率增长8%'],
            'charts' => ['activity_trend.png', 'conversion_funnel.png'],
        ];

        $log->setOutputs($outputs);

        $this->assertEquals($outputs, $log->getOutputs());
    }

    public function testSetElapsedTimeShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $elapsedTime = 15.75;

        $log->setElapsedTime($elapsedTime);

        $this->assertEquals($elapsedTime, $log->getElapsedTime());
    }

    public function testSetExecutionMetadataShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $executionMetadata = [
            'memory_usage' => '128MB',
            'cpu_time' => 8.2,
            'network_calls' => 5,
            'cache_hits' => 12,
        ];

        $log->setExecutionMetadata($executionMetadata);

        $this->assertEquals($executionMetadata, $log->getExecutionMetadata());
    }

    public function testSetStepIndexShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $stepIndex = 3;

        $log->setStepIndex($stepIndex);

        $this->assertEquals($stepIndex, $log->getStepIndex());
    }

    public function testSetContextShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $context = [
            'variables' => ['user_id' => 12345, 'session_id' => 'sess_789'],
            'environment' => 'production',
            'request_id' => 'req_abc123',
        ];

        $log->setContext($context);

        $this->assertEquals($context, $log->getContext());
    }

    public function testSetStackTraceShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $stackTrace = 'Exception in thread "workflow-executor" RuntimeException: Node execution failed';

        $log->setStackTrace($stackTrace);

        $this->assertEquals($stackTrace, $log->getStackTrace());
    }

    public function testSetThreadIdShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $threadId = 'worker-thread-456';

        $log->setThreadId($threadId);

        $this->assertEquals($threadId, $log->getThreadId());
    }

    public function testSetMetadataShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $metadata = [
            'source' => 'workflow_engine',
            'version' => '2.1.0',
            'correlation_id' => 'corr_xyz789',
        ];

        $log->setMetadata($metadata);

        $this->assertEquals($metadata, $log->getMetadata());
    }

    public function testSetLoggedAtShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $loggedAt = new \DateTimeImmutable('2024-01-15 14:30:00');

        $log->setLoggedAt($loggedAt);

        $this->assertEquals($loggedAt, $log->getLoggedAt());
    }

    public function testSetCreateTimeShouldUpdateValue(): void
    {
        $log = $this->createEntity();
        $createTime = new \DateTimeImmutable('2024-01-15 14:00:00');

        $log->setCreateTime($createTime);

        $this->assertEquals($createTime, $log->getCreateTime());
    }

    public function testAliasMethodsShouldWork(): void
    {
        $log = $this->createEntity();
        $execution = $this->createMock(WorkflowExecution::class);

        // Test setExecution alias
        $log->setExecution($execution);
        $this->assertEquals($execution, $log->getWorkflowExecution());

        // Test getLevel alias
        $log->setLogLevel('warning');
        $this->assertEquals('warning', $log->getLevel());

        // Test setLevel alias
        $log->setLevel('error');
        $this->assertEquals('error', $log->getLogLevel());

        // Test setCreatedAt alias
        $createTime = new \DateTimeImmutable('2024-01-15 15:00:00');
        $log->setCreatedAt($createTime);
        $this->assertEquals($createTime, $log->getCreateTime());
    }

    public function testToStringShouldReturnFormattedLogMessage(): void
    {
        $log = $this->createEntity();
        $log->setLogLevel('info');
        $log->setMessage('这是一个很长的日志消息，包含了详细的执行信息和状态更新');

        $result = (string) $log;

        $this->assertStringStartsWith('[INFO]', $result);
        $this->assertStringContainsString('这是一个很长的日志消息，包含了详细的执行信息和状态更新', $result);
    }

    public function testToStringWithLongMessageShouldTruncate(): void
    {
        $log = $this->createEntity();
        $log->setLogLevel('debug');
        $longMessage = str_repeat('这是一个很长的日志消息内容，用于测试字符串截断功能。', 10);
        $log->setMessage($longMessage);

        $result = (string) $log;

        $this->assertStringStartsWith('[DEBUG]', $result);
        $this->assertStringEndsWith('...', $result);
        $this->assertTrue(mb_strlen($result) < mb_strlen($longMessage) + 20); // 考虑前缀
    }

    public function testWorkflowLogShouldAcceptComplexData(): void
    {
        $log = $this->createEntity();
        $complexInputs = [
            'model_config' => [
                'temperature' => 0.7,
                'max_tokens' => 2000,
                'top_p' => 0.9,
            ],
            'prompt_template' => 'Analyze the following data and provide insights: {data}',
            'data_sources' => ['database_query', 'api_endpoint', 'file_upload'],
        ];

        $complexOutputs = [
            'analysis_result' => '数据显示了明显的季节性趋势',
            'confidence_score' => 0.92,
            'generated_charts' => ['trend_analysis.png', 'distribution.png'],
            'recommendations' => [
                '建议在Q4加大营销投入',
                '优化产品定价策略',
                '提升客户服务质量',
            ],
        ];

        $complexContext = [
            'execution_environment' => [
                'node_version' => 'v18.17.0',
                'memory_limit' => '2GB',
                'timeout' => 300,
            ],
            'workflow_state' => [
                'previous_steps' => ['data_collection', 'preprocessing'],
                'next_steps' => ['report_generation', 'notification'],
            ],
            'user_context' => [
                'user_id' => 'user_12345',
                'organization' => 'acme_corp',
                'permissions' => ['read', 'analyze'],
            ],
        ];

        $log->setInputs($complexInputs);
        $log->setOutputs($complexOutputs);
        $log->setContext($complexContext);

        $this->assertEquals($complexInputs, $log->getInputs());
        $this->assertEquals($complexOutputs, $log->getOutputs());
        $this->assertEquals($complexContext, $log->getContext());
    }
}
