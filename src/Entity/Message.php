<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DifyClientBundle\Enum\MessageRole;
use Tourze\DifyClientBundle\Enum\MessageStatus;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;

/**
 * Dify AI 消息实体
 *
 * 表示一次对话中的单条消息记录
 * 可以是用户发送的消息或 AI 的回复消息
 * 支持消息聚合、重试等高级功能
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_message', options: ['comment' => 'Dify AI 消息表'])]
class Message implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use CreatedByAware;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '所属的会话，消息必须关联到一个会话'])]
    private Conversation $conversation;

    #[IndexColumn]
    #[ORM\Column(name: 'role', type: Types::STRING, enumType: MessageRole::class, options: ['comment' => '消息角色：user-用户消息，assistant-AI回复'])]
    #[Assert\Choice(callback: [MessageRole::class, 'cases'])]
    private MessageRole $role;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '消息内容，用户输入或AI回复的文本'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 65535)]
    private string $content;

    #[IndexColumn]
    #[ORM\Column(name: 'status', type: Types::STRING, enumType: MessageStatus::class, options: ['default' => 'pending', 'comment' => '消息状态：pending-待发送，sent-已发送，received-已接收，failed-失败，aggregated-已聚合'])]
    #[Assert\Choice(callback: [MessageStatus::class, 'cases'])]
    private MessageStatus $status = MessageStatus::PENDING;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '消息发送到Dify的时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $sentTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '收到Dify回复的时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $receiveTime = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '消息的元数据信息，如聚合信息、重试信息等'])]
    #[Assert\All(constraints: [new Assert\Type('mixed')])]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '消息的重试次数'])]
    #[Assert\PositiveOrZero]
    #[Assert\Range(min: 0, max: 10)]
    private int $retryCount = 0;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '错误信息，当消息发送失败时记录'])]
    #[Assert\Length(max: 255)]
    private ?string $errorMessage = null;

    #[ORM\ManyToOne(targetEntity: RequestTask::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '关联的请求任务，用于批次管理和重试'])]
    private ?RequestTask $requestTask = null;

    public function getConversation(): Conversation
    {
        return $this->conversation;
    }

    public function setConversation(Conversation $conversation): void
    {
        $this->conversation = $conversation;
    }

    public function getRole(): MessageRole
    {
        return $this->role;
    }

    public function setRole(MessageRole $role): void
    {
        $this->role = $role;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getStatus(): MessageStatus
    {
        return $this->status;
    }

    public function setStatus(MessageStatus $status): void
    {
        $this->status = $status;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentTime;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): void
    {
        $this->sentTime = $sentAt;
    }

    public function getReceivedAt(): ?\DateTimeImmutable
    {
        return $this->receiveTime;
    }

    public function setReceivedAt(?\DateTimeImmutable $receivedAt): void
    {
        $this->receiveTime = $receivedAt;
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

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function setRetryCount(int $retryCount): void
    {
        $this->retryCount = $retryCount;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    public function getRequestTask(): ?RequestTask
    {
        return $this->requestTask;
    }

    public function setRequestTask(?RequestTask $requestTask): void
    {
        $this->requestTask = $requestTask;
    }

    /**
     * 获取消息ID（SnowflakeKey的别名方法）
     */
    public function getMessageId(): ?string
    {
        return $this->getId();
    }

    public function __toString(): string
    {
        return sprintf('%s消息: %s...', $this->role->getLabel(), mb_substr($this->content, 0, 50));
    }
}
