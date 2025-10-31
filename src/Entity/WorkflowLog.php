<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

/**
 * Dify 工作流日志实体
 *
 * 存储工作流执行的日志信息
 * 对应 API: GET /workflows/tasks/{task_id}/logs
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_workflow_log', options: ['comment' => 'Dify 工作流日志表'])]
class WorkflowLog implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[ORM\ManyToOne(targetEntity: WorkflowExecution::class)]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '所属的工作流执行'])]
    private ?WorkflowExecution $workflowExecution = null;

    #[ORM\ManyToOne(targetEntity: WorkflowTask::class)]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '关联的工作流任务'])]
    private ?WorkflowTask $workflowTask = null;

    #[IndexColumn]
    #[ORM\Column(name: 'logLevel', type: Types::STRING, length: 20, options: ['comment' => '日志级别：debug、info、warning、error'])]
    #[Assert\Length(max: 20)]
    #[Assert\Choice(choices: ['debug', 'info', 'warning', 'error'])]
    private string $logLevel;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '日志消息内容'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 65535)]
    private string $message;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '日志类别或标签'])]
    #[Assert\Length(max: 255)]
    private ?string $category = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '节点ID（如果日志与特定节点相关）'])]
    #[Assert\Length(max: 255)]
    private ?string $nodeId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '节点名称'])]
    #[Assert\Length(max: 255)]
    private ?string $nodeName = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '节点类型'])]
    #[Assert\Length(max: 100)]
    private ?string $nodeType = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '日志标题'])]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '输入参数'])]
    #[Assert\Type(type: 'array')]
    private ?array $inputs = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '输出结果'])]
    #[Assert\Type(type: 'array')]
    private ?array $outputs = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '执行耗时（秒）'])]
    #[Assert\PositiveOrZero]
    private ?float $elapsedTime = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '执行元数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $executionMetadata = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '步骤索引'])]
    #[Assert\PositiveOrZero]
    private ?int $stepIndex = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '日志的上下文数据，如变量值、错误详情等'])]
    #[Assert\Type(type: 'array')]
    private ?array $context = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '异常堆栈信息（错误日志）'])]
    #[Assert\Length(max: 65535)]
    private ?string $stackTrace = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '执行线程或进程ID'])]
    #[Assert\Length(max: 255)]
    private ?string $threadId = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '日志的额外元数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    #[IndexColumn]
    #[ORM\Column(name: 'loggedAt', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '日志记录时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $loggedAt = null;

    public function getWorkflowExecution(): ?WorkflowExecution
    {
        return $this->workflowExecution;
    }

    public function setWorkflowExecution(?WorkflowExecution $workflowExecution): void
    {
        $this->workflowExecution = $workflowExecution;
    }

    public function getWorkflowTask(): ?WorkflowTask
    {
        return $this->workflowTask;
    }

    public function setWorkflowTask(?WorkflowTask $workflowTask): void
    {
        $this->workflowTask = $workflowTask;
    }

    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    public function setLogLevel(string $logLevel): void
    {
        $this->logLevel = $logLevel;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function getNodeId(): ?string
    {
        return $this->nodeId;
    }

    public function setNodeId(?string $nodeId): void
    {
        $this->nodeId = $nodeId;
    }

    public function getNodeName(): ?string
    {
        return $this->nodeName;
    }

    public function setNodeName(?string $nodeName): void
    {
        $this->nodeName = $nodeName;
    }

    public function getStepIndex(): ?int
    {
        return $this->stepIndex;
    }

    public function setStepIndex(?int $stepIndex): void
    {
        $this->stepIndex = $stepIndex;
    }

    /** @return ?array<string, mixed> */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /** @param ?array<string, mixed> $context */
    public function setContext(?array $context): void
    {
        $this->context = $context;
    }

    public function getStackTrace(): ?string
    {
        return $this->stackTrace;
    }

    public function setStackTrace(?string $stackTrace): void
    {
        $this->stackTrace = $stackTrace;
    }

    public function getThreadId(): ?string
    {
        return $this->threadId;
    }

    public function setThreadId(?string $threadId): void
    {
        $this->threadId = $threadId;
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

    public function getLoggedAt(): ?\DateTimeImmutable
    {
        return $this->loggedAt;
    }

    public function setLoggedAt(?\DateTimeImmutable $loggedAt): void
    {
        $this->loggedAt = $loggedAt;
    }

    /**
     * 设置工作流执行（setWorkflowExecution的别名方法）
     */
    public function setExecution(?WorkflowExecution $workflowExecution): void
    {
        $this->setWorkflowExecution($workflowExecution);
    }

    /**
     * 获取日志级别（getLogLevel的别名方法）
     */
    public function getLevel(): string
    {
        return $this->getLogLevel();
    }

    /**
     * 设置日志级别（setLogLevel的别名方法）
     */
    public function setLevel(string $level): void
    {
        $this->setLogLevel($level);
    }

    /**
     * 设置创建时间
     */
    public function setCreateTime(\DateTimeImmutable $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getNodeType(): ?string
    {
        return $this->nodeType;
    }

    public function setNodeType(?string $nodeType): void
    {
        $this->nodeType = $nodeType;
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

    public function getElapsedTime(): ?float
    {
        return $this->elapsedTime;
    }

    public function setElapsedTime(?float $elapsedTime): void
    {
        $this->elapsedTime = $elapsedTime;
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

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createTime = $createdAt;
    }

    public function __toString(): string
    {
        $preview = mb_substr($this->message, 0, 50);

        return sprintf('[%s] %s%s', strtoupper($this->logLevel), $preview, mb_strlen($this->message) > 50 ? '...' : '');
    }
}
