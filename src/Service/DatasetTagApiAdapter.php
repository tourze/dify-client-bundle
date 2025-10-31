<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Entity\DatasetTag;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;
use Tourze\DifyClientBundle\Repository\DatasetTagRepository;

/**
 * Dataset标签 API 适配器
 *
 * 提供标签管理的API适配方法
 */
readonly class DatasetTagApiAdapter
{
    public function __construct(
        private DatasetService $datasetService,
        private DatasetTagRepository $tagRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDatasetTags(): array
    {
        $tags = $this->datasetService->getAllTags();

        return [
            'data' => array_map(fn ($tag) => $this->formatTagForApi($tag), $tags),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createDatasetTag(string $name): array
    {
        $tag = $this->datasetService->findOrCreateTag($name);

        return $this->formatTagForApi($tag);
    }

    /**
     * @return array<string, mixed>
     */
    public function updateDatasetTag(string $tagId, string $name): array
    {
        $tag = $this->findTagOrFail($tagId);
        $tag->setName($name);
        $this->tagRepository->save($tag);

        return array_merge($this->formatTagForApi($tag), [
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:s\Z'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteDatasetTag(string $tagId): array
    {
        $tag = $this->findTagOrFail($tagId);
        $this->tagRepository->remove($tag);

        return ['message' => 'Tag deleted successfully'];
    }

    /**
     * @return array<string, mixed>
     */
    public function bindDatasetTag(string $datasetId, string $tagId): array
    {
        [$dataset, $tag] = $this->findDatasetAndTag($datasetId, $tagId);

        if (!$dataset->getTags()->contains($tag)) {
            $this->datasetService->attachTags($dataset, [$tag->getName()]);
        }

        return ['message' => 'Tag bound successfully'];
    }

    /**
     * @return array<string, mixed>
     */
    public function unbindDatasetTag(string $datasetId, string $tagId): array
    {
        [$dataset, $tag] = $this->findDatasetAndTag($datasetId, $tagId);

        if ($dataset->getTags()->contains($tag)) {
            $this->datasetService->detachTags($dataset, [$tag->getName()]);
        }

        return ['message' => 'Tag unbound successfully'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDatasetBoundTags(string $datasetId): array
    {
        $dataset = $this->findDatasetOrFail($datasetId);
        $tags = $dataset->getTags()->toArray();

        return [
            'data' => array_map(fn ($tag) => $this->formatTagForApi($tag), $tags),
        ];
    }

    /** @return array{Dataset, DatasetTag} */
    private function findDatasetAndTag(string $datasetId, string $tagId): array
    {
        $dataset = $this->findDatasetOrFail($datasetId);
        $tag = $this->findTagOrFail($tagId);

        return [$dataset, $tag];
    }

    private function findDatasetOrFail(string $datasetId): Dataset
    {
        $dataset = $this->datasetService->findByDatasetId($datasetId);
        if (null === $dataset) {
            throw new DifyRuntimeException('Dataset not found: ' . $datasetId);
        }

        return $dataset;
    }

    private function findTagOrFail(string $tagId): DatasetTag
    {
        $tag = $this->tagRepository->find($tagId);
        if (null === $tag) {
            throw new DifyRuntimeException('Tag not found: ' . $tagId);
        }

        return $tag;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTagForApi(DatasetTag $tag): array
    {
        return [
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'binding_count' => $tag->getUsageCount(),
            'created_at' => $tag->getCreateTime()?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
