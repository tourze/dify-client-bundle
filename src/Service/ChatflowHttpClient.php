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
 * 聊天流HTTP客户端 - 处理所有与Dify Chatflow API的HTTP通信
 */
final readonly class ChatflowHttpClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private DifySettingRepository $settingRepository,
    ) {
    }

    /**
     * 发送聊天消息
     * @return array<string, mixed>
     */
    public function sendChatMessage(string $conversationId, string $content, string $userId): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, '/chat-messages');

        $payload = [
            'inputs' => [],
            'query' => $content,
            'response_mode' => 'blocking',
            'conversation_id' => $conversationId,
            'user' => $userId,
        ];

        return $this->makeJsonRequest($setting, 'POST', $url, $payload);
    }

    /**
     * 发送流式聊天消息
     */
    public function sendStreamChatMessage(string $conversationId, string $content, string $userId): \Generator
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, '/chat-messages');

        $payload = [
            'inputs' => [],
            'query' => $content,
            'response_mode' => 'streaming',
            'conversation_id' => $conversationId,
            'user' => $userId,
        ];

        $response = $this->httpClient->request('POST', $url, [
            'headers' => $this->buildAuthHeaders($setting) + ['Content-Type' => 'application/json'],
            'json' => $payload,
            'timeout' => $setting->getTimeout(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Dify API stream request failed: ' . $response->getContent(false));
        }

        foreach ($this->httpClient->stream($response) as $chunk) {
            yield $chunk->getContent();
        }
    }

    /**
     * 删除会话
     */
    public function deleteConversation(string $conversationId): void
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/conversations/{$conversationId}");

        $this->makeDeleteRequest($setting, $url, 204);
    }

    /**
     * 重命名会话
     */
    public function renameConversation(string $conversationId, string $newName): void
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/conversations/{$conversationId}");

        $this->makeJsonRequest($setting, 'PUT', $url, ['name' => $newName]);
    }

    /**
     * 停止响应
     */
    public function stopResponse(string $taskId): void
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/chat-messages/{$taskId}");

        $this->makeDeleteRequest($setting, $url, 204);
    }

    /**
     * 获取建议问题
     * @return array<string, mixed>
     */
    public function getSuggestedQuestions(string $messageId): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/messages/{$messageId}/suggested-questions");

        return $this->makeGetRequest($setting, $url);
    }

    /**
     * 获取会话变量
     * @return array<string, mixed>
     */
    public function getConversationVariables(string $conversationId): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/conversations/{$conversationId}/variables");

        return $this->makeGetRequest($setting, $url);
    }

    /**
     * 获取应用信息
     * @return array<string, mixed>
     */
    public function getAppInfo(): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, '/info');

        return $this->makeGetRequest($setting, $url);
    }

    /**
     * 获取应用参数
     * @return array<string, mixed>
     */
    public function getAppParameters(): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, '/parameters');

        return $this->makeGetRequest($setting, $url);
    }

    /**
     * 获取应用Meta信息
     * @return array<string, mixed>
     */
    public function getAppMeta(): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, '/meta');

        return $this->makeGetRequest($setting, $url);
    }

    /**
     * 获取应用站点信息
     * @return array<string, mixed>
     */
    public function getAppSite(): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, '/site');

        return $this->makeGetRequest($setting, $url);
    }

    /**
     * 获取会话列表
     * @return array<string, mixed>
     */
    public function getConversations(int $page = 1, int $limit = 20, ?string $userId = null): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, '/conversations');

        $params = ['page' => $page, 'limit' => $limit];
        if (null !== $userId) {
            $params['user'] = $userId;
        }

        $queryString = http_build_query($params);
        $fullUrl = $url . '?' . $queryString;

        return $this->makeGetRequest($setting, $fullUrl);
    }

    /**
     * 获取会话历史消息
     * @return array<string, mixed>
     */
    public function getConversationMessages(string $conversationId, ?string $userId = null, int $limit = 20, ?string $lastId = null): array
    {
        $setting = $this->getActiveSetting();
        $url = $this->buildUrl($setting, "/conversations/{$conversationId}/messages");

        $params = ['limit' => $limit];
        if (null !== $userId) {
            $params['user'] = $userId;
        }
        if (null !== $lastId) {
            $params['last_id'] = $lastId;
        }

        $queryString = http_build_query($params);
        $fullUrl = $url . '?' . $queryString;

        return $this->makeGetRequest($setting, $fullUrl);
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
    private function makeJsonRequest(DifySetting $setting, string $method, string $url, array $payload): array
    {
        try {
            $response = $this->httpClient->request($method, $url, [
                'headers' => $this->buildAuthHeaders($setting) + ['Content-Type' => 'application/json'],
                'json' => $payload,
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
