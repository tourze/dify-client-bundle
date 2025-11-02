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
 * Dify 标注实体
 *
 * 存储消息的标注信息
 * 对应 API: GET/POST/PUT/DELETE /annotations
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_annotation', options: ['comment' => 'Dify 标注表'])]
class Annotation implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[IndexColumn]
    #[ORM\Column(name: 'annotationId', type: Types::STRING, length: 255, unique: true, nullable: true, options: ['comment' => 'Dify返回的标注ID'])]
    #[Assert\Length(max: 255)]
    private ?string $annotationId = null;

    #[ORM\ManyToOne(targetEntity: Message::class)]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '关联的消息'])]
    private ?Message $message = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '所属的会话'])]
    private ?Conversation $conversation = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '用户提问内容'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 65535)]
    private string $question;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '标注的回答内容'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 65535)]
    private string $answer;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '创建标注的用户ID'])]
    #[Assert\Length(max: 255)]
    private ?string $userId = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '标注的匹配次数'])]
    #[Assert\PositiveOrZero]
    private int $hitCount = 0;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '标注的相似度阈值'])]
    #[Assert\Range(min: 0.0, max: 1.0)]
    private ?float $similarityThreshold = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否启用此标注'])]
    #[Assert\Type(type: 'bool')]
    private bool $enabled = true;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '标注的额外元数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    #[IndexColumn]
    #[ORM\Column(name: 'lastHitAt', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后一次匹配的时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $lastHitAt = null;

    public function getAnnotationId(): ?string
    {
        return $this->annotationId;
    }

    public function setAnnotationId(?string $annotationId): void
    {
        $this->annotationId = $annotationId;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): void
    {
        $this->message = $message;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): void
    {
        $this->conversation = $conversation;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function setQuestion(string $question): void
    {
        $this->question = $question;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): void
    {
        $this->answer = $answer;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    public function getHitCount(): int
    {
        return $this->hitCount;
    }

    public function setHitCount(int $hitCount): void
    {
        $this->hitCount = $hitCount;
    }

    public function getSimilarityThreshold(): ?float
    {
        return $this->similarityThreshold;
    }

    public function setSimilarityThreshold(?float $similarityThreshold): void
    {
        $this->similarityThreshold = $similarityThreshold;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
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

    public function getLastHitAt(): ?\DateTimeImmutable
    {
        return $this->lastHitAt;
    }

    public function setLastHitAt(?\DateTimeImmutable $lastHitAt): void
    {
        $this->lastHitAt = $lastHitAt;
    }

    public function __toString(): string
    {
        $questionPreview = mb_substr($this->question, 0, 50);

        return sprintf('标注: %s%s', $questionPreview, mb_strlen($this->question) > 50 ? '...' : '');
    }
}
