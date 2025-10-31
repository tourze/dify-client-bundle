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
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;

/**
 * Dify 文档块实体
 *
 * 存储知识库文档分块的内容和信息
 * 对应 API: GET/POST/PUT/DELETE /datasets/{dataset_id}/documents/{document_id}/segments
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_document_chunk', options: ['comment' => 'Dify 文档块表'])]
class DocumentChunk implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use CreatedByAware;

    #[IndexColumn]
    #[ORM\Column(name: 'segmentId', type: Types::STRING, length: 255, unique: true, options: ['comment' => 'Dify返回的分块ID'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $segmentId;

    #[ORM\ManyToOne(targetEntity: Document::class, inversedBy: 'chunks')]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '所属的文档'])]
    private ?Document $document = null;

    #[IndexColumn]
    #[ORM\Column(name: 'position', type: Types::INTEGER, options: ['comment' => '在文档中的位置顺序'])]
    #[Assert\PositiveOrZero]
    private int $position;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '分块的内容文本'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 65535)]
    private string $content;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '分块的字符数量'])]
    #[Assert\PositiveOrZero]
    private int $characterCount;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '分块的单词数量'])]
    #[Assert\PositiveOrZero]
    private int $wordCount;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '分块的token数量'])]
    #[Assert\PositiveOrZero]
    private int $tokenCount;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '分块的哈希值，用于去重'])]
    #[Assert\Length(max: 255)]
    private ?string $hash = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否启用此分块'])]
    #[Assert\Type(type: 'bool')]
    private bool $enabled = true;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '此分块被匹配的次数'])]
    #[Assert\PositiveOrZero]
    private int $hitCount = 0;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '分块的额外元数据，如关键词、摘要等'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '分块索引时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $indexedAt = null;

    #[IndexColumn]
    #[ORM\Column(name: 'lastHitAt', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后一次被匹配的时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $lastHitAt = null;

    public function getSegmentId(): string
    {
        return $this->segmentId;
    }

    public function setSegmentId(string $segmentId): void
    {
        $this->segmentId = $segmentId;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): void
    {
        $this->document = $document;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getCharacterCount(): int
    {
        return $this->characterCount;
    }

    public function setCharacterCount(int $characterCount): void
    {
        $this->characterCount = $characterCount;
    }

    public function getWordCount(): int
    {
        return $this->wordCount;
    }

    public function setWordCount(int $wordCount): void
    {
        $this->wordCount = $wordCount;
    }

    public function getTokenCount(): int
    {
        return $this->tokenCount;
    }

    public function setTokenCount(int $tokenCount): void
    {
        $this->tokenCount = $tokenCount;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(?string $hash): void
    {
        $this->hash = $hash;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getHitCount(): int
    {
        return $this->hitCount;
    }

    public function setHitCount(int $hitCount): void
    {
        $this->hitCount = $hitCount;
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

    public function getIndexedAt(): ?\DateTimeImmutable
    {
        return $this->indexedAt;
    }

    public function setIndexedAt(?\DateTimeImmutable $indexedAt): void
    {
        $this->indexedAt = $indexedAt;
    }

    public function getLastHitAt(): ?\DateTimeImmutable
    {
        return $this->lastHitAt;
    }

    public function setLastHitAt(?\DateTimeImmutable $lastHitAt): void
    {
        $this->lastHitAt = $lastHitAt;
    }

    public function getContentPreview(int $length = 100): string
    {
        return mb_substr($this->content, 0, $length) . (mb_strlen($this->content) > $length ? '...' : '');
    }

    public function __toString(): string
    {
        return sprintf('分块 #%d: %s', $this->position, $this->getContentPreview(50));
    }
}
