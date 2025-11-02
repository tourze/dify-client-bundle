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
 * Dify 应用信息实体
 *
 * 存储从 Dify API 获取的应用基本信息
 * 对应 API: GET /info
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_app_info', options: ['comment' => 'Dify 应用信息表'])]
class AppInfo implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[IndexColumn]
    #[ORM\Column(name: 'appId', type: Types::STRING, length: 255, unique: true, options: ['comment' => 'Dify应用ID，用于唯一标识应用'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $appId;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '应用名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '应用模式：chat, agent, workflow, completion'])]
    #[Assert\Length(max: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['chat', 'agent', 'workflow', 'completion'])]
    private string $mode;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '应用描述'])]
    #[Assert\Length(max: 65535)]
    private ?string $description = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '应用图标配置'])]
    #[Assert\Type(type: 'array')]
    private ?array $icon = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '应用图标背景色'])]
    #[Assert\Length(max: 10)]
    private ?string $iconBackground = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否启用站点'])]
    #[Assert\Type(type: 'bool')]
    private bool $enableSite = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否启用API'])]
    #[Assert\Type(type: 'bool')]
    private bool $enableApi = true;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '应用额外的元数据信息'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /** @return ?array<string, mixed> */
    public function getIcon(): ?array
    {
        return $this->icon;
    }

    /** @param ?array<string, mixed> $icon */
    public function setIcon(?array $icon): void
    {
        $this->icon = $icon;
    }

    public function getIconBackground(): ?string
    {
        return $this->iconBackground;
    }

    public function setIconBackground(?string $iconBackground): void
    {
        $this->iconBackground = $iconBackground;
    }

    public function isEnableSite(): bool
    {
        return $this->enableSite;
    }

    public function setEnableSite(bool $enableSite): void
    {
        $this->enableSite = $enableSite;
    }

    public function isEnableApi(): bool
    {
        return $this->enableApi;
    }

    public function setEnableApi(bool $enableApi): void
    {
        $this->enableApi = $enableApi;
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

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->name, $this->mode);
    }
}
