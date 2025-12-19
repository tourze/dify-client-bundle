<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;
use Tourze\DifyClientBundle\Exception\DifySettingNotFoundException;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;

/**
 * 数据集HTTP客户端 - 处理所有与Dify API的HTTP通信
 */
final readonly class DatasetHttpClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private DifySettingRepository $settingRepository,
    ) {
    }

    /**
     * 创建数据集
     * @return array<string, mixed>
     */
    public function createDataset(string $name, ?string $description, string $indexingTechnique): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, '/datasets');

        $payload = [
            'name' => $name,
            'indexing_technique' => $indexingTechnique,
        ];

        if (null !== $description) {
            $payload['description'] = $description;
        }

        return $this->makeJsonRequest($setting, 'POST', $url, $payload, 201);
    }

    /**
     * 更新数据集
     */
    public function updateDataset(string $datasetId, string $name, ?string $description): void
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}");

        $payload = ['name' => $name];
        if (null !== $description) {
            $payload['description'] = $description;
        }

        $this->makeJsonRequest($setting, 'PUT', $url, $payload);
    }

    /**
     * 删除数据集
     */
    public function deleteDataset(string $datasetId): void
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}");

        $this->makeDeleteRequest($setting, $url, 204);
    }

    /**
     * 获取数据集详情
     * @return array<string, mixed>
     */
    public function getDatasetInfo(string $datasetId): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}");

        return $this->makeGetRequest($setting, $url);
    }

    /**
     * 从数据集检索
     * @return array<string, mixed>
     */
    public function retrieveFromDataset(
        string $datasetId,
        string $query,
        ?int $topK,
        ?float $scoreThreshold,
        bool $rerank,
    ): array {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/retrieve");

        $payload = ['query' => $query];
        if (null !== $topK) {
            $payload['top_k'] = $topK;
        }
        if (null !== $scoreThreshold) {
            $payload['score_threshold'] = $scoreThreshold;
        }
        if ($rerank) {
            $payload['rerank'] = true;
        }

        return $this->makeJsonRequest($setting, 'POST', $url, $payload);
    }

    /**
     * 获取嵌入模型列表
     * @return array<string, mixed>
     */
    public function getEmbeddingModels(): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, '/datasets/embedding-models');

        return $this->makeGetRequest($setting, $url);
    }

    // ==================== 私有辅助方法 ====================

    private function getActiveSetting(): DifySetting
    {
        $setting = $this->settingRepository->findActiveSetting();
        if (null === $setting) {
            throw new DifySettingNotFoundException();
        }

        return $setting;
    }

    private function buildUrl(DifySetting $setting, string $path): string
    {
        return rtrim($setting->getBaseUrl(), '/') . $path;
    }

    /**
     * @return array<string, string>
     */
    private function buildAuthHeaders(DifySetting $setting): array
    {
        return [
            'Authorization' => 'Bearer ' . $setting->getApiKey(),
        ];
    }

    /**
     * 执行GET请求
     * @return array<string, mixed>
     */
    private function makeGetRequest(DifySetting $setting, string $url): array
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $this->buildAuthHeaders($setting),
                'timeout' => $setting->getTimeout(),
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new DifyRuntimeException('Request failed: ' . $response->getContent(false));
            }

            /** @var array<string, mixed> */
            return $response->toArray();
        } catch (\Exception $e) {
            throw new DifyRuntimeException(sprintf('HTTP request failed: %s', $e->getMessage()));
        }
    }

    /**
     * 执行JSON请求
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function makeJsonRequest(DifySetting $setting, string $method, string $url, array $payload, int $expectedStatus = 200): array
    {
        try {
            $response = $this->httpClient->request($method, $url, [
                'headers' => $this->buildAuthHeaders($setting) + ['Content-Type' => 'application/json'],
                'json' => $payload,
                'timeout' => $setting->getTimeout(),
            ]);

            if ($expectedStatus !== $response->getStatusCode()) {
                throw new DifyRuntimeException('Request failed: ' . $response->getContent(false));
            }

            /** @var array<string, mixed> */
            return $response->toArray();
        } catch (\Exception $e) {
            throw new DifyRuntimeException(sprintf('HTTP request failed: %s', $e->getMessage()));
        }
    }

    /**
     * 执行DELETE请求
     */
    private function makeDeleteRequest(DifySetting $setting, string $url, int $expectedStatus = 200): void
    {
        try {
            $response = $this->httpClient->request('DELETE', $url, [
                'headers' => $this->buildAuthHeaders($setting),
                'timeout' => $setting->getTimeout(),
            ]);

            if ($expectedStatus !== $response->getStatusCode()) {
                throw new DifyRuntimeException('Request failed: ' . $response->getContent(false));
            }
        } catch (\Exception $e) {
            throw new DifyRuntimeException(sprintf('HTTP request failed: %s', $e->getMessage()));
        }
    }
}
