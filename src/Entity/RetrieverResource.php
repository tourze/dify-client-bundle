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
 * Dify 检索资源实体
 *
 * 存储从知识库检索到的资源信息
 * 对应 API 响应中的 retriever_resources 字段
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_retriever_resource', options: ['comment' => 'Dify 检索资源表'])]
class RetrieverResource implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[ORM\ManyToOne(targetEntity: Message::class)]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '关联的消息'])]
    private ?Message $message = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '所属的会话'])]
    private ?Conversation $conversation = null;

    #[ORM\ManyToOne(targetEntity: Dataset::class)]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '关联的数据集'])]
    private ?Dataset $dataset = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '检索查询内容'])]
    #[Assert\Length(max: 65535)]
    private ?string $query = null;

    #[IndexColumn]
    #[ORM\Column(name: 'position', type: Types::INTEGER, options: ['comment' => '在检索结果中的位置'])]
    #[Assert\PositiveOrZero]
    private int $position;

    #[IndexColumn]
    #[ORM\Column(name: 'datasetId', type: Types::STRING, length: 255, options: ['comment' => '数据集ID'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $datasetId;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '数据集名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $datasetName;

    #[IndexColumn]
    #[ORM\Column(name: 'documentId', type: Types::STRING, length: 255, options: ['comment' => '文档ID'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $documentId;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '文档名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $documentName;

    #[IndexColumn]
    #[ORM\Column(name: 'segmentId', type: Types::STRING, length: 255, options: ['comment' => '文档段落ID'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $segmentId;

    #[ORM\Column(type: Types::FLOAT, options: ['comment' => '相似度评分'])]
    #[Assert\Range(min: 0.0, max: 1.0)]
    private float $score;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '检索到的内容'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 65535)]
    private string $content;

    #[ORM\Column(type: Types::STRING, length: 2048, nullable: true, options: ['comment' => '内容来源URL或文件路径'])]
    #[Assert\Length(max: 2048)]
    private ?string $sourceUrl = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '检索资源的额外元数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    #[IndexColumn]
    #[ORM\Column(name: 'retrievedAt', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '检索时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $retrievedAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '资源ID'])]
    #[Assert\Length(max: 255)]
    private ?string $resourceId = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '资源类型'])]
    #[Assert\Length(max: 100)]
    private ?string $resourceType = null;

    #[ORM\Column(type: Types::STRING, length: 2048, nullable: true, options: ['comment' => '资源URL'])]
    #[Assert\Length(max: 2048)]
    private ?string $resourceUrl = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '资源标题'])]
    #[Assert\Length(max: 500)]
    private ?string $title = null;

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

    public function getDataset(): ?Dataset
    {
        return $this->dataset;
    }

    public function setDataset(?Dataset $dataset): void
    {
        $this->dataset = $dataset;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(?string $query): void
    {
        $this->query = $query;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getDatasetId(): string
    {
        return $this->datasetId;
    }

    public function setDatasetId(string $datasetId): void
    {
        $this->datasetId = $datasetId;
    }

    public function getDatasetName(): string
    {
        return $this->datasetName;
    }

    public function setDatasetName(string $datasetName): void
    {
        $this->datasetName = $datasetName;
    }

    public function getDocumentId(): string
    {
        return $this->documentId;
    }

    public function setDocumentId(string $documentId): void
    {
        $this->documentId = $documentId;
    }

    public function getDocumentName(): string
    {
        return $this->documentName;
    }

    public function setDocumentName(string $documentName): void
    {
        $this->documentName = $documentName;
    }

    public function getSegmentId(): string
    {
        return $this->segmentId;
    }

    public function setSegmentId(string $segmentId): void
    {
        $this->segmentId = $segmentId;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): void
    {
        $this->score = $score;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function setSourceUrl(?string $sourceUrl): void
    {
        $this->sourceUrl = $sourceUrl;
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

    public function getRetrievedAt(): ?\DateTimeImmutable
    {
        return $this->retrievedAt;
    }

    public function setRetrievedAt(?\DateTimeImmutable $retrievedAt): void
    {
        $this->retrievedAt = $retrievedAt;
    }

    /**
     * 获取资源ID
     */
    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    /**
     * 设置资源ID
     */
    public function setResourceId(?string $resourceId): void
    {
        $this->resourceId = $resourceId;
    }

    /**
     * 获取资源类型
     */
    public function getResourceType(): ?string
    {
        return $this->resourceType;
    }

    /**
     * 设置资源类型
     */
    public function setResourceType(?string $resourceType): void
    {
        $this->resourceType = $resourceType;
    }

    /**
     * 获取资源URL
     */
    public function getResourceUrl(): ?string
    {
        return $this->resourceUrl;
    }

    /**
     * 设置资源URL
     */
    public function setResourceUrl(?string $resourceUrl): void
    {
        $this->resourceUrl = $resourceUrl;
    }

    /**
     * 获取标题
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * 设置标题
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function __toString(): string
    {
        return sprintf('检索资源 #%d (评分:%.3f): %s', $this->position, $this->score, mb_substr($this->content, 0, 50));
    }
}
