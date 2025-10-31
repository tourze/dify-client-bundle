<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;
use Tourze\DifyClientBundle\Exception\DifySettingNotFoundException;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;

/**
 * 文档HTTP客户端 - 处理所有与Dify API的HTTP通信
 * 分离HTTP通信复杂度，简化DocumentService
 */
readonly class DocumentHttpClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private DifySettingRepository $settingRepository,
    ) {
    }

    /**
     * 获取文档列表
     * @return array<string, mixed>
     */
    public function getDocuments(string $datasetId, int $page = 1, int $limit = 20, ?string $keyword = null): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/documents");

        $params = ['page' => $page, 'limit' => $limit];
        if (null !== $keyword) {
            $params['keyword'] = $keyword;
        }

        return $this->makeGetRequest($setting, $url, $params);
    }

    /**
     * 通过文件创建文档
     * @param array<string, mixed> $processRule
     * @return array<string, mixed>
     */
    public function createDocumentByFile(
        string $datasetId,
        UploadedFile $file,
        string $name,
        string $indexingTechnique,
        array $processRule = [],
    ): array {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/document/create_by_file");

        $body = [
            'file' => fopen($file->getPathname(), 'r'),
            'name' => $name,
            'indexing_technique' => $indexingTechnique,
        ];

        if ([] !== $processRule) {
            $body['process_rule'] = json_encode($processRule);
        }

        return $this->makeMultipartRequest($setting, $url, $body, 201);
    }

    /**
     * 通过文本创建文档
     * @param array<string, mixed> $processRule
     * @return array<string, mixed>
     */
    public function createDocumentByText(
        string $datasetId,
        string $name,
        string $text,
        string $indexingTechnique,
        array $processRule = [],
    ): array {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/document/create_by_text");

        $payload = [
            'name' => $name,
            'text' => $text,
            'indexing_technique' => $indexingTechnique,
            'process_rule' => $processRule,
        ];

        return $this->makeJsonRequest($setting, 'POST', $url, $payload, 201);
    }

    /**
     * 获取文档详情
     * @return array<string, mixed>
     */
    public function getDocument(string $datasetId, string $documentId): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/documents/{$documentId}");

        return $this->makeGetRequest($setting, $url);
    }

    /**
     * 通过文本更新文档
     * @param array<string, mixed>|null $processRule
     * @return array<string, mixed>
     */
    public function updateDocumentByText(
        string $datasetId,
        string $documentId,
        ?string $name,
        string $text,
        ?array $processRule,
    ): array {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/documents/{$documentId}/update_by_text");

        $payload = ['text' => $text];
        if (null !== $name) {
            $payload['name'] = $name;
        }
        if (null !== $processRule) {
            $payload['process_rule'] = $processRule;
        }

        return $this->makeJsonRequest($setting, 'PUT', $url, $payload);
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
        ?string $name,
        ?array $processRule,
    ): array {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/documents/{$documentId}/update_by_file");

        $body = ['file' => fopen($file->getPathname(), 'r')];
        if (null !== $name) {
            $body['name'] = $name;
        }
        if (null !== $processRule) {
            $body['process_rule'] = json_encode($processRule);
        }

        return $this->makeMultipartRequest($setting, $url, $body);
    }

    /**
     * 更新文档状态
     * @return array<string, mixed>
     */
    public function updateDocumentStatus(string $datasetId, string $documentId, bool $enabled): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/documents/{$documentId}/status");

        return $this->makeJsonRequest($setting, 'PATCH', $url, ['enabled' => $enabled]);
    }

    /**
     * 获取文档索引状态
     * @return array<string, mixed>
     */
    public function getDocumentIndexingStatus(string $datasetId, string $documentId): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/documents/{$documentId}/indexing-status");

        return $this->makeGetRequest($setting, $url);
    }

    /**
     * 删除文档
     * @return array<string, mixed>
     */
    public function deleteDocument(string $datasetId, string $documentId): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/documents/{$documentId}");

        return $this->makeDeleteRequest($setting, $url);
    }

    // ==================== 文档块相关方法 ====================

    /**
     * 获取文档块
     * @return array<string, mixed>
     */
    public function getDocumentSegments(string $datasetId, string $documentId, ?string $keyword, ?string $status): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/documents/{$documentId}/segments");

        $params = [];
        if (null !== $keyword) {
            $params['keyword'] = $keyword;
        }
        if (null !== $status) {
            $params['status'] = $status;
        }

        return $this->makeGetRequest($setting, $url, $params);
    }

    /**
     * 创建文档块
     * @param array<string> $keywords
     * @return array<string, mixed>
     */
    public function createDocumentSegment(
        string $datasetId,
        string $documentId,
        string $content,
        ?string $answer,
        array $keywords,
    ): array {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/documents/{$documentId}/segments");

        $payload = ['content' => $content];
        if (null !== $answer) {
            $payload['answer'] = $answer;
        }
        if ([] !== $keywords) {
            $payload['keywords'] = $keywords;
        }

        return $this->makeJsonRequest($setting, 'POST', $url, $payload, 201);
    }

    /**
     * 获取文档块详情
     * @return array<string, mixed>
     */
    public function getDocumentSegment(string $datasetId, string $documentId, string $segmentId): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/documents/{$documentId}/segments/{$segmentId}");

        return $this->makeGetRequest($setting, $url);
    }

    /**
     * 更新文档块
     * @param array<string>|null $keywords
     * @return array<string, mixed>
     */
    public function updateDocumentSegment(
        string $datasetId,
        string $documentId,
        string $segmentId,
        ?string $content,
        ?string $answer,
        ?array $keywords,
        ?bool $enabled,
    ): array {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/documents/{$documentId}/segments/{$segmentId}");

        $payload = [];
        if (null !== $content) {
            $payload['content'] = $content;
        }
        if (null !== $answer) {
            $payload['answer'] = $answer;
        }
        if (null !== $keywords) {
            $payload['keywords'] = $keywords;
        }
        if (null !== $enabled) {
            $payload['enabled'] = $enabled;
        }

        return $this->makeJsonRequest($setting, 'PUT', $url, $payload);
    }

    /**
     * 删除文档块
     * @return array<string, mixed>
     */
    public function deleteDocumentSegment(string $datasetId, string $documentId, string $segmentId): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/documents/{$documentId}/segments/{$segmentId}");

        return $this->makeDeleteRequest($setting, $url);
    }

    // ==================== 子块相关方法 ====================

    /**
     * 创建子块
     * @return array<string, mixed>
     */
    public function createChildChunk(
        string $datasetId,
        string $documentId,
        string $segmentId,
        string $content,
    ): array {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/documents/{$documentId}/segments/{$segmentId}/child-chunks");

        return $this->makeJsonRequest($setting, 'POST', $url, ['content' => $content], 201);
    }

    /**
     * 获取子块
     * @return array<string, mixed>
     */
    public function getChildChunk(string $datasetId, string $documentId, string $segmentId, string $chunkId): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/documents/{$documentId}/segments/{$segmentId}/child-chunks/{$chunkId}");

        return $this->makeGetRequest($setting, $url);
    }

    /**
     * 更新子块
     * @return array<string, mixed>
     */
    public function updateChildChunk(
        string $datasetId,
        string $documentId,
        string $segmentId,
        string $chunkId,
        string $content,
    ): array {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/documents/{$documentId}/segments/{$segmentId}/child-chunks/{$chunkId}");

        return $this->makeJsonRequest($setting, 'PUT', $url, ['content' => $content]);
    }

    /**
     * 删除子块
     * @return array<string, mixed>
     */
    public function deleteChildChunk(string $datasetId, string $documentId, string $segmentId, string $chunkId): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/datasets/{$datasetId}/documents/{$documentId}/segments/{$segmentId}/child-chunks/{$chunkId}");

        return $this->makeDeleteRequest($setting, $url);
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
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function makeGetRequest(DifySetting $setting, string $url, array $params = []): array
    {
        try {
            $options = [
                'headers' => $this->buildAuthHeaders($setting),
                'timeout' => $setting->getTimeout(),
            ];

            if ([] !== $params) {
                $options['query'] = $params;
            }

            $response = $this->httpClient->request('GET', $url, $options);

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
                'timeout' => max($setting->getTimeout(), 300),
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
     * 执行Multipart请求（文件上传）
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function makeMultipartRequest(DifySetting $setting, string $url, array $body, int $expectedStatus = 200): array
    {
        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => $this->buildAuthHeaders($setting),
                'body' => $body,
                'timeout' => max($setting->getTimeout(), 300),
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
     * @return array<string, mixed>
     */
    private function makeDeleteRequest(DifySetting $setting, string $url): array
    {
        try {
            $response = $this->httpClient->request('DELETE', $url, [
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
}
