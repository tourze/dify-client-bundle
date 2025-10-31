<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API请求处理器 - 简化控制器中的通用操作
 */
readonly class ApiRequestHandler
{
    /**
     * 解析并验证JSON请求数据
     * @return array<string, mixed>|JsonResponse
     */
    public function parseJsonRequest(Request $request): array|JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * 处理异常并返回错误响应
     */
    public function handleException(\Exception $e): JsonResponse
    {
        return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
    }

    /**
     * 执行操作并返回JSON响应
     */
    public function execute(callable $operation, int $successStatus = Response::HTTP_OK): JsonResponse
    {
        try {
            $result = $operation();

            return new JsonResponse($result, $successStatus);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * 验证字符串参数
     */
    public function validateStringParam(mixed $value, string $paramName): JsonResponse|string|null
    {
        if (null !== $value && !is_string($value)) {
            return new JsonResponse(
                ['error' => $paramName . ' must be a string'],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $value;
    }

    /**
     * 解析分页参数
     *
     * @return array<string, int>
     */
    public function parsePaginationParams(Request $request): array
    {
        return [
            'page' => (int) $request->query->get('page', 1),
            'limit' => (int) $request->query->get('limit', 20),
        ];
    }

    /**
     * 安全地转换为字符串
     */
    public function safeStringCast(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
            return (string) $value;
        }

        throw new \InvalidArgumentException('无法将值转换为字符串');
    }

    /**
     * 解析JSON字符串参数
     */
    public function parseJsonParam(string $jsonString, mixed $default = null): mixed
    {
        $decoded = json_decode($jsonString, true);

        return $decoded ?? $default;
    }
}
