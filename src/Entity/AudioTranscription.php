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
 * Dify 语音转录实体
 *
 * 存储语音转文字和文字转语音的记录
 * 对应 API: POST /text-to-audio, POST /audio-to-text
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_audio_transcription', options: ['comment' => 'Dify 语音转录表'])]
class AudioTranscription implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[IndexColumn]
    #[ORM\Column(name: 'taskId', type: Types::STRING, length: 255, unique: true, options: ['comment' => '转录任务ID'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $taskId;

    #[ORM\ManyToOne(targetEntity: Message::class)]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '关联的消息'])]
    private ?Message $message = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '所属的会话'])]
    private ?Conversation $conversation = null;

    #[IndexColumn]
    #[ORM\Column(name: 'type', type: Types::STRING, length: 20, options: ['comment' => '转录类型：text_to_audio-文字转语音，audio_to_text-语音转文字'])]
    #[Assert\Length(max: 20)]
    #[Assert\Choice(choices: ['text_to_audio', 'audio_to_text'])]
    private string $type;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '文本内容（输入或输出的文字）'])]
    #[Assert\Length(max: 65535)]
    private ?string $text = null;

    #[ORM\Column(type: Types::STRING, length: 2048, nullable: true, options: ['comment' => '音频文件URL或路径'])]
    #[Assert\Length(max: 2048)]
    private ?string $audioUrl = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '音频格式：mp3、wav、m4a等'])]
    #[Assert\Length(max: 50)]
    private ?string $audioFormat = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '音频时长（秒）'])]
    #[Assert\PositiveOrZero]
    private ?int $duration = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '音频文件大小（字节）'])]
    #[Assert\PositiveOrZero]
    private ?int $fileSize = null;

    #[IndexColumn]
    #[ORM\Column(name: 'status', type: Types::STRING, length: 20, options: ['default' => 'pending', 'comment' => '转录状态：pending-待处理，processing-处理中，completed-已完成，failed-失败'])]
    #[Assert\Length(max: 20)]
    #[Assert\Choice(choices: ['pending', 'processing', 'completed', 'failed'])]
    private string $status = 'pending';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户ID'])]
    #[Assert\Length(max: 255)]
    private ?string $userId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '错误信息（处理失败时）'])]
    #[Assert\Length(max: 65535)]
    private ?string $errorMessage = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '转录的额外元数据，如语言、语速等参数'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '开始处理时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $startedAt = null;

    #[IndexColumn]
    #[ORM\Column(name: 'completedAt', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '完成处理时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '原始文件名'])]
    #[Assert\Length(max: 255)]
    private ?string $originalFilename = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => 'MIME类型'])]
    #[Assert\Length(max: 100)]
    private ?string $mimeType = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '语言代码'])]
    #[Assert\Length(max: 10)]
    private ?string $language = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '处理完成时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '转录置信度'])]
    #[Assert\Range(min: 0, max: 1)]
    private ?float $confidence = null;

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function setTaskId(string $taskId): void
    {
        $this->taskId = $taskId;
    }

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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    public function getAudioUrl(): ?string
    {
        return $this->audioUrl;
    }

    public function setAudioUrl(?string $audioUrl): void
    {
        $this->audioUrl = $audioUrl;
    }

    public function getAudioFormat(): ?string
    {
        return $this->audioFormat;
    }

    public function setAudioFormat(?string $audioFormat): void
    {
        $this->audioFormat = $audioFormat;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): void
    {
        $this->duration = $duration;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
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

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function isTextToAudio(): bool
    {
        return 'text_to_audio' === $this->type;
    }

    public function isAudioToText(): bool
    {
        return 'audio_to_text' === $this->type;
    }

    public function isCompleted(): bool
    {
        return 'completed' === $this->status;
    }

    public function isFailed(): bool
    {
        return 'failed' === $this->status;
    }

    /**
     * 获取原始文件名
     */
    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    /**
     * 设置原始文件名
     */
    public function setOriginalFilename(?string $originalFilename): void
    {
        $this->originalFilename = $originalFilename;
    }

    /**
     * 获取MIME类型
     */
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * 设置MIME类型
     */
    public function setMimeType(?string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    /**
     * 获取语言
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * 设置语言
     */
    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    /**
     * 获取处理完成时间
     */
    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    /**
     * 设置处理完成时间
     */
    public function setProcessedAt(?\DateTimeImmutable $processedAt): void
    {
        $this->processedAt = $processedAt;
    }

    /**
     * 获取置信度
     */
    public function getConfidence(): ?float
    {
        return $this->confidence;
    }

    /**
     * 设置置信度
     */
    public function setConfidence(?float $confidence): void
    {
        $this->confidence = $confidence;
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
        $typeLabel = 'text_to_audio' === $this->type ? '文字转语音' : '语音转文字';

        return sprintf('%s任务 (%s)', $typeLabel, $this->status);
    }
}
