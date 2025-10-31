<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Entity\Document;
use Tourze\DifyClientBundle\Entity\DocumentChunk;
use Tourze\DifyClientBundle\Event\DocumentOperationFailedEvent;
use Tourze\DifyClientBundle\Event\DocumentOperationSuccessEvent;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;
use Tourze\DifyClientBundle\Repository\DocumentChunkRepository;
use Tourze\DifyClientBundle\Repository\DocumentRepository;

/**
 * 文档管理服务
 *
 * 核心业务逻辑，HTTP通信由DocumentHttpClient处理
 */
readonly class DocumentService
{
    private DocumentHttpClient $httpClient;

    private DocumentValidator $validator;

    private DocumentFactory $factory;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private DifySettingRepository $settingRepository,
        private DocumentRepository $documentRepository,
        private DocumentChunkRepository $chunkRepository,
        private ClockInterface $clock,
        ?DocumentHttpClient $httpClient = null,
        ?DocumentValidator $validator = null,
        ?DocumentFactory $factory = null,
    ) {
        $this->httpClient = $httpClient ?? new DocumentHttpClient(new CurlHttpClient(), $this->settingRepository);
        $this->validator = $validator ?? new DocumentValidator();
        $this->factory = $factory ?? new DocumentFactory($this->clock);
    }

    // ==================== 核心业务方法 ====================

    /**
     * 通过文件创建文档
     */
    public function createDocumentFromFile(
        Dataset $dataset,
        UploadedFile $file,
        ?string $name = null,
        string $indexingTechnique = 'high_quality',
        string $userId = 'system',
    ): Document {
        $this->validator->validateFile($file);
        $this->validator->validateDataset($dataset);

        $document = $this->factory->createFromFile($dataset, $file, $name, $indexingTechnique, $userId);
        $this->documentRepository->save($document);

        $datasetId = $dataset->getDatasetId();
        $documentName = $document->getName();

        if (null === $datasetId) {
            throw new DifyRuntimeException('Invalid dataset configuration - missing datasetId');
        }

        return $this->executeDocumentCreation(
            $document,
            fn () => $this->httpClient->createDocumentByFile(
                $datasetId,
                $file,
                $documentName,
                $indexingTechnique
            ),
            'create_from_file',
            ['file_name' => $file->getClientOriginalName(), 'file_size' => $file->getSize()]
        );
    }

    /**
     * 通过文本创建文档
     */
    public function createDocumentFromText(
        Dataset $dataset,
        string $text,
        string $name,
        string $indexingTechnique = 'high_quality',
        string $userId = 'system',
    ): Document {
        $this->validator->validateText($text);
        $this->validator->validateDataset($dataset);

        $document = $this->factory->createFromText($dataset, $text, $name, $indexingTechnique, $userId);
        $this->documentRepository->save($document);

        $datasetId = $dataset->getDatasetId();

        if (null === $datasetId) {
            throw new DifyRuntimeException('Invalid dataset configuration');
        }

        return $this->executeDocumentCreation(
            $document,
            fn () => $this->httpClient->createDocumentByText(
                $datasetId,
                $name,
                $text,
                $indexingTechnique
            ),
            'create_from_text',
            ['text_length' => mb_strlen($text)]
        );
    }

    /**
     * 更新文档
     */
    public function updateDocument(Document $document, ?string $name = null, ?string $content = null): Document
    {
        $backup = $this->createDocumentBackup($document);

        try {
            $this->applyDocumentUpdates($document, $name, $content);
            $this->syncDocumentToDify($document);
            $this->completeDocumentUpdate($document, $name, $content);

            return $document;
        } catch (\Exception $e) {
            $this->restoreFromBackup($document, $backup);
            $this->dispatchFailedEvent($document, 'update', $e, ['original_name' => $backup['name']]);
            throw new DifyRuntimeException(sprintf('Failed to update document: %s', $e->getMessage()));
        }
    }

    /**
     * 删除文档
     */
    public function deleteDocument(Document $document): void
    {
        $documentId = $document->getDocumentId();

        // 尝试删除Dify端的文档
        if (null !== $documentId) {
            try {
                $dataset = $document->getDataset();
                if (null === $dataset || null === $dataset->getDatasetId()) {
                    error_log('Document does not have valid dataset for deletion');

                    return;
                }
                $this->httpClient->deleteDocument($dataset->getDatasetId(), $documentId);
            } catch (\Exception $e) {
                error_log(sprintf('Failed to delete Dify document %s: %s', $documentId, $e->getMessage()));
            }
        }

        $this->documentRepository->remove($document);

        $this->eventDispatcher->dispatch(new DocumentOperationSuccessEvent(
            $document,
            'delete',
            ['document_id' => $documentId]
        ));
    }

    /**
     * 重新索引文档
     */
    public function reindexDocument(Document $document): Document
    {
        $documentId = $document->getDocumentId();
        if (null === $documentId) {
            throw new DifyRuntimeException('Document does not have Dify document ID');
        }

        $document->setProcessingStatus('processing');
        $document->setUpdatedAt($this->clock->now());
        $this->documentRepository->save($document);

        return $document;
    }

    // ==================== 查询方法 ====================

    /**
     * 获取数据集的文档列表
     * @return array<Document>
     */
    public function getDatasetDocuments(Dataset $dataset, int $limit = 50, int $offset = 0): array
    {
        return $this->documentRepository->findBy(
            ['dataset' => $dataset],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * 搜索文档
     * @return array<Document>
     */
    public function searchDocuments(
        ?string $query = null,
        ?Dataset $dataset = null,
        ?string $status = null,
        int $limit = 50,
        int $offset = 0,
    ): array {
        return $this->documentRepository->search($query, $dataset, $status, $limit, $offset);
    }

    /**
     * 获取文档统计信息
     * @return array<string, mixed>
     */
    public function getDocumentStats(?Dataset $dataset = null): array
    {
        return $this->documentRepository->getStatistics($dataset, $this->clock);
    }

    /**
     * 获取文档的分块信息
     * @return array<DocumentChunk>
     */
    public function getDocumentChunks(Document $document): array
    {
        return $this->chunkRepository->findBy(
            ['document' => $document],
            ['position' => 'ASC']
        );
    }

    /**
     * 批量导入文档
     * @param array<UploadedFile> $files
     * @return array{success: int, failed: int, results: array<Document>, errors: array<string>}
     */
    public function batchImportDocuments(
        Dataset $dataset,
        array $files,
        string $indexingTechnique = 'high_quality',
        string $userId = 'system',
    ): array {
        $results = [
            'success' => 0,
            'failed' => 0,
            'results' => [],
            'errors' => [],
        ];

        foreach ($files as $index => $file) {
            try {
                $document = $this->createDocumentFromFile($dataset, $file, null, $indexingTechnique, $userId);
                $results['results'][] = $document;
                ++$results['success'];
            } catch (\Exception $e) {
                ++$results['failed'];
                $results['errors'][] = sprintf('File %d (%s): %s', $index + 1, $file->getClientOriginalName(), $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * 清理处理失败的文档
     */
    public function cleanupFailedDocuments(\DateInterval $expiry): int
    {
        $expiredDate = $this->clock->now()->sub($expiry);
        $failedDocuments = $this->documentRepository->findFailedDocuments($expiredDate);

        $cleanupCount = 0;
        foreach ($failedDocuments as $document) {
            $this->deleteDocument($document);
            ++$cleanupCount;
        }

        return $cleanupCount;
    }

    // ==================== HTTP API 代理方法 ====================

    /**
     * 获取知识库的文档列表
     * @return array<string, mixed>
     */
    public function getDocuments(string $datasetId, int $page = 1, int $limit = 20, ?string $keyword = null): array
    {
        return $this->httpClient->getDocuments($datasetId, $page, $limit, $keyword);
    }

    /**
     * 从文本创建文档
     * @param array<string, mixed> $processRule
     * @return array<string, mixed>
     */
    public function createDocumentByText(
        string $datasetId,
        string $name,
        string $text,
        string $indexingTechnique = 'high_quality',
        array $processRule = [],
    ): array {
        return $this->httpClient->createDocumentByText($datasetId, $name, $text, $indexingTechnique, $processRule);
    }

    /**
     * 从文件创建文档
     * @param array<string, mixed> $processRule
     * @return array<string, mixed>
     */
    public function createDocumentByFile(
        string $datasetId,
        UploadedFile $file,
        string $name = '',
        string $indexingTechnique = 'high_quality',
        array $processRule = [],
    ): array {
        $this->validator->validateFile($file);

        return $this->httpClient->createDocumentByFile(
            $datasetId,
            $file,
            '' !== $name ? $name : $file->getClientOriginalName(),
            $indexingTechnique,
            $processRule
        );
    }

    /**
     * 获取文档详情
     * @return array<string, mixed>
     */
    public function getDocument(string $datasetId, string $documentId): array
    {
        return $this->httpClient->getDocument($datasetId, $documentId);
    }

    /**
     * 获取文档处理状态
     * @return array<string, mixed>|null
     */
    public function getDocumentStatus(Document $document): ?array
    {
        $documentId = $document->getDocumentId();
        if (null === $documentId) {
            throw new DifyRuntimeException('Document does not have Dify document ID');
        }

        $dataset = $document->getDataset();
        if (null === $dataset || null === $dataset->getDatasetId()) {
            throw new DifyRuntimeException('Document does not have valid dataset');
        }

        return $this->httpClient->getDocument($dataset->getDatasetId(), $documentId);
    }

    /**
     * 删除文档（通过ID）
     * @return array<string, mixed>
     */
    public function deleteDocumentById(string $datasetId, string $documentId): array
    {
        return $this->httpClient->deleteDocument($datasetId, $documentId);
    }

    /**
     * 通过文本更新文档
     * @param array<string, mixed>|null $processRule
     * @return array<string, mixed>
     */
    public function updateDocumentByText(
        string $datasetId,
        string $documentId,
        ?string $name = null,
        string $text = '',
        ?array $processRule = null,
    ): array {
        return $this->httpClient->updateDocumentByText($datasetId, $documentId, $name, $text, $processRule);
    }

    /**
     * 通过文件更新文档
     * @param array<string, mixed>|null $processRule
     * @return array<string, mixed>
     */
    public function updateDocumentByFile(
        string $datasetId,
        string $documentId,
        UploadedFile $file,
        ?string $name = null,
        ?array $processRule = null,
    ): array {
        $this->validator->validateFile($file);

        return $this->httpClient->updateDocumentByFile($datasetId, $documentId, $file, $name, $processRule);
    }

    /**
     * 更新文档状态
     * @return array<string, mixed>
     */
    public function updateDocumentStatus(string $datasetId, string $documentId, bool $enabled): array
    {
        return $this->httpClient->updateDocumentStatus($datasetId, $documentId, $enabled);
    }

    /**
     * 获取文档索引状态
     * @return array<string, mixed>
     */
    public function getDocumentIndexingStatus(string $datasetId, string $documentId): array
    {
        return $this->httpClient->getDocumentIndexingStatus($datasetId, $documentId);
    }

    // ==================== 文档块管理方法 ====================

    /**
     * @return array<string, mixed>
     */
    public function getDocumentSegments(string $datasetId, string $documentId, ?string $keyword = null, ?string $status = null): array
    {
        return $this->httpClient->getDocumentSegments($datasetId, $documentId, $keyword, $status);
    }

    /**
     * @param array<string> $keywords
     * @return array<string, mixed>
     */
    public function createDocumentSegment(string $datasetId, string $documentId, string $content, ?string $answer = null, array $keywords = []): array
    {
        return $this->httpClient->createDocumentSegment($datasetId, $documentId, $content, $answer, $keywords);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDocumentSegment(string $datasetId, string $documentId, string $segmentId): array
    {
        return $this->httpClient->getDocumentSegment($datasetId, $documentId, $segmentId);
    }

    /**
     * @param array<string>|null $keywords
     * @return array<string, mixed>
     */
    public function updateDocumentSegment(string $datasetId, string $documentId, string $segmentId, ?string $content = null, ?string $answer = null, ?array $keywords = null, ?bool $enabled = null): array
    {
        return $this->httpClient->updateDocumentSegment($datasetId, $documentId, $segmentId, $content, $answer, $keywords, $enabled);
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteDocumentSegment(string $datasetId, string $documentId, string $segmentId): array
    {
        return $this->httpClient->deleteDocumentSegment($datasetId, $documentId, $segmentId);
    }

    // ==================== 子块管理方法 ====================

    /** @return array<string, mixed> */
    public function createChildChunk(string $datasetId, string $documentId, string $segmentId, string $content): array
    {
        return $this->httpClient->createChildChunk($datasetId, $documentId, $segmentId, $content);
    }

    /** @return array<string, mixed> */
    public function getChildChunk(string $datasetId, string $documentId, string $segmentId, string $chunkId): array
    {
        return $this->httpClient->getChildChunk($datasetId, $documentId, $segmentId, $chunkId);
    }

    /** @return array<string, mixed> */
    public function updateChildChunk(string $datasetId, string $documentId, string $segmentId, string $chunkId, string $content): array
    {
        return $this->httpClient->updateChildChunk($datasetId, $documentId, $segmentId, $chunkId, $content);
    }

    /** @return array<string, mixed> */
    public function deleteChildChunk(string $datasetId, string $documentId, string $segmentId, string $chunkId): array
    {
        return $this->httpClient->deleteChildChunk($datasetId, $documentId, $segmentId, $chunkId);
    }

    // ==================== 私有辅助方法 ====================

    /**
     * 执行文档创建流程
     * @param array<string, mixed> $metadata
     */
    private function executeDocumentCreation(
        Document $document,
        callable $createOperation,
        string $operationType,
        array $metadata,
    ): Document {
        try {
            $response = $createOperation();
            if (is_array($response)) {
                /** @var array<string, mixed> $response */
                $this->updateDocumentFromResponse($document, $response);
            }
            $this->documentRepository->save($document);
            $this->dispatchSuccessEvent($document, $operationType, $metadata);

            return $document;
        } catch (\Exception $e) {
            $this->handleDocumentFailure($document, $e);
            $this->dispatchFailedEvent($document, $operationType, $e, $metadata);
            throw $e;
        }
    }

    /** @param array<string, mixed> $response */
    private function updateDocumentFromResponse(Document $document, array $response): void
    {
        if (isset($response['id']) && is_string($response['id'])) {
            $document->setDocumentId($response['id']);
        }
        if (isset($response['processing_status']) && is_string($response['processing_status'])) {
            $document->setProcessingStatus($response['processing_status']);
        }
        if (isset($response['word_count']) && (is_int($response['word_count']) || is_numeric($response['word_count']))) {
            $document->setWordCount((int) $response['word_count']);
        }
        if (isset($response['tokens']) && (is_int($response['tokens']) || is_numeric($response['tokens']))) {
            $document->setTokens((int) $response['tokens']);
        }
        if (isset($response['error']) && is_string($response['error'])) {
            $document->setErrorMessage($response['error']);
        }
    }

    private function handleDocumentFailure(Document $document, \Exception $e): void
    {
        $document->setProcessingStatus('failed');
        $document->setErrorMessage($e->getMessage());
        $this->documentRepository->save($document);
        error_log(sprintf('Document creation failed: %s', $e->getMessage()));
    }

    /** @return array<string, mixed> */
    private function createDocumentBackup(Document $document): array
    {
        return [
            'name' => $document->getName(),
            'content' => $document->getContent(),
        ];
    }

    private function applyDocumentUpdates(Document $document, ?string $name, ?string $content): void
    {
        if (null !== $name && $name !== $document->getName()) {
            $document->setName($name);
        }
        if (null !== $content && $content !== $document->getContent()) {
            $document->setContent($content);
        }
    }

    private function syncDocumentToDify(Document $document): void
    {
        $documentId = $document->getDocumentId();
        $dataset = $document->getDataset();

        if (null !== $documentId && null !== $dataset) {
            $datasetId = $dataset->getDatasetId();
            if (null !== $datasetId) {
                $content = $document->getContent();
                $name = $document->getName();
                if (null !== $content) {
                    $this->httpClient->updateDocumentByText(
                        $datasetId,
                        $documentId,
                        $name,
                        $content,
                        null
                    );
                }
            }
        }
    }

    private function completeDocumentUpdate(Document $document, ?string $name, ?string $content): void
    {
        $document->setUpdatedAt($this->clock->now());
        $this->documentRepository->save($document);

        $this->eventDispatcher->dispatch(new DocumentOperationSuccessEvent(
            $document,
            'update',
            ['name' => $name, 'content_length' => null !== $content ? mb_strlen($content) : null]
        ));
    }

    /** @param array<string, mixed> $backup */
    private function restoreFromBackup(Document $document, array $backup): void
    {
        if (is_string($backup['name'])) {
            $document->setName($backup['name']);
        }
        if (is_string($backup['content']) || is_null($backup['content'])) {
            $document->setContent($backup['content']);
        }
    }

    /** @param array<string, mixed> $metadata */
    private function dispatchSuccessEvent(Document $document, string $operationType, array $metadata): void
    {
        $this->eventDispatcher->dispatch(new DocumentOperationSuccessEvent(
            $document,
            $operationType,
            $metadata
        ));
    }

    /** @param array<string, mixed> $metadata */
    private function dispatchFailedEvent(Document $document, string $operationType, \Exception $e, array $metadata): void
    {
        $this->eventDispatcher->dispatch(new DocumentOperationFailedEvent(
            $document,
            $operationType,
            $e->getMessage(),
            $e,
            $metadata
        ));
    }
}
