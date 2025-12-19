<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\DifyClientBundle\Entity\Annotation;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Event\DifyErrorEvent;
use Tourze\DifyClientBundle\Event\DifyReplyEvent;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;
use Tourze\DifyClientBundle\Exception\DifySettingNotFoundException;
use Tourze\DifyClientBundle\Repository\AnnotationRepository;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;

/**
 * 标注管理服务
 *
 * 提供消息标注的创建、更新、删除和查询功能
 * 对应 API: GET/POST/PUT/DELETE /annotations
 */
final readonly class AnnotationService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EventDispatcherInterface $eventDispatcher,
        private DifySettingRepository $settingRepository,
        private AnnotationRepository $annotationRepository,
        private ClockInterface $clock,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 创建新的标注
     */
    public function createAnnotation(
        string $question,
        string $answer,
        ?Message $message = null,
        ?Conversation $conversation = null,
        string $userId = 'system',
    ): Annotation {
        $setting = $this->getActiveSetting();

        // 创建本地标注实体
        $annotation = $this->buildAnnotation($question, $answer, $message, $conversation, $userId);
        $this->persistAnnotation($annotation);

        try {
            // 同步创建到 Dify
            $response = $this->createDifyAnnotation($setting, $question, $answer, $conversation?->getConversationId());

            // 更新标注ID
            if (isset($response['id']) && is_string($response['id'])) {
                $annotation->setAnnotationId($response['id']);
                $this->persistAnnotation($annotation);
            }

            // 派发标注创建成功事件
            $this->eventDispatcher->dispatch(new DifyReplyEvent(
                conversation: $conversation,
                reply: "Annotation created successfully: {$answer}",
                originalMessage: $message,
                isComplete: true
            ));

            return $annotation;
        } catch (\Exception $e) {
            $this->handleAnnotationFailure($annotation, $e);

            // 派发标注创建失败事件
            $this->eventDispatcher->dispatch(new DifyErrorEvent(
                conversation: $conversation,
                message: $message,
                errorMessage: "Failed to create annotation: {$e->getMessage()}",
                exception: $e
            ));

            throw $e;
        }
    }

    /**
     * 更新标注
     */
    public function updateAnnotation(Annotation $annotation, ?string $question = null, ?string $answer = null): Annotation
    {
        $setting = $this->getActiveSetting();

        $originalQuestion = $annotation->getQuestion();
        $originalAnswer = $annotation->getAnswer();

        // 更新本地记录
        if (null !== $question && $question !== $originalQuestion) {
            $annotation->setQuestion($question);
        }
        if (null !== $answer && $answer !== $originalAnswer) {
            $annotation->setAnswer($answer);
        }

        try {
            // 同步更新到 Dify
            $annotationId = $annotation->getAnnotationId();
            if (null !== $annotationId && '' !== $annotationId) {
                $this->updateDifyAnnotation($setting, $annotationId, $annotation->getQuestion(), $annotation->getAnswer());
            }

            $this->persistAnnotation($annotation);

            return $annotation;
        } catch (\Exception $e) {
            // 回滚本地变更
            $annotation->setQuestion($originalQuestion);
            $annotation->setAnswer($originalAnswer);
            throw new DifyRuntimeException(sprintf('Failed to update annotation: %s', $e->getMessage()));
        }
    }

    /**
     * 删除标注
     */
    public function deleteAnnotation(Annotation $annotation): void
    {
        $setting = $this->getActiveSetting();

        // 如果有 annotationId，尝试删除 Dify 端的标注
        $annotationId = $annotation->getAnnotationId();
        if (null !== $annotationId && '' !== $annotationId) {
            try {
                $this->deleteDifyAnnotation($setting, $annotationId);
            } catch (\Exception $e) {
                // 记录错误但不阻止本地删除
                error_log(sprintf('Failed to delete Dify annotation %s: %s', $annotation->getAnnotationId(), $e->getMessage()));
            }
        }

        $this->entityManager->remove($annotation);
        $this->entityManager->flush();
    }

    /**
     * 启用或禁用标注
     */
    public function toggleAnnotation(Annotation $annotation, bool $enabled): Annotation
    {
        $annotation->setEnabled($enabled);
        $this->persistAnnotation($annotation);

        return $annotation;
    }

    /**
     * 根据标注ID查找标注
     */
    public function findByAnnotationId(string $annotationId): ?Annotation
    {
        return $this->annotationRepository->findOneBy(['annotationId' => $annotationId]);
    }

    /**
     * 获取会话的所有标注
     *
     * @return array<Annotation>
     */
    public function getConversationAnnotations(Conversation $conversation, bool $enabledOnly = false): array
    {
        $criteria = ['conversation' => $conversation];
        if ($enabledOnly) {
            $criteria['enabled'] = true;
        }

        return $this->annotationRepository->findBy($criteria, ['createdAt' => 'DESC']);
    }

    /**
     * 获取消息的标注
     *
     * @return array<Annotation>
     */
    public function getMessageAnnotations(Message $message, bool $enabledOnly = false): array
    {
        $criteria = ['message' => $message];
        if ($enabledOnly) {
            $criteria['enabled'] = true;
        }

        return $this->annotationRepository->findBy($criteria, ['createdAt' => 'DESC']);
    }

    /**
     * 搜索标注
     *
     * @return array<Annotation>
     */
    public function searchAnnotations(
        ?string $query = null,
        ?string $userId = null,
        ?bool $enabled = null,
        int $limit = 50,
        int $offset = 0,
    ): array {
        return $this->annotationRepository->search($query, $limit, $offset);
    }

    /**
     * 获取标注的匹配统计
     *
     * @return array<string, mixed>
     */
    public function getAnnotationStats(): array
    {
        $qb = $this->annotationRepository->createQueryBuilder('a');

        $totalAnnotations = (int) $qb
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $enabledAnnotations = (int) $qb
            ->select('COUNT(a.id)')
            ->where('a.enabled = true')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $totalHits = (int) $qb
            ->select('SUM(a.hitCount)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $avgHitCount = $totalAnnotations > 0
            ? round($totalHits / $totalAnnotations, 2)
            : 0;

        $topAnnotations = $qb
            ->select('a.question, a.hitCount')
            ->orderBy('a.hitCount', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getArrayResult()
        ;

        return [
            'total_annotations' => $totalAnnotations,
            'enabled_annotations' => $enabledAnnotations,
            'total_hits' => $totalHits,
            'average_hit_count' => $avgHitCount,
            'top_annotations' => $topAnnotations,
        ];
    }

    /**
     * 记录标注匹配
     */
    public function recordAnnotationHit(Annotation $annotation): void
    {
        $annotation->setHitCount($annotation->getHitCount() + 1);
        $annotation->setLastHitAt($this->clock->now());
        $this->persistAnnotation($annotation);
    }

    /**
     * 批量导入标注
     *
     * @param array<array{question: string, answer: string, userId?: string}> $annotationsData
     * @return array{success: int, failed: int, errors: array<string>}
     */
    public function batchImportAnnotations(array $annotationsData): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($annotationsData as $index => $data) {
            try {
                $this->createAnnotation(
                    $data['question'],
                    $data['answer'],
                    null,
                    null,
                    $data['userId'] ?? 'system'
                );
                ++$results['success'];
            } catch (\Exception $e) {
                ++$results['failed'];
                $results['errors'][] = sprintf('Row %d: %s', $index + 1, $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * 获取标注列表
     *
     * @param array<string, mixed> $params 查询参数
     * @return array<string, mixed> 标注列表和分页信息
     */
    public function getAnnotations(array $params = []): array
    {
        $setting = $this->getActiveSetting();

        try {
            return $this->getDifyAnnotations($setting, $params);
        } catch (\Exception $e) {
            throw new DifyRuntimeException(sprintf('Failed to get annotations: %s', $e->getMessage()));
        }
    }

    /**
     * 标注回复初始设置
     *
     * @return array<string, mixed> 初始化任务信息
     */
    public function initAnnotations(): array
    {
        $setting = $this->getActiveSetting();

        try {
            return $this->initDifyAnnotations($setting);
        } catch (\Exception $e) {
            throw new DifyRuntimeException(sprintf('Failed to init annotations: %s', $e->getMessage()));
        }
    }

    /**
     * 查询标注回复初始设置任务状态
     *
     * @return array<string, mixed> 任务状态信息
     */
    public function getAnnotationInitStatus(?string $jobId = null): array
    {
        $setting = $this->getActiveSetting();

        try {
            return $this->getDifyAnnotationInitStatus($setting, $jobId);
        } catch (\Exception $e) {
            throw new DifyRuntimeException(sprintf('Failed to get annotation init status: %s', $e->getMessage()));
        }
    }

    /**
     * 清理过期或无用的标注
     */
    public function cleanupAnnotations(\DateInterval $inactiveThreshold): int
    {
        $thresholdDate = $this->clock->now()->sub($inactiveThreshold);

        $qb = $this->annotationRepository->createQueryBuilder('a');
        $inactiveAnnotations = $qb
            ->where('a.enabled = false')
            ->orWhere('a.hitCount = 0 AND a.createdAt < :threshold')
            ->orWhere('a.lastHitAt < :threshold')
            ->setParameter('threshold', $thresholdDate)
            ->getQuery()
            ->getResult()
        ;

        $cleanupCount = 0;

        if (is_array($inactiveAnnotations)) {
            /** @var Annotation $annotation */
            foreach ($inactiveAnnotations as $annotation) {
                $this->deleteAnnotation($annotation);
                ++$cleanupCount;
            }
        }

        return $cleanupCount;
    }

    private function getActiveSetting(): DifySetting
    {
        $setting = $this->settingRepository->findActiveSetting();
        if (null === $setting) {
            throw new DifySettingNotFoundException();
        }

        return $setting;
    }

    private function buildAnnotation(
        string $question,
        string $answer,
        ?Message $message,
        ?Conversation $conversation,
        string $userId,
    ): Annotation {
        $annotation = new Annotation();
        $annotation->setQuestion($question);
        $annotation->setAnswer($answer);
        $annotation->setMessage($message);
        $annotation->setConversation($conversation);
        $annotation->setUserId($userId);
        $annotation->setEnabled(true);
        $annotation->setHitCount(0);
        $annotation->setCreateTime($this->clock->now());

        return $annotation;
    }

    private function persistAnnotation(Annotation $annotation): void
    {
        $this->entityManager->persist($annotation);
        $this->entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    private function createDifyAnnotation(DifySetting $setting, string $question, string $answer, ?string $conversationId): array
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/annotations';

        $payload = [
            'question' => $question,
            'answer' => $answer,
        ];

        if (null !== $conversationId) {
            $payload['conversation_id'] = $conversationId;
        }

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'timeout' => $setting->getTimeout(),
        ]);

        if (201 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Dify annotation creation failed: ' . $response->getContent(false));
        }

        /** @var array<string, mixed> */
        return $response->toArray();
    }

    private function updateDifyAnnotation(DifySetting $setting, string $annotationId, string $question, string $answer): void
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/annotations/' . $annotationId;

        $response = $this->httpClient->request('PUT', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'question' => $question,
                'answer' => $answer,
            ],
            'timeout' => $setting->getTimeout(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Failed to update Dify annotation: ' . $response->getContent(false));
        }
    }

    private function deleteDifyAnnotation(DifySetting $setting, string $annotationId): void
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/annotations/' . $annotationId;

        $response = $this->httpClient->request('DELETE', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
            ],
            'timeout' => $setting->getTimeout(),
        ]);

        if (204 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Failed to delete Dify annotation: ' . $response->getContent(false));
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function getDifyAnnotations(DifySetting $setting, array $params): array
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/annotations';

        $queryParams = [];
        if (isset($params['limit']) && is_int($params['limit'])) {
            $queryParams['limit'] = $params['limit'];
        }
        if (isset($params['offset']) && is_int($params['offset'])) {
            $queryParams['offset'] = $params['offset'];
        }
        if (isset($params['keyword']) && is_string($params['keyword'])) {
            $queryParams['keyword'] = $params['keyword'];
        }

        if ([] !== $queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }

        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
            ],
            'timeout' => $setting->getTimeout(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Failed to get Dify annotations: ' . $response->getContent(false));
        }

        /** @var array<string, mixed> */
        return $response->toArray();
    }

    /** @return array<string, mixed> */
    private function initDifyAnnotations(DifySetting $setting): array
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/annotations/init';

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
                'Content-Type' => 'application/json',
            ],
            'json' => [],
            'timeout' => $setting->getTimeout(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Failed to init Dify annotations: ' . $response->getContent(false));
        }

        /** @var array<string, mixed> */
        return $response->toArray();
    }

    /** @return array<string, mixed> */
    private function getDifyAnnotationInitStatus(DifySetting $setting, ?string $jobId = null): array
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/annotations/init/status';
        if (null !== $jobId) {
            $url .= '/' . $jobId;
        }

        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
            ],
            'timeout' => $setting->getTimeout(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Failed to get Dify annotation init status: ' . $response->getContent(false));
        }

        /** @var array<string, mixed> */
        return $response->toArray();
    }

    private function handleAnnotationFailure(Annotation $annotation, \Exception $e): void
    {
        // 可以在这里添加错误处理逻辑，比如记录到日志或发送通知
        error_log(sprintf('Annotation creation failed: %s', $e->getMessage()));
    }
}
