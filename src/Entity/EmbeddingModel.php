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
 * Dify 嵌入模型实体
 *
 * 存储可用的嵌入模型信息
 * 对应 API: GET /datasets/embedding-models
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_embedding_model', options: ['comment' => 'Dify 嵌入模型表'])]
class EmbeddingModel implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[IndexColumn]
    #[ORM\Column(name: 'modelName', type: Types::STRING, length: 255, unique: true, options: ['comment' => '嵌入模型名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $modelName;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '模型提供商名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $provider;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '模型显示名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $displayName;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '模型描述'])]
    #[Assert\Length(max: 65535)]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '向量维度数'])]
    #[Assert\PositiveOrZero]
    private ?int $dimensions = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '最大token数'])]
    #[Assert\PositiveOrZero]
    private ?int $maxTokens = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '模型类型：text、multimodal等'])]
    #[Assert\Length(max: 50)]
    private ?string $modelType = null;

    /** @var array<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '支持的语言列表'])]
    #[Assert\Type(type: 'array')]
    private ?array $supportedLanguages = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否可用'])]
    #[Assert\Type(type: 'bool')]
    private bool $available = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否为默认模型'])]
    #[Assert\Type(type: 'bool')]
    private bool $isDefault = false;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '每1000 tokens的价格'])]
    #[Assert\PositiveOrZero]
    private ?float $pricePerThousandTokens = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '价格货币单位'])]
    #[Assert\Length(max: 10)]
    private ?string $currency = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '模型的额外元数据和配置'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    #[IndexColumn]
    #[ORM\Column(name: 'lastSyncAt', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后同步时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $lastSyncAt = null;

    public function getModelName(): string
    {
        return $this->modelName;
    }

    public function setModelName(string $modelName): void
    {
        $this->modelName = $modelName;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDimensions(): ?int
    {
        return $this->dimensions;
    }

    public function setDimensions(?int $dimensions): void
    {
        $this->dimensions = $dimensions;
    }

    public function getMaxTokens(): ?int
    {
        return $this->maxTokens;
    }

    public function setMaxTokens(?int $maxTokens): void
    {
        $this->maxTokens = $maxTokens;
    }

    public function getModelType(): ?string
    {
        return $this->modelType;
    }

    public function setModelType(?string $modelType): void
    {
        $this->modelType = $modelType;
    }

    /** @return ?array<string> */
    public function getSupportedLanguages(): ?array
    {
        return $this->supportedLanguages;
    }

    /** @param ?array<string> $supportedLanguages */
    public function setSupportedLanguages(?array $supportedLanguages): void
    {
        $this->supportedLanguages = $supportedLanguages;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): void
    {
        $this->available = $available;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): void
    {
        $this->isDefault = $isDefault;
    }

    public function getPricePerThousandTokens(): ?float
    {
        return $this->pricePerThousandTokens;
    }

    public function setPricePerThousandTokens(?float $pricePerThousandTokens): void
    {
        $this->pricePerThousandTokens = $pricePerThousandTokens;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
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

    public function getLastSyncAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncAt;
    }

    public function setLastSyncAt(?\DateTimeImmutable $lastSyncAt): void
    {
        $this->lastSyncAt = $lastSyncAt;
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->displayName, $this->provider);
    }
}
