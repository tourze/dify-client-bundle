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
 * Dify 建议问题实体
 *
 * 存储AI生成的下一轮建议问题
 * 对应 API: GET /messages/{message_id}/suggested-questions
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_suggested_question', options: ['comment' => 'Dify 建议问题表'])]
class SuggestedQuestion implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[ORM\ManyToOne(targetEntity: Message::class)]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '关联的消息，建议问题基于此消息生成'])]
    private Message $message;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '所属的会话'])]
    private Conversation $conversation;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '建议的问题内容'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 65535)]
    private string $question;

    #[IndexColumn]
    #[ORM\Column(name: 'sortOrder', type: Types::INTEGER, options: ['default' => 0, 'comment' => '问题的排序顺序，数字越小越靠前'])]
    #[Assert\PositiveOrZero]
    private int $sortOrder = 0;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '问题的相关性评分（0.0-1.0）'])]
    #[Assert\Range(min: 0.0, max: 1.0)]
    private ?float $relevanceScore = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '问题的分类标签'])]
    #[Assert\Length(max: 100)]
    private ?string $category = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '问题被点击的次数'])]
    #[Assert\PositiveOrZero]
    private int $clickCount = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否显示此建议问题'])]
    #[Assert\Type(type: 'bool')]
    private bool $visible = true;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '建议问题的额外元数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '问题的答案或相关内容'])]
    #[Assert\Length(max: 65535)]
    private ?string $answer = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '创建该建议问题的用户ID'])]
    #[Assert\Length(max: 255)]
    private ?string $userId = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否启用该建议问题'])]
    #[Assert\Type(type: 'bool')]
    private bool $enabled = true;

    /** @var array<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '问题标签'])]
    #[Assert\Type(type: 'array')]
    private ?array $tags = null;

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function setMessage(Message $message): void
    {
        $this->message = $message;
    }

    public function getConversation(): Conversation
    {
        return $this->conversation;
    }

    public function setConversation(Conversation $conversation): void
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

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getRelevanceScore(): ?float
    {
        return $this->relevanceScore;
    }

    public function setRelevanceScore(?float $relevanceScore): void
    {
        $this->relevanceScore = $relevanceScore;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function getClickCount(): int
    {
        return $this->clickCount;
    }

    public function setClickCount(int $clickCount): void
    {
        $this->clickCount = $clickCount;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): void
    {
        $this->visible = $visible;
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

    /**
     * 设置问题内容（setQuestion 的别名）
     */
    public function setContent(string $content): void
    {
        $this->setQuestion($content);
    }

    /**
     * 获取答案
     */
    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    /**
     * 设置答案
     */
    public function setAnswer(?string $answer): void
    {
        $this->answer = $answer;
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
     * 获取是否启用
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * 设置是否启用
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /** @return ?array<string> */
    public function getTags(): ?array
    {
        return $this->tags;
    }

    /** @param ?array<string> $tags */
    public function setTags(?array $tags): void
    {
        $this->tags = $tags;
    }

    public function __toString(): string
    {
        $preview = mb_substr($this->question, 0, 50);

        return sprintf('建议问题: %s%s', $preview, mb_strlen($this->question) > 50 ? '...' : '');
    }
}
