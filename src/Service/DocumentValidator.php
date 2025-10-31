<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;

/**
 * 文档验证器 - 专门处理文档相关的验证逻辑
 */
readonly class DocumentValidator
{
    private const MAX_FILE_SIZE = 100 * 1024 * 1024; // 100MB
    private const MAX_TEXT_LENGTH = 1000000; // 1M字符

    private const ALLOWED_MIME_TYPES = [
        'text/plain',
        'text/markdown',
        'text/html',
        'text/csv',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    /**
     * 验证上传文件
     */
    public function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new DifyRuntimeException('Invalid document file upload');
        }

        $this->validateFileSize($file);
        $this->validateMimeType($file);
    }

    /**
     * 验证文本内容
     */
    public function validateText(string $text): void
    {
        if ('' === trim($text)) {
            throw new DifyRuntimeException('Document text cannot be empty');
        }

        if (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            throw new DifyRuntimeException('Document text exceeds 1M character limit');
        }
    }

    /**
     * 验证数据集
     */
    public function validateDataset(Dataset $dataset): void
    {
        if (null === $dataset->getDatasetId()) {
            throw new DifyRuntimeException('Dataset must have a valid Dify dataset ID');
        }
    }

    private function validateFileSize(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new DifyRuntimeException('Document file size exceeds 100MB limit');
        }
    }

    private function validateMimeType(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new DifyRuntimeException('Unsupported document format: ' . $mimeType);
        }
    }
}
