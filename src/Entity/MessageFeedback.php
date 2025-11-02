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
 * Dify 消息反馈实体
 *
 * 存储用户对消息的反馈信息（点赞/点踩）
 * 对应 API: POST /messages/{message_id}/feedbacks, GET /feedbacks
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_message_feedback', options: ['comment' => 'Dify 消息反馈表'])]
class MessageFeedback implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[IndexColumn]
    #[ORM\Column(name: 'feedbackId', type: Types::STRING, length: 255, unique: true, nullable: true, options: ['comment' => 'Dify返回的反馈ID'])]
    #[Assert\Length(max: 255)]
    private ?string $feedbackId = null;

    #[ORM\ManyToOne(targetEntity: Message::class)]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '被反馈的消息'])]
    private Message $message;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '所属的会话'])]
    private Conversation $conversation;

    #[IndexColumn]
    #[ORM\Column(name: 'rating', type: Types::STRING, length: 20, options: ['comment' => '反馈类型：like-点赞，dislike-点踩'])]
    #[Assert\Length(max: 20)]
    #[Assert\Choice(choices: ['like', 'dislike'])]
    private string $rating;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '提供反馈的用户ID'])]
    #[Assert\Length(max: 255)]
    private ?string $userId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '详细的反馈内容或建议'])]
    #[Assert\Length(max: 65535)]
    private ?string $content = null;

    /** @var array<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '反馈的标签分类，如["响应速度", "内容准确性"]'])]
    #[Assert\Type(type: 'array')]
    private ?array $tags = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '反馈的额外元数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否已被处理或回复'])]
    #[Assert\Type(type: 'bool')]
    private bool $processed = false;

    #[IndexColumn]
    #[ORM\Column(name: 'submittedAt', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '反馈提交时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $submittedAt = null;

    public function getFeedbackId(): ?string
    {
        return $this->feedbackId;
    }

    public function setFeedbackId(?string $feedbackId): void
    {
        $this->feedbackId = $feedbackId;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function setMessage(Message $message): void
    {
        $this->message = $message;
    }

    public function getConversation(): Conversation
    {
        return $this->conversation;
    }

    public function setConversation(Conversation $conversation): void
    {
        $this->conversation = $conversation;
    }

    public function getRating(): string
    {
        return $this->rating;
    }

    public function setRating(string $rating): void
    {
        $this->rating = $rating;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    /** @return ?array<string> */
    public function getTags(): ?array
    {
        return $this->tags;
    }

    /** @param ?array<string> $tags */
    public function setTags(?array $tags): void
    {
        $this->tags = $tags;
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

    public function isProcessed(): bool
    {
        return $this->processed;
    }

    public function setProcessed(bool $processed): void
    {
        $this->processed = $processed;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?\DateTimeImmutable $submittedAt): void
    {
        $this->submittedAt = $submittedAt;
    }

    public function isPositive(): bool
    {
        return 'like' === $this->rating;
    }

    public function isNegative(): bool
    {
        return 'dislike' === $this->rating;
    }

    /**
     * 设置更新时间
     */
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updateTime = $updatedAt;
    }

    /**
     * 设置评论内容（setContent 的别名）
     */
    public function setComment(?string $comment): void
    {
        $this->setContent($comment);
    }

    public function __toString(): string
    {
        return sprintf('%s反馈 (用户:%s)', 'like' === $this->rating ? '点赞' : '点踩', $this->userId ?? '匿名');
    }
}
