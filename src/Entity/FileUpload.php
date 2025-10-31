<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DifyClientBundle\Enum\FileTransferMethod;
use Tourze\DifyClientBundle\Enum\FileType;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

/**
 * Dify 文件上传实体
 *
 * 存储上传到 Dify 的文件信息
 * 对应 API: POST /files/upload
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_file_upload', options: ['comment' => 'Dify 文件上传表'])]
class FileUpload implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[IndexColumn]
    #[ORM\Column(name: 'fileId', type: Types::STRING, length: 255, unique: true, nullable: true, options: ['comment' => 'Dify返回的文件ID，用于后续API调用'])]
    #[Assert\Length(max: 255)]
    private ?string $fileId = null;

    #[ORM\Column(type: Types::STRING, enumType: FileType::class, options: ['comment' => '文件类型：document、image、audio、video、custom'])]
    #[Assert\Choice(callback: [FileType::class, 'cases'])]
    private FileType $type;

    #[ORM\Column(type: Types::STRING, enumType: FileTransferMethod::class, options: ['comment' => '传输方式：remote_url、local_file'])]
    #[Assert\Choice(callback: [FileTransferMethod::class, 'cases'])]
    private FileTransferMethod $transferMethod;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '原始文件名'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 2048, nullable: true, options: ['comment' => '文件URL（远程URL方式时使用）'])]
    #[Assert\Length(max: 2048)]
    #[Assert\Url]
    private ?string $url = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '文件大小（字节）'])]
    #[Assert\PositiveOrZero]
    private ?int $size = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '文件MIME类型'])]
    #[Assert\Length(max: 100)]
    private ?string $mimeType = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '本地文件存储路径'])]
    #[Assert\Length(max: 500)]
    private ?string $localPath = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '文件额外的元数据信息'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    #[IndexColumn]
    #[ORM\Column(name: 'uploadedAt', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '上传到Dify的时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $uploadedAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '原始文件名'])]
    #[Assert\Length(max: 255)]
    private ?string $originalName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '存储文件名'])]
    #[Assert\Length(max: 255)]
    private ?string $storedName = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '文件扩展名'])]
    #[Assert\Length(max: 50)]
    private ?string $extension = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户ID'])]
    #[Assert\Length(max: 255)]
    private ?string $userId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '删除时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '错误信息'])]
    #[Assert\Length(max: 500)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '上传状态：pending、processing、completed、failed'])]
    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['pending', 'processing', 'completed', 'failed'])]
    private ?string $uploadStatus = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '处理完成时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $processedAt = null;

    public function getFileId(): ?string
    {
        return $this->fileId;
    }

    public function setFileId(?string $fileId): void
    {
        $this->fileId = $fileId;
    }

    public function getType(): FileType
    {
        return $this->type;
    }

    public function setType(FileType $type): void
    {
        $this->type = $type;
    }

    public function getTransferMethod(): FileTransferMethod
    {
        return $this->transferMethod;
    }

    public function setTransferMethod(FileTransferMethod $transferMethod): void
    {
        $this->transferMethod = $transferMethod;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): void
    {
        $this->size = $size;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    public function getLocalPath(): ?string
    {
        return $this->localPath;
    }

    public function setLocalPath(?string $localPath): void
    {
        $this->localPath = $localPath;
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

    public function getUploadedAt(): ?\DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(?\DateTimeImmutable $uploadedAt): void
    {
        $this->uploadedAt = $uploadedAt;
    }

    /**
     * 获取原始文件名
     */
    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    /**
     * 设置原始文件名
     */
    public function setOriginalName(?string $originalName): void
    {
        $this->originalName = $originalName;
    }

    /**
     * 获取存储文件名
     */
    public function getStoredName(): ?string
    {
        return $this->storedName;
    }

    /**
     * 设置存储文件名
     */
    public function setStoredName(?string $storedName): void
    {
        $this->storedName = $storedName;
    }

    /**
     * 获取文件扩展名
     */
    public function getExtension(): ?string
    {
        return $this->extension;
    }

    /**
     * 设置文件扩展名
     */
    public function setExtension(?string $extension): void
    {
        $this->extension = $extension;
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
     * 获取删除时间
     */
    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    /**
     * 设置删除时间
     */
    public function setDeletedAt(?\DateTimeImmutable $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * 设置创建时间
     */
    public function setCreateTime(\DateTimeImmutable $createTime): void
    {
        $this->createTime = $createTime;
    }

    /**
     * 获取错误信息
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * 设置错误信息
     */
    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    /**
     * 设置文件大小（setSize 的别名）
     */
    public function setFileSize(?int $size): void
    {
        $this->setSize($size);
    }

    /**
     * 设置文件类型（setType 的别名）
     */
    public function setFileType(FileType $type): void
    {
        $this->setType($type);
    }

    /**
     * 设置原始文件名（setOriginalName 的别名）
     */
    public function setOriginalFilename(?string $originalFilename): void
    {
        $this->setOriginalName($originalFilename);
    }

    /**
     * 设置文件URL（setUrl 的别名）
     */
    public function setFileUrl(?string $fileUrl): void
    {
        $this->setUrl($fileUrl);
    }

    /**
     * 获取上传状态
     */
    public function getUploadStatus(): ?string
    {
        return $this->uploadStatus;
    }

    /**
     * 设置上传状态
     */
    public function setUploadStatus(?string $uploadStatus): void
    {
        $this->uploadStatus = $uploadStatus;
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

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->name, $this->type->getLabel());
    }
}
