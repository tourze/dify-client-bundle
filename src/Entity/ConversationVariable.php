<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

/**
 * Dify 会话变量实体
 *
 * 存储会话中的变量信息
 * 对应 API: GET /conversations/{conversation_id}/variables
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_conversation_variable', options: ['comment' => 'Dify 会话变量表'])]
class ConversationVariable implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '所属的会话'])]
    private Conversation $conversation;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '变量名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '变量值，可以是字符串或JSON格式'])]
    #[Assert\Length(max: 65535)]
    private ?string $value = null;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['default' => 'string', 'comment' => '变量类型：string、number、boolean、object、array'])]
    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['string', 'number', 'boolean', 'object', 'array'])]
    private string $type = 'string';

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '变量描述或用途说明'])]
    #[Assert\Length(max: 65535)]
    private ?string $description = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否为必需变量'])]
    #[Assert\Type(type: 'bool')]
    private bool $required = false;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '变量的额外配置信息'])]
    #[Assert\Type(type: 'array')]
    private ?array $config = null;

    public function getConversation(): Conversation
    {
        return $this->conversation;
    }

    public function setConversation(Conversation $conversation): void
    {
        $this->conversation = $conversation;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }

    /** @return ?array<string, mixed> */
    public function getConfig(): ?array
    {
        return $this->config;
    }

    /** @param ?array<string, mixed> $config */
    public function setConfig(?array $config): void
    {
        $this->config = $config;
    }

    /**
     * 获取解析后的变量值
     * 根据类型自动解析JSON或返回原始值
     *
     * @return mixed
     */
    public function getParsedValue(): mixed
    {
        if (null === $this->value) {
            return null;
        }

        return match ($this->type) {
            'number' => is_numeric($this->value) ? (float) $this->value : $this->value,
            'boolean' => in_array(strtolower($this->value), ['true', '1', 'yes'], true),
            'object', 'array' => json_decode($this->value, true) ?? $this->value,
            default => $this->value,
        };
    }

    /**
     * 设置变量值并自动确定类型
     *
     * @param mixed $value
     */
    public function setValueWithType(mixed $value): void
    {
        if (null === $value) {
            $this->setNullValue();

            return;
        }

        if (is_bool($value)) {
            $this->setBooleanValue($value);

            return;
        }

        if (is_numeric($value)) {
            $this->setNumericValue($value);

            return;
        }

        if (is_array($value)) {
            $this->setArrayValue($value);

            return;
        }

        if (is_object($value)) {
            $this->setObjectValue($value);

            return;
        }

        if (is_string($value)) {
            $this->setStringValue($value);

            return;
        }

        // 对于其他标量类型
        $this->setStringValue(serialize($value));
    }

    private function setNullValue(): void
    {
        $this->value = null;
        $this->type = 'string';
    }

    private function setBooleanValue(bool $value): void
    {
        $this->value = $value ? 'true' : 'false';
        $this->type = 'boolean';
    }

    /** @param int|float|numeric-string $value */
    private function setNumericValue(int|float|string $value): void
    {
        $this->value = (string) $value;
        $this->type = 'number';
    }

    /** @param array<mixed> $value */
    private function setArrayValue(array $value): void
    {
        $this->value = json_encode($value, JSON_THROW_ON_ERROR);
        $this->type = 'array';
    }

    private function setObjectValue(object $value): void
    {
        $this->value = json_encode($value, JSON_THROW_ON_ERROR);
        $this->type = 'object';
    }

    private function setStringValue(string $value): void
    {
        $this->value = $value;
        $this->type = 'string';
    }

    public function __toString(): string
    {
        return sprintf('%s: %s (%s)', $this->name, $this->value ?? 'null', $this->type);
    }
}
