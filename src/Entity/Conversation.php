<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DifyClientBundle\Enum\ConversationStatus;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;

/**
 * Dify AI 会话实体
 *
 * 表示与 Dify AI 的一次完整对话会话
 * 包含会话状态、Dify 返回的会话ID、最后活跃时间等信息
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_conversation', options: ['comment' => 'Dify AI 会话表'])]
class Conversation implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use CreatedByAware;

    #[IndexColumn]
    #[ORM\Column(name: 'conversationId', type: Types::STRING, length: 255, unique: true, options: ['comment' => 'Dify返回的会话ID，用于关联对话上下文'])]
    #[Assert\Length(max: 255)]
    private ?string $conversationId = null;

    #[IndexColumn]
    #[ORM\Column(name: 'status', type: Types::STRING, enumType: ConversationStatus::class, options: ['default' => 'active', 'comment' => '会话状态：active-活跃，closed-已关闭'])]
    #[Assert\Choice(callback: [ConversationStatus::class, 'cases'])]
    private ConversationStatus $status = ConversationStatus::ACTIVE;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '会话摘要信息，可由AI生成或手动设置'])]
    #[Assert\Length(max: 65535)]
    private ?string $summary = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户ID，用于标识会话所属用户'])]
    #[Assert\Length(max: 255)]
    private ?string $userId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '会话名称，用于显示和标识'])]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '会话元数据，存储额外的配置信息'])]
    #[Assert\Type(type: 'array', message: '元数据必须是数组')]
    private ?array $metadata = null;

    #[IndexColumn]
    #[ORM\Column(name: 'lastActive', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后活跃时间，用于会话管理和清理'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $lastActive = null;

    #[ORM\Column(name: 'archivedAt', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '归档时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $archivedAt = null;

    /** @var Collection<int, Message> */
    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: Message::class)]
    private Collection $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getConversationId(): ?string
    {
        return $this->conversationId;
    }

    public function setConversationId(?string $conversationId): void
    {
        $this->conversationId = $conversationId;
    }

    public function getStatus(): ConversationStatus
    {
        return $this->status;
    }

    public function setStatus(ConversationStatus $status): void
    {
        $this->status = $status;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): void
    {
        $this->summary = $summary;
    }

    public function getLastActive(): ?\DateTimeImmutable
    {
        return $this->lastActive;
    }

    public function setLastActive(?\DateTimeImmutable $lastActive): void
    {
        $this->lastActive = $lastActive;
    }

    public function getArchivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?\DateTimeImmutable $archivedAt): void
    {
        $this->archivedAt = $archivedAt;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
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
     * 获取会话名称
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * 设置会话名称
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /** @return Collection<int, Message> */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            // 由于 Message 的 conversation 属性不能为 null，我们需要保持引用
            // 实际业务中可能需要删除 Message 或重新分配到其他会话
        }

        return $this;
    }

    /**
     * 设置创建时间
     */
    public function setCreateTime(\DateTimeImmutable $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function __toString(): string
    {
        return $this->conversationId ?? $this->id ?? '会话';
    }
}
