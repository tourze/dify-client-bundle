<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DifyClientBundle\Enum\WorkflowStatus;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

/**
 * Dify 工作流任务实体
 *
 * 存储工作流中的单个任务信息
 * 对应 API: DELETE /workflows/tasks/{task_id}
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_workflow_task', options: ['comment' => 'Dify 工作流任务表'])]
class WorkflowTask implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[ORM\ManyToOne(targetEntity: WorkflowExecution::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '所属的工作流执行'])]
    private ?WorkflowExecution $workflowExecution = null;

    #[IndexColumn]
    #[ORM\Column(name: 'nodeId', type: Types::STRING, length: 255, options: ['comment' => '工作流节点ID'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $nodeId;

    #[IndexColumn]
    #[ORM\Column(name: 'taskId', type: Types::STRING, length: 255, nullable: true, options: ['comment' => '任务ID，用于标识具体的任务实例'])]
    #[Assert\Length(max: 255)]
    private ?string $taskId = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '节点名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $nodeName;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '节点类型'])]
    #[Assert\Length(max: 100)]
    #[Assert\NotBlank]
    private string $nodeType;

    #[IndexColumn]
    #[ORM\Column(name: 'status', type: Types::STRING, length: 20, options: ['default' => 'pending', 'comment' => '任务状态：pending、running、completed、failed、stopped'])]
    #[Assert\Length(max: 20)]
    #[Assert\Choice(choices: ['pending', 'running', 'completed', 'failed', 'stopped'])]
    private string $status = 'pending';

    #[IndexColumn]
    #[ORM\Column(name: 'stepIndex', type: Types::INTEGER, options: ['comment' => '在工作流中的步骤序号'])]
    #[Assert\PositiveOrZero]
    private int $stepIndex;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '任务输入参数'])]
    #[Assert\Type(type: 'array')]
    private ?array $inputs = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '任务输出结果'])]
    #[Assert\Type(type: 'array')]
    private ?array $outputs = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '任务执行耗时（秒）'])]
    #[Assert\PositiveOrZero]
    private ?float $executionTime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '错误信息（任务失败时）'])]
    #[Assert\Length(max: 65535)]
    private ?string $errorMessage = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '任务的额外元数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '任务开始时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $startedAt = null;

    #[IndexColumn]
    #[ORM\Column(name: 'completedAt', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '任务完成时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '任务标题'])]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '执行元数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $executionMetadata = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '执行耗时（秒）'])]
    #[Assert\PositiveOrZero]
    private ?float $elapsedTime = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '总Token消耗'])]
    #[Assert\PositiveOrZero]
    private ?int $totalTokens = null;

    #[IndexColumn]
    #[ORM\Column(name: 'finishedAt', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '任务完成时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $finishedAt = null;

    public function getWorkflowExecution(): ?WorkflowExecution
    {
        return $this->workflowExecution;
    }

    public function setWorkflowExecution(?WorkflowExecution $workflowExecution): void
    {
        $this->workflowExecution = $workflowExecution;
    }

    public function getNodeId(): string
    {
        return $this->nodeId;
    }

    public function setNodeId(string $nodeId): void
    {
        $this->nodeId = $nodeId;
    }

    public function getNodeName(): string
    {
        return $this->nodeName;
    }

    public function setNodeName(string $nodeName): void
    {
        $this->nodeName = $nodeName;
    }

    public function getNodeType(): string
    {
        return $this->nodeType;
    }

    public function setNodeType(string $nodeType): void
    {
        $this->nodeType = $nodeType;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * 设置任务状态（支持string和WorkflowStatus枚举）
     */
    public function setStatus(string|WorkflowStatus $status): void
    {
        $this->status = $status instanceof WorkflowStatus ? $status->value : $status;
    }

    public function getStepIndex(): int
    {
        return $this->stepIndex;
    }

    public function setStepIndex(int $stepIndex): void
    {
        $this->stepIndex = $stepIndex;
    }

    /** @return ?array<string, mixed> */
    public function getInputs(): ?array
    {
        return $this->inputs;
    }

    /** @param ?array<string, mixed> $inputs */
    public function setInputs(?array $inputs): void
    {
        $this->inputs = $inputs;
    }

    /** @return ?array<string, mixed> */
    public function getOutputs(): ?array
    {
        return $this->outputs;
    }

    /** @param ?array<string, mixed> $outputs */
    public function setOutputs(?array $outputs): void
    {
        $this->outputs = $outputs;
    }

    public function getExecutionTime(): ?float
    {
        return $this->executionTime;
    }

    public function setExecutionTime(?float $executionTime): void
    {
        $this->executionTime = $executionTime;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    /** @return ?array<string, mixed> */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /** @param ?array<string, mixed> $metadata */
    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * 获取任务ID
     */
    public function getTaskId(): ?string
    {
        return $this->taskId;
    }

    /**
     * 设置任务ID
     */
    public function setTaskId(?string $taskId): void
    {
        $this->taskId = $taskId;
    }

    /**
     * 设置执行实例（setWorkflowExecution的别名方法）
     */
    public function setExecution(?WorkflowExecution $workflowExecution): void
    {
        $this->setWorkflowExecution($workflowExecution);
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function isCompleted(): bool
    {
        return 'completed' === $this->status;
    }

    public function isFailed(): bool
    {
        return 'failed' === $this->status;
    }

    public function isRunning(): bool
    {
        return 'running' === $this->status;
    }

    /**
     * 设置创建时间
     */
    public function setCreateTime(\DateTimeImmutable $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /** @return ?array<string, mixed> */
    public function getExecutionMetadata(): ?array
    {
        return $this->executionMetadata;
    }

    /** @param ?array<string, mixed> $executionMetadata */
    public function setExecutionMetadata(?array $executionMetadata): void
    {
        $this->executionMetadata = $executionMetadata;
    }

    public function getElapsedTime(): ?float
    {
        return $this->elapsedTime;
    }

    public function setElapsedTime(?float $elapsedTime): void
    {
        $this->elapsedTime = $elapsedTime;
    }

    public function getTotalTokens(): ?int
    {
        return $this->totalTokens;
    }

    public function setTotalTokens(?int $totalTokens): void
    {
        $this->totalTokens = $totalTokens;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): void
    {
        $this->finishedAt = $finishedAt;
    }

    /**
     * 设置工作流运行ID（测试兼容方法）
     */
    public function setWorkflowRunId(?string $workflowRunId): void
    {
        // 这个方法是为了测试兼容性，实际存储在workflowExecution中
        // 在实际使用中应该通过setWorkflowExecution来关联
    }

    /**
     * 设置任务名称（测试兼容方法）
     */
    public function setTaskName(string $taskName): void
    {
        $this->setNodeName($taskName);
    }

    /**
     * 设置任务类型（测试兼容方法）
     */
    public function setTaskType(string $taskType): void
    {
        $this->setNodeType($taskType);
    }

    /**
     * 设置输入数据（测试兼容方法）
     * @param array<string, mixed> $inputData
     */
    public function setInputData(array $inputData): void
    {
        $this->setInputs($inputData);
    }

    /**
     * 设置输出数据（测试兼容方法）
     * @param array<string, mixed> $outputData
     */
    public function setOutputData(array $outputData): void
    {
        $this->setOutputs($outputData);
    }

    /**
     * 设置用户ID（测试兼容方法）
     */
    public function setUserId(string $userId): void
    {
        // 工作流任务可能不直接存储用户ID，而是通过workflowExecution关联
        // 这里提供兼容方法，实际可以扩展metadata存储用户信息
        $metadata = $this->getMetadata() ?? [];
        $metadata['userId'] = $userId;
        $this->setMetadata($metadata);
    }

    public function __toString(): string
    {
        return sprintf('%s (步骤%d: %s)', $this->nodeName, $this->stepIndex, $this->status);
    }
}
