<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpClient\CurlHttpClient;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Entity\DatasetTag;
use Tourze\DifyClientBundle\Entity\RetrieverResource;
use Tourze\DifyClientBundle\Event\DatasetOperationFailedEvent;
use Tourze\DifyClientBundle\Event\DatasetOperationSuccessEvent;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;
use Tourze\DifyClientBundle\Repository\DatasetRepository;
use Tourze\DifyClientBundle\Repository\DatasetTagRepository;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;
use Tourze\DifyClientBundle\Repository\RetrieverResourceRepository;

/**
 * 数据集管理服务
 *
 * 核心业务逻辑，HTTP通信由DatasetHttpClient处理
 */
final readonly class DatasetService
{
    private DatasetHttpClient $httpClient;

    private DatasetFactory $factory;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private DifySettingRepository $settingRepository,
        private DatasetRepository $datasetRepository,
        private DatasetTagRepository $tagRepository,
        private RetrieverResourceRepository $retrieverResourceRepository,
        private ClockInterface $clock,
        ?DatasetHttpClient $httpClient = null,
        ?DatasetFactory $factory = null,
    ) {
        $this->httpClient = $httpClient ?? new DatasetHttpClient(new CurlHttpClient(), $this->settingRepository);
        $this->factory = $factory ?? new DatasetFactory($this->clock);
    }

    // ==================== 核心业务方法 ====================

    /**
     * 创建数据集
     *
     * @param array<string>|null $tags
     */
    public function createDataset(
        string $name,
        ?string $description = null,
        string $indexingTechnique = 'high_quality',
        ?array $tags = null,
    ): Dataset {
        $dataset = $this->factory->create($name, $description, $indexingTechnique);
        $this->datasetRepository->save($dataset);

        return $this->executeDatasetCreation($dataset, $name, $description, $indexingTechnique, $tags);
    }

    /**
     * 更新数据集
     */
    public function updateDataset(Dataset $dataset, ?string $name = null, ?string $description = null): Dataset
    {
        return $this->executeDatasetUpdate($dataset, $name, $description);
    }

    /**
     * 删除数据集
     */
    public function deleteDataset(Dataset $dataset): void
    {
        $datasetId = $dataset->getDatasetId();

        // 尝试删除Dify端的数据集
        if (null !== $datasetId) {
            try {
                $this->httpClient->deleteDataset($datasetId);
            } catch (\Exception $e) {
                error_log(sprintf('Failed to delete Dify dataset %s: %s', $datasetId, $e->getMessage()));
            }
        }

        $this->datasetRepository->remove($dataset);

        $this->eventDispatcher->dispatch(new DatasetOperationSuccessEvent(
            $dataset,
            'delete',
            ['dataset_id' => $datasetId]
        ));
    }

    /**
     * 同步数据集信息（从 Dify API 获取最新信息）
     */
    public function syncDataset(Dataset $dataset): Dataset
    {
        $datasetId = $dataset->getDatasetId();
        if (null === $datasetId) {
            throw new DifyRuntimeException('Dataset does not have Dify dataset ID');
        }

        $response = $this->httpClient->getDatasetInfo($datasetId);
        $this->updateDatasetFromResponse($dataset, $response);
        $this->datasetRepository->save($dataset);

        return $dataset;
    }

    /**
     * 从知识库检索相关信息
     *
     * @return array<string, mixed>
     */
    public function retrieveFromDataset(
        Dataset $dataset,
        string $query,
        ?int $topK = null,
        ?float $scoreThreshold = null,
        bool $rerank = false,
    ): array {
        $datasetId = $this->validateDatasetId($dataset);
        $response = $this->httpClient->retrieveFromDataset($datasetId, $query, $topK, $scoreThreshold, $rerank);
        $resources = $this->processRetrievalResponse($dataset, $query, $response);

        return $this->buildRetrievalResult($query, $resources, $response);
    }

    private function validateDatasetId(Dataset $dataset): string
    {
        $datasetId = $dataset->getDatasetId();
        if (null === $datasetId) {
            throw new DifyRuntimeException('Dataset must have a valid Dify dataset ID');
        }

        return $datasetId;
    }

    /**
     * @param array<string, mixed> $response
     * @return array<RetrieverResource>
     */
    private function processRetrievalResponse(Dataset $dataset, string $query, array $response): array
    {
        $resources = [];
        $dataArray = $response['data'] ?? [];

        if (is_array($dataArray)) {
            $resources = $this->createRetrieverResources($dataset, $query, $dataArray);
        }

        return $resources;
    }

    /**
     * @param array<mixed> $dataArray
     * @return array<RetrieverResource>
     */
    private function createRetrieverResources(Dataset $dataset, string $query, array $dataArray): array
    {
        $resources = [];
        foreach ($dataArray as $item) {
            if (!is_array($item)) {
                continue;
            }
            /** @var array<string, mixed> $item */
            $resource = $this->createRetrieverResource($dataset, $query, $item);
            $this->retrieverResourceRepository->save($resource);
            $resources[] = $resource;
        }

        return $resources;
    }

    /**
     * @param array<RetrieverResource> $resources
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    private function buildRetrievalResult(string $query, array $resources, array $response): array
    {
        return [
            'query' => $query,
            'resources' => $resources,
            'total' => count($resources),
            'metadata' => $response['metadata'] ?? [],
        ];
    }

    // ==================== 查询方法 ====================

    /**
     * 根据数据集ID查找数据集
     */
    public function findByDatasetId(string $datasetId): ?Dataset
    {
        return $this->datasetRepository->findOneBy(['datasetId' => $datasetId]);
    }

    /**
     * 搜索数据集
     * @param array<string>|null $tags
     * @return array<Dataset>
     */
    public function searchDatasets(
        ?string $query = null,
        ?array $tags = null,
        ?string $indexingTechnique = null,
        int $limit = 50,
        int $offset = 0,
    ): array {
        // 将字符串标签转换为 DatasetTag 对象
        $tagObjects = null;
        if (null !== $tags && [] !== $tags) {
            $tagObjects = [];
            foreach ($tags as $tagName) {
                $tagObjects[] = $this->findOrCreateTag($tagName);
            }
        }

        return $this->datasetRepository->search($query, $tagObjects, $indexingTechnique, $limit, $offset);
    }

    /**
     * 获取所有数据集
     * @return array<Dataset>
     */
    public function getAllDatasets(int $limit = 50, int $offset = 0): array
    {
        return $this->datasetRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * 获取数据集统计信息
     *
     * @return array<string, mixed>
     */
    public function getDatasetStats(): array
    {
        return $this->datasetRepository->getStatistics($this->clock);
    }

    /**
     * 批量同步所有数据集
     *
     * @return array<string, mixed>
     */
    public function syncAllDatasets(): array
    {
        $datasets = $this->datasetRepository->findAll();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($datasets as $dataset) {
            try {
                $this->syncDataset($dataset);
                ++$successCount;
            } catch (\Exception $e) {
                ++$failedCount;
                $errors[] = sprintf('Dataset %s: %s', $dataset->getName(), $e->getMessage());
            }
        }

        return [
            'success' => $successCount,
            'failed' => $failedCount,
            'errors' => $errors,
        ];
    }

    // ==================== 标签管理方法 ====================

    /**
     * 为数据集添加标签
     *
     * @param array<string> $tagNames
     */
    public function attachTags(Dataset $dataset, array $tagNames): void
    {
        foreach ($tagNames as $tagName) {
            $tag = $this->findOrCreateTag($tagName);
            if (!$dataset->getTags()->contains($tag)) {
                $dataset->addTag($tag);
            }
        }

        $this->datasetRepository->save($dataset);
    }

    /**
     * 从数据集移除标签
     *
     * @param array<string> $tagNames
     */
    public function detachTags(Dataset $dataset, array $tagNames): void
    {
        foreach ($tagNames as $tagName) {
            $tag = $this->tagRepository->findOneBy(['name' => $tagName]);
            if (null !== $tag && $dataset->getTags()->contains($tag)) {
                $dataset->removeTag($tag);
            }
        }

        $this->datasetRepository->save($dataset);
    }

    /**
     * 获取所有标签
     * @return array<DatasetTag>
     */
    public function getAllTags(): array
    {
        return $this->tagRepository->findAll();
    }

    /**
     * 获取流行的标签
     * @return array<array{name: string, count: int}>
     */
    public function getPopularTags(int $limit = 10): array
    {
        return $this->tagRepository->getPopularTags($limit);
    }

    /**
     * 清理未使用的标签
     */
    public function cleanupUnusedTags(): int
    {
        $unusedTags = $this->tagRepository->findUnusedTags();
        $cleanupCount = 0;

        foreach ($unusedTags as $tag) {
            $this->tagRepository->remove($tag);
            ++$cleanupCount;
        }

        return $cleanupCount;
    }

    // ==================== HTTP API 方法 ====================

    /**
     * 获取嵌入模型列表
     *
     * @return array<string, mixed>
     */
    public function getEmbeddingModels(): array
    {
        return $this->httpClient->getEmbeddingModels();
    }

    // ==================== 私有辅助方法 ====================

    /**
     * @param array<string>|null $tags
     */
    private function executeDatasetCreation(
        Dataset $dataset,
        string $name,
        ?string $description,
        string $indexingTechnique,
        ?array $tags,
    ): Dataset {
        try {
            $response = $this->httpClient->createDataset($name, $description, $indexingTechnique);

            if (isset($response['id']) && is_string($response['id'])) {
                $dataset->setDatasetId($response['id']);
                $this->datasetRepository->save($dataset);
            }

            if ([] !== $tags && null !== $tags) {
                $this->attachTags($dataset, $tags);
            }
            $this->dispatchSuccessEvent($dataset, 'create', ['technique' => $indexingTechnique, 'tags' => $tags ?? []]);

            return $dataset;
        } catch (\Exception $e) {
            error_log(sprintf('Dataset creation failed: %s', $e->getMessage()));
            $this->dispatchFailedEvent($dataset, 'create', $e, ['name' => $name]);
            throw $e;
        }
    }

    private function executeDatasetUpdate(Dataset $dataset, ?string $name, ?string $description): Dataset
    {
        $backup = ['name' => $dataset->getName(), 'description' => $dataset->getDescription()];

        try {
            if (null !== $name && $name !== $dataset->getName()) {
                $dataset->setName($name);
            }
            if (null !== $description && $description !== $dataset->getDescription()) {
                $dataset->setDescription($description);
            }

            $datasetId = $dataset->getDatasetId();
            if (null !== $datasetId) {
                $this->httpClient->updateDataset($datasetId, $dataset->getName(), $dataset->getDescription());
            }

            $this->datasetRepository->save($dataset);
            $this->dispatchSuccessEvent($dataset, 'update', ['name' => $name, 'description' => $description]);

            return $dataset;
        } catch (\Exception $e) {
            $dataset->setName($backup['name']);
            $dataset->setDescription($backup['description']);

            $this->dispatchFailedEvent($dataset, 'update', $e, $backup);
            throw new DifyRuntimeException(sprintf('Failed to update dataset: %s', $e->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $response
     */
    private function updateDatasetFromResponse(Dataset $dataset, array $response): void
    {
        if (isset($response['name']) && is_string($response['name'])) {
            $dataset->setName($response['name']);
        }

        $description = $response['description'] ?? null;
        if (is_string($description) || is_null($description)) {
            $dataset->setDescription($description);
        }

        if (isset($response['indexing_technique']) && is_string($response['indexing_technique'])) {
            $dataset->setIndexingTechnique($response['indexing_technique']);
        }

        if (isset($response['document_count']) && is_numeric($response['document_count'])) {
            $dataset->setDocumentCount((int) $response['document_count']);
        }

        if (isset($response['word_count']) && is_numeric($response['word_count'])) {
            $dataset->setWordCount((int) $response['word_count']);
        }
    }

    public function findOrCreateTag(string $tagName): DatasetTag
    {
        $tag = $this->tagRepository->findOneBy(['name' => $tagName]);

        if (null === $tag) {
            $tag = new DatasetTag();
            $tag->setName($tagName);
            $tag->setCreateTime($this->clock->now());
            $this->tagRepository->save($tag);
        }

        return $tag;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function createRetrieverResource(Dataset $dataset, string $query, array $item): RetrieverResource
    {
        $resource = new RetrieverResource();
        $resource->setDataset($dataset);
        $resource->setQuery($query);
        $resource->setContent(is_string($item['content'] ?? '') ? ($item['content'] ?? '') : '');
        $score = $item['score'] ?? 0.0;
        $resource->setScore((float) (is_numeric($score) ? $score : 0.0));
        $metadata = $item['metadata'] ?? [];
        $validatedMetadata = null;
        if (is_array($metadata) && !array_is_list($metadata)) {
            /** @var array<string, mixed> $validatedMetadata */
            $validatedMetadata = $metadata;
        }
        $resource->setMetadata($validatedMetadata);
        $resource->setCreateTime($this->clock->now());

        return $resource;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function dispatchSuccessEvent(Dataset $dataset, string $operation, array $metadata): void
    {
        $this->eventDispatcher->dispatch(new DatasetOperationSuccessEvent(
            $dataset,
            $operation,
            $metadata
        ));
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function dispatchFailedEvent(Dataset $dataset, string $operation, \Exception $e, array $metadata): void
    {
        $this->eventDispatcher->dispatch(new DatasetOperationFailedEvent(
            $dataset,
            $operation,
            $e->getMessage(),
            $e,
            $metadata
        ));
    }
}
