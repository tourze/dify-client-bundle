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
 * Dify 数据集标签实体
 *
 * 存储知识库数据集的分类标签
 * 对应 API: GET/POST/PUT/DELETE /datasets/tags
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_dataset_tag', options: ['comment' => 'Dify 数据集标签表'])]
class DatasetTag implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use CreatedByAware;

    #[IndexColumn]
    #[ORM\Column(name: 'tagId', type: Types::STRING, length: 255, unique: true, options: ['comment' => 'Dify返回的标签ID'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $tagId;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true, options: ['comment' => '标签名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '标签描述'])]
    #[Assert\Length(max: 65535)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '标签颜色（十六进制颜色码）'])]
    #[Assert\Length(max: 10)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: '颜色必须是有效的十六进制颜色码')]
    private ?string $color = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '使用此标签的数据集数量'])]
    #[Assert\PositiveOrZero]
    private int $usageCount = 0;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '标签的额外元数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    /** @var Collection<int, Dataset> */
    #[ORM\ManyToMany(targetEntity: Dataset::class, inversedBy: 'tags')]
    #[ORM\JoinTable(name: 'dify_dataset_tag_mapping')]
    private Collection $datasets;

    public function __construct()
    {
        $this->datasets = new ArrayCollection();
    }

    public function getTagId(): string
    {
        return $this->tagId;
    }

    public function setTagId(string $tagId): void
    {
        $this->tagId = $tagId;
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

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): void
    {
        $this->color = $color;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): void
    {
        $this->usageCount = $usageCount;
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

    /** @return Collection<int, Dataset> */
    public function getDatasets(): Collection
    {
        return $this->datasets;
    }

    public function addDataset(Dataset $dataset): self
    {
        if (!$this->datasets->contains($dataset)) {
            $this->datasets->add($dataset);
        }

        return $this;
    }

    public function removeDataset(Dataset $dataset): self
    {
        $this->datasets->removeElement($dataset);

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
        return $this->name;
    }
}
