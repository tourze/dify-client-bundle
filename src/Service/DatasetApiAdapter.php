<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;

/**
 * Dataset API 适配器
 *
 * 提供Controller层的API适配方法，将业务逻辑委托给DatasetService
 */
readonly class DatasetApiAdapter
{
    public function __construct(
        private DatasetService $datasetService,
    ) {
    }

    /**
     * 获取数据集列表（Controller适配方法）
     *
     * @param array<string> $tagIds
     * @return array<string, mixed>
     */
    public function getDatasets(int $page = 1, int $limit = 20, ?string $keyword = null, array $tagIds = []): array
    {
        $offset = ($page - 1) * $limit;
        $tagNames = []; // 可以根据tagIds查找标签名称

        $datasets = $this->datasetService->searchDatasets($keyword, $tagNames, null, $limit, $offset);

        return [
            'data' => array_map(fn ($dataset) => $this->formatDatasetForApi($dataset), $datasets),
            'page' => $page,
            'limit' => $limit,
            'total' => count($datasets),
        ];
    }

    /**
     * 获取单个数据集（Controller适配方法）
     *
     * @return array<string, mixed>
     */
    public function getDataset(string $datasetId): array
    {
        $dataset = $this->findDatasetOrFail($datasetId);

        return $this->formatDatasetForApi($dataset);
    }

    /**
     * 删除数据集（Controller适配方法）
     *
     * @return array<string, mixed>
     */
    public function deleteDatasetById(string $datasetId): array
    {
        $dataset = $this->findDatasetOrFail($datasetId);
        $this->datasetService->deleteDataset($dataset);

        return ['message' => 'Dataset deleted successfully'];
    }

    /**
     * 更新数据集（Controller适配方法）
     *
     * @param array<string, mixed>|null $retrievalModel
     * @return array<string, mixed>
     */
    public function updateDatasetById(
        string $datasetId,
        ?string $name = null,
        ?string $description = null,
        ?string $permission = null,
        ?string $indexingTechnique = null,
        ?string $embeddingModel = null,
        ?string $embeddingModelProvider = null,
        ?array $retrievalModel = null,
    ): array {
        $dataset = $this->findDatasetOrFail($datasetId);
        $this->datasetService->updateDataset($dataset, $name, $description);

        return array_merge($this->formatDatasetForApi($dataset), [
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:s\Z'),
        ]);
    }

    /**
     * 从数据集检索（Controller适配方法）
     *
     * @param array<string, mixed> $retrievalModel
     * @return array<string, mixed>
     */
    public function retrieveFromDatasetById(string $datasetId, string $query, string $user, array $retrievalModel = []): array
    {
        $dataset = $this->findDatasetOrFail($datasetId);

        return $this->datasetService->retrieveFromDataset($dataset, $query);
    }

    /**
     * 获取嵌入模型列表
     *
     * @return array<string, mixed>
     */
    public function getEmbeddingModelsList(): array
    {
        return $this->datasetService->getEmbeddingModels();
    }

    private function findDatasetOrFail(string $datasetId): Dataset
    {
        $dataset = $this->datasetService->findByDatasetId($datasetId);
        if (null === $dataset) {
            throw new DifyRuntimeException('Dataset not found: ' . $datasetId);
        }

        return $dataset;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDatasetForApi(Dataset $dataset): array
    {
        return [
            'id' => $dataset->getDatasetId(),
            'name' => $dataset->getName(),
            'description' => $dataset->getDescription(),
            'indexing_technique' => $dataset->getIndexingTechnique(),
            'document_count' => $dataset->getDocumentCount(),
            'word_count' => $dataset->getWordCount(),
            'created_at' => $dataset->getCreateTime()?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
