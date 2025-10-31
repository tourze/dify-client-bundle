<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Clock\ClockInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Entity\WorkflowExecution;
use Tourze\DifyClientBundle\Entity\WorkflowLog;
use Tourze\DifyClientBundle\Entity\WorkflowTask;
use Tourze\DifyClientBundle\Enum\WorkflowStatus;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;
use Tourze\DifyClientBundle\Exception\DifySettingNotFoundException;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;
use Tourze\DifyClientBundle\Repository\WorkflowExecutionRepository;
use Tourze\DifyClientBundle\Repository\WorkflowLogRepository;
use Tourze\DifyClientBundle\Repository\WorkflowTaskRepository;

/**
 * 工作流执行服务
 *
 * 提供工作流的执行、监控、日志管理等功能
 * 对应 API: POST /workflows/run, GET /workflows/{workflow_id}/logs
 */
readonly class WorkflowService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private DifySettingRepository $settingRepository,
        private WorkflowExecutionRepository $executionRepository,
        private WorkflowTaskRepository $taskRepository,
        private WorkflowLogRepository $logRepository,
        private ClockInterface $clock,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 执行工作流
     *
     * @param array<string, mixed> $inputs
     */
    public function executeWorkflow(
        string $workflowId,
        array $inputs = [],
        string $userId = 'system',
        bool $blocking = true,
    ): WorkflowExecution {
        $setting = $this->getActiveSetting();
        $execution = $this->initializeExecution($workflowId, $inputs, $userId, $blocking);

        try {
            $response = $this->sendWorkflowRequest($setting, $workflowId, $inputs, $userId, $blocking);
            $this->processWorkflowResponse($execution, $response, $blocking);

            return $execution;
        } catch (\Exception $e) {
            $this->handleExecutionFailure($execution, $e);
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $inputs
     */
    private function initializeExecution(string $workflowId, array $inputs, string $userId, bool $blocking): WorkflowExecution
    {
        $execution = $this->createExecution($workflowId, $inputs, $userId, $blocking);
        $this->persistExecution($execution);

        return $execution;
    }

    /**
     * @param array<string, mixed> $response
     */
    private function processWorkflowResponse(WorkflowExecution $execution, array $response, bool $blocking): void
    {
        $this->updateExecutionFromResponse($execution, $response);
        $this->persistExecution($execution);

        $this->maybeCreateTask($execution, $response, $blocking);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function maybeCreateTask(WorkflowExecution $execution, array $response, bool $blocking): void
    {
        if (!$blocking && isset($response['task_id']) && is_string($response['task_id'])) {
            $this->createWorkflowTask($execution, $response['task_id']);
        }
    }

    /**
     * 流式执行工作流
     *
     * @param array<string, mixed> $inputs
     */
    public function executeWorkflowStream(
        string $workflowId,
        array $inputs = [],
        string $userId = 'system',
    ): \Generator {
        $setting = $this->getActiveSetting();
        $execution = $this->initializeStreamExecution($workflowId, $inputs, $userId);

        try {
            $response = $this->sendWorkflowStreamRequest($setting, $workflowId, $inputs, $userId);
            $fullOutput = '';

            foreach ($response as $chunk) {
                $content = $this->processStreamChunk($chunk);
                if ('' !== $content) {
                    $fullOutput .= $content;
                    yield $content;
                }
            }

            $this->finalizeStreamExecution($execution, $fullOutput);
        } catch (\Exception $e) {
            $this->handleExecutionFailure($execution, $e);
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $inputs
     */
    private function initializeStreamExecution(string $workflowId, array $inputs, string $userId): WorkflowExecution
    {
        $execution = $this->createExecution($workflowId, $inputs, $userId, false);
        $this->persistExecution($execution);

        return $execution;
    }

    private function processStreamChunk(mixed $chunk): string
    {
        if (!is_string($chunk)) {
            return '';
        }

        $outputContent = $this->parseStreamChunk($chunk);

        return '' !== $outputContent ? $outputContent : '';
    }

    private function finalizeStreamExecution(WorkflowExecution $execution, string $fullOutput): void
    {
        $execution->setOutputs(['result' => $fullOutput]);
        $execution->setStatus(WorkflowStatus::COMPLETED);
        $execution->setFinishedAt($this->clock->now());
        $this->persistExecution($execution);
    }

    /**
     * 获取工作流执行状态
     */
    public function getExecutionStatus(string $executionId): ?WorkflowExecution
    {
        return $this->executionRepository->find($executionId);
    }

    /**
     * 获取工作流任务状态
     */
    /** @return ?array<string, mixed> */
    public function getTaskStatus(string $taskId): ?array
    {
        $setting = $this->getActiveSetting();

        try {
            return $this->getWorkflowTaskStatus($setting, $taskId);
        } catch (\Exception $e) {
            throw new DifyRuntimeException(sprintf('Failed to get task status: %s', $e->getMessage()));
        }
    }

    /**
     * 停止工作流执行
     */
    public function stopWorkflow(WorkflowExecution $execution): void
    {
        $this->validateWorkflowIsRunning($execution);
        $this->attemptStopDifyTask($execution);
        $this->markWorkflowAsStopped($execution);
    }

    private function validateWorkflowIsRunning(WorkflowExecution $execution): void
    {
        if (WorkflowStatus::RUNNING !== $execution->getStatus()) {
            throw new DifyRuntimeException('Cannot stop workflow that is not running');
        }
    }

    private function attemptStopDifyTask(WorkflowExecution $execution): void
    {
        $setting = $this->getActiveSetting();
        $taskId = $execution->getTaskId();

        if (null === $taskId || '' === $taskId) {
            return;
        }

        try {
            $this->stopDifyWorkflowTask($setting, $taskId);
        } catch (\Exception $e) {
            $this->logStopTaskFailure($execution, $e);
        }
    }

    private function logStopTaskFailure(WorkflowExecution $execution, \Exception $e): void
    {
        error_log(sprintf('Failed to stop Dify workflow task %s: %s', $execution->getTaskId(), $e->getMessage()));
    }

    private function markWorkflowAsStopped(WorkflowExecution $execution): void
    {
        $execution->setStatus(WorkflowStatus::STOPPED);
        $execution->setFinishedAt($this->clock->now());
        $this->persistExecution($execution);
    }

    /**
     * 获取工作流执行日志
     *
     * @return array<WorkflowLog>
     */
    public function getWorkflowLogs(WorkflowExecution $execution, int $limit = 100, int $offset = 0): array
    {
        return $this->logRepository->findBy(
            ['execution' => $execution],
            ['createdAt' => 'ASC'],
            $limit,
            $offset
        );
    }

    /**
     * 添加工作流日志
     * @param ?array<string, mixed> $context
     */
    public function addWorkflowLog(
        WorkflowExecution $execution,
        string $level,
        string $message,
        ?array $context = null,
    ): WorkflowLog {
        $log = new WorkflowLog();
        $log->setExecution($execution);
        $log->setLevel($level);
        $log->setMessage($message);
        $log->setContext($context);
        $log->setCreateTime($this->clock->now());

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $log;
    }

    /**
     * 获取用户的工作流执行历史
     *
     * @return array<WorkflowExecution>
     */
    public function getUserExecutions(string $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->executionRepository->findBy(
            ['userId' => $userId],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * 获取指定工作流的执行历史
     *
     * @return array<WorkflowExecution>
     */
    public function getWorkflowExecutions(string $workflowId, int $limit = 50, int $offset = 0): array
    {
        return $this->executionRepository->findBy(
            ['workflowId' => $workflowId],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * 获取工作流执行统计信息
     */
    /** @return array<string, mixed> */
    public function getExecutionStats(?string $workflowId = null): array
    {
        $baseQueryBuilder = $this->createBaseQueryBuilder($workflowId);

        return [
            'total_executions' => $this->getTotalExecutions($baseQueryBuilder),
            'status_distribution' => $this->getStatusDistribution($baseQueryBuilder),
            'average_execution_time' => $this->getAverageExecutionTime($baseQueryBuilder),
            'daily_stats_7_days' => $this->getDailyStats($baseQueryBuilder),
        ];
    }

    private function createBaseQueryBuilder(?string $workflowId): QueryBuilder
    {
        $qb = $this->executionRepository->createQueryBuilder('e');

        if (null !== $workflowId && '' !== $workflowId) {
            $qb->where('e.workflowId = :workflowId')
                ->setParameter('workflowId', $workflowId)
            ;
        }

        return $qb;
    }

    private function getTotalExecutions(QueryBuilder $qb): int
    {
        return (int) $qb
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @return array<mixed>
     */
    private function getStatusDistribution(QueryBuilder $qb): array
    {
        return $qb
            ->select('e.status, COUNT(e.id) as count')
            ->groupBy('e.status')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    private function getAverageExecutionTime(QueryBuilder $qb): float
    {
        $avgTime = $qb
            ->select('AVG(TIMESTAMPDIFF(SECOND, e.startedAt, e.finishedAt))')
            ->where('e.finishedAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return null !== $avgTime ? round((float) $avgTime, 2) : 0;
    }

    /** @return array<string, mixed> */
    private function getDailyStats(QueryBuilder $qb): array
    {
        $sevenDaysAgo = $this->clock->now()->modify('-7 days');

        /** @var array<string, mixed> */
        return $qb
            ->select('DATE(e.createdAt) as date, COUNT(e.id) as count')
            ->where('e.createdAt >= :sevenDaysAgo')
            ->setParameter('sevenDaysAgo', $sevenDaysAgo)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    /**
     * 重试失败的工作流执行
     */
    public function retryExecution(WorkflowExecution $execution): WorkflowExecution
    {
        $this->validateExecutionCanBeRetried($execution);
        [$workflowId, $userId] = $this->extractExecutionRequiredFields($execution);

        return $this->executeWorkflow(
            $workflowId,
            $execution->getInputs() ?? [],
            $userId,
            $execution->isBlocking()
        );
    }

    private function validateExecutionCanBeRetried(WorkflowExecution $execution): void
    {
        if (WorkflowStatus::FAILED !== $execution->getStatus()) {
            throw new DifyRuntimeException('Can only retry failed executions');
        }
    }

    /** @return array{string, string} */
    private function extractExecutionRequiredFields(WorkflowExecution $execution): array
    {
        $workflowId = $execution->getWorkflowId();
        $userId = $execution->getUserId();

        if (null === $workflowId || null === $userId) {
            throw new DifyRuntimeException('Workflow execution is missing required workflow ID or user ID');
        }

        return [$workflowId, $userId];
    }

    /**
     * 执行工作流（Controller适配方法）
     */
    /**
     * @param array<string, mixed> $inputs
     * @param array<string, mixed> $files
     * @return array<string, mixed>
     */
    public function runWorkflow(array $inputs = [], string $user = 'system', string $responseMode = 'blocking', array $files = []): array
    {
        // 这里需要一个默认的workflow ID，或者从配置/参数中获取
        // 暂时返回错误，需要在使用时提供具体的workflow ID
        throw new DifyRuntimeException('Workflow ID is required for execution. Please use executeWorkflow() method directly.');
    }

    /**
     * 停止工作流任务（Controller适配方法）
     * @return array<string, mixed>
     */
    public function stopWorkflowTask(string $taskId): array
    {
        // 根据taskId查找执行记录
        $execution = $this->executionRepository->findOneBy(['taskId' => $taskId]);
        if (null === $execution) {
            throw new DifyRuntimeException('Workflow execution not found for task: ' . $taskId);
        }

        $this->stopWorkflow($execution);

        return ['message' => 'Workflow stopped successfully'];
    }

    /**
     * 获取工作流任务日志（Controller适配方法）
     * @return array<string, mixed>
     */
    public function getWorkflowTaskLogs(string $taskId, string $user, ?string $keyword = null): array
    {
        $execution = $this->findExecutionByTaskId($taskId);
        $logs = $this->getFilteredLogs($execution, $keyword);

        return $this->buildLogsResponse($taskId, $logs);
    }

    private function findExecutionByTaskId(string $taskId): WorkflowExecution
    {
        $execution = $this->executionRepository->findOneBy(['taskId' => $taskId]);
        if (null === $execution) {
            throw new DifyRuntimeException('Workflow execution not found for task: ' . $taskId);
        }

        return $execution;
    }

    /** @return array<WorkflowLog> */
    private function getFilteredLogs(WorkflowExecution $execution, ?string $keyword): array
    {
        $logs = $this->getWorkflowLogs($execution);

        return $this->filterLogsByKeyword($logs, $keyword);
    }

    /**
     * @param array<WorkflowLog> $logs
     * @return array<string, mixed>
     */
    private function buildLogsResponse(string $taskId, array $logs): array
    {
        return [
            'task_id' => $taskId,
            'logs' => $this->transformLogsToArray($logs),
        ];
    }

    /**
     * @param array<WorkflowLog> $logs
     * @return array<WorkflowLog>
     */
    private function filterLogsByKeyword(array $logs, ?string $keyword): array
    {
        if (null === $keyword || '' === $keyword) {
            return $logs;
        }

        return array_filter($logs, fn ($log) => false !== stripos($log->getMessage(), $keyword));
    }

    /**
     * @param array<WorkflowLog> $logs
     * @return array<int, array<string, mixed>>
     */
    private function transformLogsToArray(array $logs): array
    {
        return array_values(array_map(fn ($log) => [
            'id' => $log->getId(),
            'level' => $log->getLevel(),
            'message' => $log->getMessage(),
            'context' => $log->getContext(),
            'created_at' => $log->getCreateTime()?->format('Y-m-d\TH:i:s\Z'),
        ], $logs));
    }

    /**
     * 清理过期的执行记录
     */
    public function cleanupOldExecutions(\DateInterval $expiry): int
    {
        $expiredDate = $this->clock->now()->sub($expiry);
        $expiredExecutions = $this->findExpiredExecutions($expiredDate);

        return $this->performCleanup($expiredExecutions);
    }

    /** @return array<WorkflowExecution> */
    private function findExpiredExecutions(\DateTimeInterface $expiredDate): array
    {
        $qb = $this->buildExpiredExecutionsQuery($expiredDate);

        /** @var array<WorkflowExecution> */
        return $qb->getQuery()->getResult();
    }

    private function buildExpiredExecutionsQuery(\DateTimeInterface $expiredDate): QueryBuilder
    {
        return $this->executionRepository->createQueryBuilder('e')
            ->where('e.createdAt < :expiredDate')
            ->andWhere('e.status IN (:finalStates)')
            ->setParameter('expiredDate', $expiredDate)
            ->setParameter('finalStates', $this->getFinalStates())
        ;
    }

    /** @return array<string> */
    private function getFinalStates(): array
    {
        return [
            WorkflowStatus::COMPLETED->value,
            WorkflowStatus::FAILED->value,
            WorkflowStatus::STOPPED->value,
        ];
    }

    /** @param array<WorkflowExecution> $expiredExecutions */
    private function performCleanup(array $expiredExecutions): int
    {
        if ([] === $expiredExecutions) {
            return 0;
        }

        $this->removeExpiredExecutions($expiredExecutions);
        $this->entityManager->flush();

        return count($expiredExecutions);
    }

    /** @param array<WorkflowExecution> $expiredExecutions */
    private function removeExpiredExecutions(array $expiredExecutions): void
    {
        foreach ($expiredExecutions as $execution) {
            $this->removeExecutionWithRelatedData($execution, $this->entityManager);
        }
    }

    private function removeExecutionWithRelatedData(WorkflowExecution $execution, EntityManagerInterface $em): void
    {
        $this->removeRelatedLogs($execution, $em);
        $this->removeRelatedTasks($execution, $em);
        $em->remove($execution);
    }

    private function removeRelatedLogs(WorkflowExecution $execution, EntityManagerInterface $em): void
    {
        $logs = $this->logRepository->findBy(['execution' => $execution]);
        foreach ($logs as $log) {
            $em->remove($log);
        }
    }

    private function removeRelatedTasks(WorkflowExecution $execution, EntityManagerInterface $em): void
    {
        $tasks = $this->taskRepository->findBy(['execution' => $execution]);
        foreach ($tasks as $task) {
            $em->remove($task);
        }
    }

    private function getActiveSetting(): DifySetting
    {
        $setting = $this->settingRepository->findActiveSetting();
        if (null === $setting) {
            throw new DifySettingNotFoundException();
        }

        return $setting;
    }

    /**
     * @param array<string, mixed> $inputs
     */
    private function createExecution(string $workflowId, array $inputs, string $userId, bool $blocking): WorkflowExecution
    {
        $execution = new WorkflowExecution();
        $execution->setWorkflowId($workflowId);
        $execution->setInputs($inputs);
        $execution->setUserId($userId);
        $execution->setBlocking($blocking);
        $execution->setStatus(WorkflowStatus::PENDING);
        $execution->setCreateTime($this->clock->now());

        return $execution;
    }

    private function persistExecution(WorkflowExecution $execution): void
    {
        $this->entityManager->persist($execution);
        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $inputs
     * @return array<string, mixed>
     */
    private function sendWorkflowRequest(
        DifySetting $setting,
        string $workflowId,
        array $inputs,
        string $userId,
        bool $blocking,
    ): array {
        $url = rtrim($setting->getBaseUrl(), '/') . '/workflows/run';

        $payload = [
            'inputs' => $inputs,
            'response_mode' => $blocking ? 'blocking' : 'streaming',
            'user' => $userId,
        ];

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'timeout' => $blocking ? max($setting->getTimeout(), 300) : $setting->getTimeout(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Dify workflow execution failed: ' . $response->getContent(false));
        }

        /** @var array<string, mixed> */
        return $response->toArray();
    }

    /**
     * @param array<string, mixed> $inputs
     */
    private function sendWorkflowStreamRequest(DifySetting $setting, string $workflowId, array $inputs, string $userId): \Generator
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/workflows/run';

        $payload = [
            'inputs' => $inputs,
            'response_mode' => 'streaming',
            'user' => $userId,
        ];

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'timeout' => $setting->getTimeout(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Dify workflow stream request failed: ' . $response->getContent(false));
        }

        foreach ($this->httpClient->stream($response) as $chunk) {
            yield $chunk->getContent();
        }
    }

    /** @param array<string, mixed> $response */
    private function updateExecutionFromResponse(WorkflowExecution $execution, array $response): void
    {
        $this->updateExecutionIds($execution, $response);
        $this->updateExecutionOutputs($execution, $response);
        $this->updateExecutionStatus($execution, $response);
    }

    /** @param array<string, mixed> $response */
    private function updateExecutionIds(WorkflowExecution $execution, array $response): void
    {
        if (isset($response['workflow_run_id']) && is_string($response['workflow_run_id'])) {
            $execution->setExecutionId($response['workflow_run_id']);
        }

        if (isset($response['task_id']) && is_string($response['task_id'])) {
            $execution->setTaskId($response['task_id']);
        }
    }

    /** @param array<string, mixed> $response */
    private function updateExecutionOutputs(WorkflowExecution $execution, array $response): void
    {
        if (isset($response['data']) && is_array($response['data'])) {
            /** @var array<string, mixed> $data */
            $data = $response['data'];
            $execution->setOutputs($data);
        }
    }

    /** @param array<string, mixed> $response */
    private function updateExecutionStatus(WorkflowExecution $execution, array $response): void
    {
        $execution->setStatus(WorkflowStatus::RUNNING);
        $execution->setStartedAt($this->clock->now());

        if ($this->shouldMarkAsCompleted($execution, $response)) {
            $execution->setStatus(WorkflowStatus::COMPLETED);
            $execution->setFinishedAt($this->clock->now());
        }
    }

    /** @param array<string, mixed> $response */
    private function shouldMarkAsCompleted(WorkflowExecution $execution, array $response): bool
    {
        return $execution->isBlocking() && isset($response['data']);
    }

    private function createWorkflowTask(WorkflowExecution $execution, string $taskId): WorkflowTask
    {
        $task = new WorkflowTask();
        $task->setExecution($execution);
        $task->setTaskId($taskId);
        $task->setStatus(WorkflowStatus::RUNNING);
        $task->setCreateTime($this->clock->now());

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    /** @return array<string, mixed> */
    private function getWorkflowTaskStatus(DifySetting $setting, string $taskId): array
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/workflows/tasks/' . $taskId;

        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
            ],
            'timeout' => $setting->getTimeout(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Failed to get workflow task status: ' . $response->getContent(false));
        }

        /** @var array<string, mixed> */
        return $response->toArray();
    }

    private function stopDifyWorkflowTask(DifySetting $setting, string $taskId): void
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/workflows/tasks/' . $taskId . '/stop';

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
            ],
            'timeout' => $setting->getTimeout(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Failed to stop Dify workflow task: ' . $response->getContent(false));
        }
    }

    private function parseStreamChunk(string $chunk): string
    {
        $lines = explode("\n", trim($chunk));

        foreach ($lines as $line) {
            $content = $this->extractDataFromLine($line);
            if ('' !== $content) {
                return $content;
            }
        }

        return '';
    }

    private function extractDataFromLine(string $line): string
    {
        if (!str_starts_with($line, 'data: ')) {
            return '';
        }

        $data = substr($line, 6);
        if ('[DONE]' === $data) {
            return '';
        }

        return $this->parseJsonData($data);
    }

    private function parseJsonData(string $data): string
    {
        $decoded = json_decode($data, true);
        if (JSON_ERROR_NONE !== json_last_error() || !is_array($decoded) || !isset($decoded['data'])) {
            return '';
        }

        if (is_string($decoded['data'])) {
            return $decoded['data'];
        }

        $encoded = json_encode($decoded['data']);

        return false !== $encoded ? $encoded : '';
    }

    private function handleExecutionFailure(WorkflowExecution $execution, \Exception $e): void
    {
        $execution->setStatus(WorkflowStatus::FAILED);
        $execution->setFinishedAt($this->clock->now());
        $execution->setErrorMessage($e->getMessage());
        $this->persistExecution($execution);

        $this->addWorkflowLog($execution, 'error', $e->getMessage(), [
            'exception_class' => get_class($e),
            'exception_code' => $e->getCode(),
        ]);
    }
}
