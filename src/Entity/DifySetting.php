<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;

/**
 * Dify AI 配置实体
 *
 * 存储 Dify AI 应用的连接配置和参数设置
 * 同一时间只能有一个配置处于激活状态
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_setting', options: ['comment' => 'Dify AI 配置表'])]
class DifySetting implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use CreatedByAware;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, unique: true, options: ['comment' => '配置名称，用于标识不同的Dify应用配置'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'Dify AI应用的API密钥，用于身份验证'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $apiKey;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'Dify API的基础URL地址'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    #[Assert\Url]
    private string $baseUrl;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 5, 'comment' => '消息聚合的批量阈值，达到此数量时立即发送'])]
    #[Assert\PositiveOrZero]
    #[Assert\Range(min: 1, max: 1000)]
    private int $batchThreshold = 5;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 30, 'comment' => 'API请求超时时间（秒）'])]
    #[Assert\PositiveOrZero]
    #[Assert\Range(min: 1, max: 300)]
    private int $timeout = 30;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 3, 'comment' => '失败消息的重试次数'])]
    #[Assert\PositiveOrZero]
    #[Assert\Range(min: 0, max: 10)]
    private int $retryAttempts = 3;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 3, 'comment' => '最大重试次数'])]
    #[Assert\PositiveOrZero]
    #[Assert\Range(min: 0, max: 10)]
    private int $maxRetries = 3;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 10, 'comment' => '批量处理大小'])]
    #[Assert\PositiveOrZero]
    #[Assert\Range(min: 1, max: 1000)]
    private int $batchSize = 10;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 300, 'comment' => '批量处理超时时间（秒）'])]
    #[Assert\PositiveOrZero]
    #[Assert\Range(min: 1, max: 3600)]
    private int $batchTimeout = 300;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '配置元数据'])]
    #[Assert\Type(type: 'array', message: '元数据必须是数组')]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Dify聊天窗口的iframe嵌入代码'])]
    #[Assert\Length(max: 10000)]
    private ?string $iframeEmbedCode = null;

    #[IndexColumn]
    #[ORM\Column(name: 'isActive', type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否为当前激活的配置，同一时间只能有一个激活配置'])]
    #[Assert\Type(type: 'bool')]
    private bool $isActive = false;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function getBatchThreshold(): int
    {
        return $this->batchThreshold;
    }

    public function setBatchThreshold(int $batchThreshold): void
    {
        $this->batchThreshold = $batchThreshold;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function getRetryAttempts(): int
    {
        return $this->retryAttempts;
    }

    public function setRetryAttempts(int $retryAttempts): void
    {
        $this->retryAttempts = $retryAttempts;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function setMaxRetries(int $maxRetries): void
    {
        $this->maxRetries = $maxRetries;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize;
    }

    public function getBatchTimeout(): int
    {
        return $this->batchTimeout;
    }

    public function setBatchTimeout(int $batchTimeout): void
    {
        $this->batchTimeout = $batchTimeout;
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

    public function getIframeEmbedCode(): ?string
    {
        return $this->iframeEmbedCode;
    }

    public function setIframeEmbedCode(?string $iframeEmbedCode): void
    {
        $this->iframeEmbedCode = $iframeEmbedCode;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
