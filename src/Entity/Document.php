<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;

/**
 * Dify 文档实体
 *
 * 存储知识库中的文档信息
 * 对应 API: GET/POST/PUT/DELETE /datasets/{dataset_id}/documents
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_document', options: ['comment' => 'Dify 文档表'])]
class Document implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use CreatedByAware;

    #[IndexColumn]
    #[ORM\Column(name: 'documentId', type: Types::STRING, length: 255, unique: true, nullable: true, options: ['comment' => 'Dify返回的文档ID'])]
    #[Assert\Length(max: 255)]
    private ?string $documentId = null;

    #[ORM\ManyToOne(targetEntity: Dataset::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '所属的数据集'])]
    private ?Dataset $dataset = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '文档名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '文档内容（文本创建时使用）'])]
    #[Assert\Length(max: 65535)]
    private ?string $text = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '原始文件名'])]
    #[Assert\Length(max: 255)]
    private ?string $originalFilename = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => 'MIME类型'])]
    #[Assert\Length(max: 100)]
    private ?string $mimeType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '文档完整内容'])]
    #[Assert\Length(max: 65535)]
    private ?string $content = null;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '文档数据来源：upload_file-文件上传，notion_import-Notion导入'])]
    #[Assert\Length(max: 100)]
    #[Assert\Choice(choices: ['upload_file', 'notion_import', 'text_input'])]
    private string $dataSource;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '索引结构：economy-经济型，high_quality-高质量'])]
    #[Assert\Length(max: 100)]
    #[Assert\Choice(choices: ['economy', 'high_quality'])]
    private string $indexingTechnique;

    #[IndexColumn]
    #[ORM\Column(name: 'indexingStatus', type: Types::STRING, length: 50, options: ['default' => 'pending', 'comment' => '索引状态：pending-待处理，parsing-解析中，cleaning-清理中，splitting-分割中，indexing-索引中，completed-已完成，error-错误，paused-已暂停'])]
    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['pending', 'parsing', 'cleaning', 'splitting', 'indexing', 'completed', 'error', 'paused'])]
    private string $indexingStatus = 'pending';

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否启用此文档'])]
    #[Assert\Type(type: 'bool')]
    private bool $enabled = true;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '文档字符数量'])]
    #[Assert\PositiveOrZero]
    private int $characterCount = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '文档分块数量'])]
    #[Assert\PositiveOrZero]
    private int $chunkCount = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '文件大小（字节）'])]
    #[Assert\PositiveOrZero]
    private ?int $fileSize = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '文件类型'])]
    #[Assert\Length(max: 100)]
    private ?string $fileType = null;

    #[ORM\Column(type: Types::STRING, length: 2048, nullable: true, options: ['comment' => '文件URL或路径'])]
    #[Assert\Length(max: 2048)]
    private ?string $fileUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '处理错误信息'])]
    #[Assert\Length(max: 65535)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '文档处理状态'])]
    #[Assert\Length(max: 50)]
    private ?string $processingStatus = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '文档字数统计'])]
    #[Assert\PositiveOrZero]
    private int $wordCount = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '文档令牌数量'])]
    #[Assert\PositiveOrZero]
    private int $tokens = 0;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户ID'])]
    #[Assert\Length(max: 255)]
    private ?string $userId = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '文档的额外元数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '索引开始时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $indexingStartedAt = null;

    #[IndexColumn]
    #[ORM\Column(name: 'indexingCompletedAt', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '索引完成时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $indexingCompletedAt = null;

    /** @var Collection<int, DocumentChunk> */
    #[ORM\OneToMany(mappedBy: 'document', targetEntity: DocumentChunk::class, cascade: ['persist'])]
    private Collection $chunks;

    public function __construct()
    {
        $this->chunks = new ArrayCollection();
    }

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    public function setDocumentId(?string $documentId): void
    {
        $this->documentId = $documentId;
    }

    public function getDataset(): ?Dataset
    {
        return $this->dataset;
    }

    public function setDataset(?Dataset $dataset): void
    {
        $this->dataset = $dataset;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(?string $originalFilename): void
    {
        $this->originalFilename = $originalFilename;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getDataSource(): string
    {
        return $this->dataSource;
    }

    public function setDataSource(string $dataSource): void
    {
        $this->dataSource = $dataSource;
    }

    public function getIndexingTechnique(): string
    {
        return $this->indexingTechnique;
    }

    public function setIndexingTechnique(string $indexingTechnique): void
    {
        $this->indexingTechnique = $indexingTechnique;
    }

    public function getIndexingStatus(): string
    {
        return $this->indexingStatus;
    }

    public function setIndexingStatus(string $indexingStatus): void
    {
        $this->indexingStatus = $indexingStatus;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getCharacterCount(): int
    {
        return $this->characterCount;
    }

    public function setCharacterCount(int $characterCount): void
    {
        $this->characterCount = $characterCount;
    }

    public function getChunkCount(): int
    {
        return $this->chunkCount;
    }

    public function setChunkCount(int $chunkCount): void
    {
        $this->chunkCount = $chunkCount;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function getFileType(): ?string
    {
        return $this->fileType;
    }

    public function setFileType(?string $fileType): void
    {
        $this->fileType = $fileType;
    }

    public function getFileUrl(): ?string
    {
        return $this->fileUrl;
    }

    public function setFileUrl(?string $fileUrl): void
    {
        $this->fileUrl = $fileUrl;
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

    public function getIndexingStartedAt(): ?\DateTimeImmutable
    {
        return $this->indexingStartedAt;
    }

    public function setIndexingStartedAt(?\DateTimeImmutable $indexingStartedAt): void
    {
        $this->indexingStartedAt = $indexingStartedAt;
    }

    public function getIndexingCompletedAt(): ?\DateTimeImmutable
    {
        return $this->indexingCompletedAt;
    }

    public function setIndexingCompletedAt(?\DateTimeImmutable $indexingCompletedAt): void
    {
        $this->indexingCompletedAt = $indexingCompletedAt;
    }

    /** @return Collection<int, DocumentChunk> */
    public function getChunks(): Collection
    {
        return $this->chunks;
    }

    public function addChunk(DocumentChunk $chunk): void
    {
        if (!$this->chunks->contains($chunk)) {
            $this->chunks->add($chunk);
            $chunk->setDocument($this);
        }
    }

    public function removeChunk(DocumentChunk $chunk): void
    {
        if ($this->chunks->removeElement($chunk)) {
            if ($chunk->getDocument() === $this) {
                $chunk->setDocument(null);
            }
        }
    }

    public function isIndexingCompleted(): bool
    {
        return 'completed' === $this->indexingStatus;
    }

    public function hasError(): bool
    {
        return 'error' === $this->indexingStatus;
    }

    /**
     * 获取处理状态
     */
    public function getProcessingStatus(): ?string
    {
        return $this->processingStatus;
    }

    /**
     * 设置处理状态
     */
    public function setProcessingStatus(?string $processingStatus): void
    {
        $this->processingStatus = $processingStatus;
    }

    /**
     * 获取字数统计
     */
    public function getWordCount(): int
    {
        return $this->wordCount;
    }

    /**
     * 设置字数统计
     */
    public function setWordCount(int $wordCount): void
    {
        $this->wordCount = $wordCount;
    }

    /**
     * 获取令牌数量
     */
    public function getTokens(): int
    {
        return $this->tokens;
    }

    /**
     * 设置令牌数量
     */
    public function setTokens(int $tokens): void
    {
        $this->tokens = $tokens;
    }

    /**
     * 获取用户ID
     */
    public function getUserId(): ?string
    {
        return $this->userId;
    }

    /**
     * 设置用户ID
     */
    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * 设置更新时间（别名方法，用于兼容）
     */
    public function setUpdatedAt(\DateTimeInterface $updatedAt): void
    {
        if ($updatedAt instanceof \DateTimeImmutable) {
            $this->setUpdateTime($updatedAt);
        } else {
            $this->setUpdateTime(\DateTimeImmutable::createFromInterface($updatedAt));
        }
    }

    /**
     * 获取更新时间（别名方法，用于兼容）
     */
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->getUpdateTime();
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->name, $this->indexingStatus);
    }
}
