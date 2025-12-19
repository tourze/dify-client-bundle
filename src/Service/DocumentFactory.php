<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Entity\Document;

/**
 * 文档工厂 - 专门处理文档实体的创建
 */
final readonly class DocumentFactory
{
    public function __construct(private ClockInterface $clock)
    {
    }

    /**
     * 从文件创建文档
     */
    public function createFromFile(
        Dataset $dataset,
        UploadedFile $file,
        ?string $name,
        string $indexingTechnique,
        string $userId,
    ): Document {
        $document = $this->createBaseDocument($dataset, $name ?? $file->getClientOriginalName(), $indexingTechnique, $userId);

        $document->setOriginalFilename($file->getClientOriginalName());
        $document->setMimeType($file->getMimeType());
        $document->setFileSize($file->getSize());

        return $document;
    }

    /**
     * 从文本创建文档
     */
    public function createFromText(
        Dataset $dataset,
        string $text,
        string $name,
        string $indexingTechnique,
        string $userId,
    ): Document {
        $document = $this->createBaseDocument($dataset, $name, $indexingTechnique, $userId);
        $document->setContent($text);

        return $document;
    }

    private function createBaseDocument(
        Dataset $dataset,
        string $name,
        string $indexingTechnique,
        string $userId,
    ): Document {
        $document = new Document();
        $document->setDataset($dataset);
        $document->setName($name);
        $document->setIndexingTechnique($indexingTechnique);
        $document->setUserId($userId);
        $document->setProcessingStatus('pending');
        $document->setCreateTime($this->clock->now());

        return $document;
    }
}
