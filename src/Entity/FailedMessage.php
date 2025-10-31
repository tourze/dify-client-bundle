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
 * Dify AI 失败消息实体
 *
 * 记录发送给 Dify AI 失败的消息信息
 * 支持重试机制和错误追踪
 * 包含与原始消息、会话、请求任务的关联关系
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_failed_message', options: ['comment' => 'Dify AI 失败消息表'])]
class FailedMessage implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '关联的会话，记录失败消息所属的对话上下文'])]
    private ?Conversation $conversation = null;

    #[ORM\ManyToOne(targetEntity: Message::class)]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '关联的原始消息，记录失败的具体消息内容'])]
    private ?Message $message = null;

    #[ORM\ManyToOne(targetEntity: RequestTask::class, inversedBy: 'failedMessages')]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '关联的请求任务，用于批次重试'])]
    private ?RequestTask $requestTask = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '错误信息，描述消息失败的具体原因'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 65535)]
    private string $error;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '失败时的尝试次数'])]
    #[Assert\PositiveOrZero]
    private int $attempts;

    #[IndexColumn]
    #[ORM\Column(name: 'failTime', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '消息失败的时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $failTime = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '失败时的上下文信息，如异常类型、错误码等'])]
    #[Assert\All(constraints: [new Assert\Type('mixed')])]
    private ?array $context = null;

    #[IndexColumn]
    #[ORM\Column(name: 'retried', type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否已经重试过，防止重复重试'])]
    #[Assert\Type(type: 'bool')]
    private bool $retried = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => 'Messenger任务ID，用于追踪和重试'])]
    #[Assert\Length(max: 255)]
    private ?string $taskId = null;

    /** @var array<int, array<string, mixed>>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '重试历史记录，包含每次重试的时间和结果'])]
    #[Assert\All(constraints: [new Assert\Type('mixed')])]
    private ?array $retryHistory = null;

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): void
    {
        $this->conversation = $conversation;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): void
    {
        $this->message = $message;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function setError(string $error): void
    {
        $this->error = $error;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setAttempts(int $attempts): void
    {
        $this->attempts = $attempts;
    }

    public function getFailedAt(): ?\DateTimeImmutable
    {
        return $this->failTime;
    }

    public function setFailedAt(?\DateTimeImmutable $failedAt): void
    {
        $this->failTime = $failedAt;
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

    public function isRetried(): bool
    {
        return $this->retried;
    }

    public function setRetried(bool $retried): void
    {
        $this->retried = $retried;
    }

    public function getTaskId(): ?string
    {
        return $this->taskId;
    }

    public function setTaskId(?string $taskId): void
    {
        $this->taskId = $taskId;
    }

    /** @return array<int, array<string, mixed>>|null */
    public function getRetryHistory(): ?array
    {
        return $this->retryHistory;
    }

    /** @param array<int, array<string, mixed>>|null $retryHistory */
    public function setRetryHistory(?array $retryHistory): void
    {
        $this->retryHistory = $retryHistory;
    }

    public function addRetryAttempt(\DateTimeImmutable $retryTime, string $result): void
    {
        if (null === $this->retryHistory) {
            $this->retryHistory = [];
        }

        $this->retryHistory[] = [
            'timestamp' => $retryTime->format(\DateTimeInterface::ATOM),
            'result' => $result,
        ];
    }

    public function getRequestTask(): ?RequestTask
    {
        return $this->requestTask;
    }

    public function setRequestTask(?RequestTask $requestTask): void
    {
        $this->requestTask = $requestTask;
    }

    public function __toString(): string
    {
        return sprintf('失败消息 #%s (%s次尝试)', $this->id, $this->attempts);
    }
}
