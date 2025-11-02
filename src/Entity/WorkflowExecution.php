<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DifyClientBundle\Enum\WorkflowStatus;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

/**
 * Dify 工作流执行实体
 *
 * 存储工作流的执行记录
 * 对应 API: POST /workflows/run
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_workflow_execution', options: ['comment' => 'Dify 工作流执行表'])]
class WorkflowExecution implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[IndexColumn]
    #[ORM\Column(name: 'workflowRunId', type: Types::STRING, length: 255, unique: true, options: ['comment' => 'Dify返回的工作流运行ID'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $workflowRunId;

    #[IndexColumn]
    #[ORM\Column(name: 'taskId', type: Types::STRING, length: 255, unique: true, nullable: true, options: ['comment' => '工作流任务ID'])]
    #[Assert\Length(max: 255)]
    private ?string $taskId = null;

    #[IndexColumn]
    #[ORM\Column(name: 'workflowId', type: Types::STRING, length: 255, nullable: true, options: ['comment' => 'Dify工作流ID，用于标识具体的工作流'])]
    #[Assert\Length(max: 255)]
    private ?string $workflowId = null;

    #[ORM\Column(name: 'blocking', type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否为阻塞执行模式'])]
    #[Assert\Type(type: 'bool')]
    private bool $blocking = false;

    #[IndexColumn]
    #[ORM\Column(name: 'executionId', type: Types::STRING, length: 255, nullable: true, options: ['comment' => '执行ID，用于追踪特定的执行实例'])]
    #[Assert\Length(max: 255)]
    private ?string $executionId = null;

    #[IndexColumn]
    #[ORM\Column(name: 'status', type: Types::STRING, enumType: WorkflowStatus::class, options: ['default' => 'pending', 'comment' => '工作流执行状态'])]
    #[Assert\Choice(callback: [WorkflowStatus::class, 'cases'])]
    private WorkflowStatus $status = WorkflowStatus::PENDING;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '工作流输入参数'])]
    #[Assert\Type(type: 'array')]
    private ?array $inputs = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '工作流输出结果'])]
    #[Assert\Type(type: 'array')]
    private ?array $outputs = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '执行工作流的用户ID'])]
    #[Assert\Length(max: 255)]
    private ?string $userId = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '工作流总步数'])]
    #[Assert\PositiveOrZero]
    private ?int $totalSteps = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '已完成步数'])]
    #[Assert\PositiveOrZero]
    private int $completedSteps = 0;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '执行耗时（秒）'])]
    #[Assert\PositiveOrZero]
    private ?float $elapsedTime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '错误信息（执行失败时）'])]
    #[Assert\Length(max: 65535)]
    private ?string $errorMessage = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '工作流的额外元数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '开始执行时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $startedAt = null;

    #[IndexColumn]
    #[ORM\Column(name: 'finishedAt', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '完成执行时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '总Token消耗'])]
    #[Assert\PositiveOrZero]
    private ?int $totalTokens = null;

    /** @var Collection<int, WorkflowTask> */
    #[ORM\OneToMany(mappedBy: 'workflowExecution', targetEntity: WorkflowTask::class, cascade: ['persist'])]
    private Collection $tasks;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
    }

    public function getWorkflowRunId(): string
    {
        return $this->workflowRunId;
    }

    public function setWorkflowRunId(string $workflowRunId): void
    {
        $this->workflowRunId = $workflowRunId;
    }

    public function getTaskId(): ?string
    {
        return $this->taskId;
    }

    public function setTaskId(?string $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function getStatus(): WorkflowStatus
    {
        return $this->status;
    }

    public function setStatus(WorkflowStatus $status): void
    {
        $this->status = $status;
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

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    public function getTotalSteps(): ?int
    {
        return $this->totalSteps;
    }

    public function setTotalSteps(?int $totalSteps): void
    {
        $this->totalSteps = $totalSteps;
    }

    public function getCompletedSteps(): int
    {
        return $this->completedSteps;
    }

    public function setCompletedSteps(int $completedSteps): void
    {
        $this->completedSteps = $completedSteps;
    }

    public function getElapsedTime(): ?float
    {
        return $this->elapsedTime;
    }

    public function setElapsedTime(?float $elapsedTime): void
    {
        $this->elapsedTime = $elapsedTime;
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
     * 获取工作流ID
     */
    public function getWorkflowId(): ?string
    {
        return $this->workflowId;
    }

    /**
     * 设置工作流ID
     */
    public function setWorkflowId(?string $workflowId): void
    {
        $this->workflowId = $workflowId;
    }

    /**
     * 获取是否为阻塞执行模式
     */
    public function isBlocking(): bool
    {
        return $this->blocking;
    }

    /**
     * 设置是否为阻塞执行模式
     */
    public function setBlocking(bool $blocking): void
    {
        $this->blocking = $blocking;
    }

    /**
     * 获取执行ID
     */
    public function getExecutionId(): ?string
    {
        return $this->executionId;
    }

    /**
     * 设置执行ID
     */
    public function setExecutionId(?string $executionId): void
    {
        $this->executionId = $executionId;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): void
    {
        $this->finishedAt = $finishedAt;
    }

    /** @return Collection<int, WorkflowTask> */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(WorkflowTask $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setWorkflowExecution($this);
        }

        return $this;
    }

    public function removeTask(WorkflowTask $task): self
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getWorkflowExecution() === $this) {
                $task->setWorkflowExecution(null);
            }
        }

        return $this;
    }

    public function getProgressPercentage(): float
    {
        if (null === $this->totalSteps || 0 === $this->totalSteps) {
            return 0.0;
        }

        return round(($this->completedSteps / $this->totalSteps) * 100, 2);
    }

    public function getTotalTokens(): ?int
    {
        return $this->totalTokens;
    }

    public function setTotalTokens(?int $totalTokens): void
    {
        $this->totalTokens = $totalTokens;
    }

    public function __toString(): string
    {
        return sprintf('工作流执行 %s (%s)', $this->taskId, $this->status->getLabel());
    }
}
