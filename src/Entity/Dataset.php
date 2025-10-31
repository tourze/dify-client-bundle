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
 * Dify 数据集实体
 *
 * 存储知识库数据集信息
 * 对应 API: GET/POST/PUT/DELETE /datasets
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_dataset', options: ['comment' => 'Dify 数据集表'])]
class Dataset implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use CreatedByAware;

    #[IndexColumn]
    #[ORM\Column(name: 'datasetId', type: Types::STRING, length: 255, unique: true, nullable: true, options: ['comment' => 'Dify返回的数据集ID'])]
    #[Assert\Length(max: 255)]
    private ?string $datasetId = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '数据集名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '数据集描述'])]
    #[Assert\Length(max: 65535)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '数据集类型：qa-问答型，text-文本型'])]
    #[Assert\Length(max: 100)]
    #[Assert\Choice(choices: ['qa', 'text'])]
    private string $dataSourceType;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '索引结构：economy-经济型，high_quality-高质量'])]
    #[Assert\Length(max: 100)]
    #[Assert\Choice(choices: ['economy', 'high_quality'])]
    private string $indexingTechnique;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '文档数量'])]
    #[Assert\PositiveOrZero]
    private int $documentCount = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '单词总数'])]
    #[Assert\PositiveOrZero]
    private int $wordCount = 0;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '嵌入模型名称'])]
    #[Assert\Length(max: 255)]
    private ?string $embeddingModel = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '嵌入模型提供商'])]
    #[Assert\Length(max: 255)]
    private ?string $embeddingModelProvider = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '数据集的额外元数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    /** @var Collection<int, Document> */
    #[ORM\OneToMany(mappedBy: 'dataset', targetEntity: Document::class, cascade: ['persist'])]
    private Collection $documents;

    /** @var Collection<int, DatasetTag> */
    #[ORM\ManyToMany(targetEntity: DatasetTag::class, mappedBy: 'datasets')]
    private Collection $tags;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getDatasetId(): ?string
    {
        return $this->datasetId;
    }

    public function setDatasetId(?string $datasetId): void
    {
        $this->datasetId = $datasetId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDataSourceType(): string
    {
        return $this->dataSourceType;
    }

    public function setDataSourceType(string $dataSourceType): void
    {
        $this->dataSourceType = $dataSourceType;
    }

    public function getIndexingTechnique(): string
    {
        return $this->indexingTechnique;
    }

    public function setIndexingTechnique(string $indexingTechnique): void
    {
        $this->indexingTechnique = $indexingTechnique;
    }

    public function getDocumentCount(): int
    {
        return $this->documentCount;
    }

    public function setDocumentCount(int $documentCount): void
    {
        $this->documentCount = $documentCount;
    }

    public function getWordCount(): int
    {
        return $this->wordCount;
    }

    public function setWordCount(int $wordCount): void
    {
        $this->wordCount = $wordCount;
    }

    public function getEmbeddingModel(): ?string
    {
        return $this->embeddingModel;
    }

    public function setEmbeddingModel(?string $embeddingModel): void
    {
        $this->embeddingModel = $embeddingModel;
    }

    public function getEmbeddingModelProvider(): ?string
    {
        return $this->embeddingModelProvider;
    }

    public function setEmbeddingModelProvider(?string $embeddingModelProvider): void
    {
        $this->embeddingModelProvider = $embeddingModelProvider;
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

    /** @return Collection<int, Document> */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): void
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setDataset($this);
        }
    }

    public function removeDocument(Document $document): void
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getDataset() === $this) {
                $document->setDataset(null);
            }
        }
    }

    /** @return Collection<int, DatasetTag> */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(DatasetTag $tag): void
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->addDataset($this);
        }
    }

    public function removeTag(DatasetTag $tag): void
    {
        if ($this->tags->removeElement($tag)) {
            $tag->removeDataset($this);
        }
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
        return sprintf('%s (%d文档)', $this->name, $this->documentCount);
    }
}
