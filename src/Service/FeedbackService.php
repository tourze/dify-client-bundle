<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\MessageFeedback;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;
use Tourze\DifyClientBundle\Exception\DifySettingNotFoundException;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;
use Tourze\DifyClientBundle\Repository\MessageFeedbackRepository;

/**
 * 反馈服务
 *
 * 处理消息的点赞、点踩等用户反馈功能
 * 对应 API: POST /messages/{message_id}/feedbacks
 */
readonly class FeedbackService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private DifySettingRepository $settingRepository,
        private MessageFeedbackRepository $feedbackRepository,
        private ClockInterface $clock,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 提交消息反馈
     */
    public function submitFeedback(
        Message $message,
        string $rating,
        ?string $content = null,
        string $userId = 'system',
    ): MessageFeedback {
        $setting = $this->getActiveSetting();

        // 验证评分值
        $this->validateRating($rating);

        // 检查是否已存在反馈
        $existingFeedback = $this->findUserFeedback($message, $userId);
        if (null !== $existingFeedback) {
            return $this->updateFeedback($existingFeedback, $rating, $content);
        }

        // 创建新反馈
        $feedback = $this->createFeedback($message, $rating, $content, $userId);
        $this->persistFeedback($feedback);

        try {
            // 同步到 Dify
            $response = $this->submitDifyFeedback($setting, $message, $rating, $content, $userId);

            // 更新反馈ID
            if (isset($response['id']) && is_string($response['id'])) {
                $feedback->setFeedbackId($response['id']);
                $this->persistFeedback($feedback);
            }

            return $feedback;
        } catch (\Exception $e) {
            $this->handleFeedbackFailure($feedback, $e);
            throw $e;
        }
    }

    /**
     * 点赞消息
     */
    public function likeMessage(Message $message, string $userId = 'system'): MessageFeedback
    {
        return $this->submitFeedback($message, 'like', null, $userId);
    }

    /**
     * 点踩消息
     */
    public function dislikeMessage(Message $message, ?string $reason = null, string $userId = 'system'): MessageFeedback
    {
        return $this->submitFeedback($message, 'dislike', $reason, $userId);
    }

    /**
     * 更新反馈
     */
    public function updateFeedback(MessageFeedback $feedback, string $rating, ?string $content = null): MessageFeedback
    {
        $this->validateRating($rating);

        $originalRating = $feedback->getRating();
        $originalContent = $feedback->getContent();

        // 更新本地记录
        $feedback->setRating($rating);
        $feedback->setContent($content);
        $feedback->setUpdatedAt($this->clock->now());

        try {
            $setting = $this->getActiveSetting();

            // 同步更新到 Dify（如果有 feedbackId）
            $feedbackId = $feedback->getFeedbackId();
            if (null !== $feedbackId) {
                $this->updateDifyFeedback($setting, $feedbackId, $rating, $content);
            }

            $this->persistFeedback($feedback);

            return $feedback;
        } catch (\Exception $e) {
            // 回滚本地变更
            $feedback->setRating($originalRating);
            $feedback->setContent($originalContent);
            throw new DifyRuntimeException(sprintf('Failed to update feedback: %s', $e->getMessage()));
        }
    }

    /**
     * 删除反馈
     */
    public function deleteFeedback(MessageFeedback $feedback): void
    {
        $setting = $this->getActiveSetting();

        // 如果有 feedbackId，尝试删除 Dify 端的反馈
        $feedbackId = $feedback->getFeedbackId();
        if (null !== $feedbackId) {
            try {
                $this->deleteDifyFeedback($setting, $feedbackId);
            } catch (\Exception $e) {
                // 记录错误但不阻止本地删除
                error_log(sprintf('Failed to delete Dify feedback %s: %s', $feedbackId, $e->getMessage()));
            }
        }

        $this->entityManager->remove($feedback);
        $this->entityManager->flush();
    }

    /**
     * 获取用户对特定消息的反馈
     */
    public function findUserFeedback(Message $message, string $userId): ?MessageFeedback
    {
        return $this->feedbackRepository->findOneBy([
            'message' => $message,
            'userId' => $userId,
        ]);
    }

    /**
     * 获取消息的所有反馈
     *
     * @return array<MessageFeedback>
     */
    public function getMessageFeedbacks(Message $message): array
    {
        return $this->feedbackRepository->findBy(
            ['message' => $message],
            ['createdAt' => 'DESC']
        );
    }

    /**
     * 获取用户的所有反馈
     *
     * @return array<MessageFeedback>
     */
    public function getUserFeedbacks(string $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->feedbackRepository->findBy(
            ['userId' => $userId],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * 获取应用的所有反馈列表
     *
     * @return array<MessageFeedback>
     */
    public function getAllFeedbacks(int $limit = 50, int $offset = 0, ?string $rating = null): array
    {
        $criteria = [];
        if (null !== $rating && '' !== $rating) {
            $criteria['rating'] = $rating;
        }

        return $this->feedbackRepository->findBy(
            $criteria,
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * 获取反馈列表
     *
     * @param array<string, mixed> $params 查询参数
     * @return array<string, mixed> 反馈列表和分页信息
     */
    public function getFeedbacks(array $params = []): array
    {
        $setting = $this->getActiveSetting();

        try {
            return $this->getDifyFeedbacks($setting, $params);
        } catch (\Exception $e) {
            throw new DifyRuntimeException(sprintf('Failed to get feedbacks: %s', $e->getMessage()));
        }
    }

    /**
     * 从 Dify API 获取应用反馈列表
     *
     * @return array<string, mixed>
     */
    public function fetchAppFeedbacks(): array
    {
        $setting = $this->getActiveSetting();

        try {
            return $this->getDifyAppFeedbacks($setting);
        } catch (\Exception $e) {
            throw new DifyRuntimeException(sprintf('Failed to fetch app feedbacks: %s', $e->getMessage()));
        }
    }

    /**
     * 获取反馈统计信息
     *
     * @return array<string, mixed>
     */
    public function getFeedbackStats(): array
    {
        $qb = $this->feedbackRepository->createQueryBuilder('f');

        $totalFeedbacks = (int) $qb
            ->select('COUNT(f.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $likesCount = (int) $qb
            ->select('COUNT(f.id)')
            ->where('f.rating = :like')
            ->setParameter('like', 'like')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $dislikesCount = (int) $qb
            ->select('COUNT(f.id)')
            ->where('f.rating = :dislike')
            ->setParameter('dislike', 'dislike')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $ratingStats = $qb
            ->select('f.rating, COUNT(f.id) as count')
            ->groupBy('f.rating')
            ->getQuery()
            ->getArrayResult()
        ;

        // 按日期统计最近7天的反馈
        $sevenDaysAgo = $this->clock->now()->modify('-7 days');
        $dailyStats = $qb
            ->select('DATE(f.createdAt) as date, f.rating, COUNT(f.id) as count')
            ->where('f.createdAt >= :sevenDaysAgo')
            ->setParameter('sevenDaysAgo', $sevenDaysAgo)
            ->groupBy('date', 'f.rating')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        return [
            'total_feedbacks' => $totalFeedbacks,
            'likes_count' => $likesCount,
            'dislikes_count' => $dislikesCount,
            'like_ratio' => $totalFeedbacks > 0 ? round($likesCount / $totalFeedbacks * 100, 2) : 0,
            'rating_distribution' => $ratingStats,
            'daily_stats_7_days' => $dailyStats,
        ];
    }

    /**
     * 获取负面反馈及其原因分析
     *
     * @return array<array{content: string, count: int}>
     */
    public function getNegativeFeedbackAnalysis(): array
    {
        $qb = $this->feedbackRepository->createQueryBuilder('f');

        $negativeFeedbacks = $qb
            ->select('f.content')
            ->where('f.rating = :dislike')
            ->andWhere('f.content IS NOT NULL')
            ->andWhere('f.content != :empty')
            ->setParameter('dislike', 'dislike')
            ->setParameter('empty', '')
            ->getQuery()
            ->getArrayResult()
        ;

        // 简单的关键词统计
        $reasonCounts = [];
        foreach ($negativeFeedbacks as $feedback) {
            if (!is_array($feedback) || !isset($feedback['content']) || !is_string($feedback['content'])) {
                continue;
            }
            $content = strtolower(trim($feedback['content']));
            if ('' !== $content) {
                $reasonCounts[$content] = ($reasonCounts[$content] ?? 0) + 1;
            }
        }

        // 按频次排序
        arsort($reasonCounts);

        return array_map(
            fn ($content, $count) => ['content' => $content, 'count' => $count],
            array_keys($reasonCounts),
            array_values($reasonCounts)
        );
    }

    /**
     * 清理过期的反馈记录
     */
    public function cleanupOldFeedbacks(\DateInterval $expiry): int
    {
        $expiredDate = $this->clock->now()->sub($expiry);

        $qb = $this->feedbackRepository->createQueryBuilder('f');
        $expiredFeedbacks = $qb
            ->where('f.createdAt < :expiredDate')
            ->setParameter('expiredDate', $expiredDate)
            ->getQuery()
            ->getResult()
        ;

        $cleanupCount = 0;
        if (is_iterable($expiredFeedbacks)) {
            foreach ($expiredFeedbacks as $feedback) {
                if ($feedback instanceof MessageFeedback) {
                    $this->deleteFeedback($feedback);
                    ++$cleanupCount;
                }
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

    private function validateRating(string $rating): void
    {
        $allowedRatings = ['like', 'dislike'];
        if (!in_array($rating, $allowedRatings, true)) {
            throw new DifyRuntimeException(sprintf('Invalid rating "%s". Allowed values: %s', $rating, implode(', ', $allowedRatings)));
        }
    }

    public function createFeedback(Message $message, string $rating, ?string $content, string $userId): MessageFeedback
    {
        $feedback = new MessageFeedback();
        $feedback->setMessage($message);
        $feedback->setRating($rating);
        $feedback->setContent($content);
        $feedback->setUserId($userId);
        $feedback->setCreateTime($this->clock->now());

        return $feedback;
    }

    private function persistFeedback(MessageFeedback $feedback): void
    {
        $this->entityManager->persist($feedback);
        $this->entityManager->flush();
    }

    /** @return array<string, mixed> */
    private function submitDifyFeedback(
        DifySetting $setting,
        Message $message,
        string $rating,
        ?string $content,
        string $userId,
    ): array {
        // 这里需要消息的 messageId，通常存储在 message 的 metadata 中
        $messageMetadata = $message->getMetadata() ?? [];
        $difyMessageId = $messageMetadata['dify_message_id'] ?? null;

        if (null === $difyMessageId || !is_string($difyMessageId) || '' === $difyMessageId) {
            throw new DifyRuntimeException('Message does not have Dify message ID');
        }

        $url = rtrim($setting->getBaseUrl(), '/') . '/messages/' . $difyMessageId . '/feedbacks';

        $payload = [
            'rating' => $rating,
            'user' => $userId,
        ];

        if (null !== $content && '' !== $content) {
            $payload['content'] = $content;
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
            throw new DifyRuntimeException('Dify feedback submission failed: ' . $response->getContent(false));
        }

        /** @var array<string, mixed> */
        return $response->toArray();
    }

    private function updateDifyFeedback(DifySetting $setting, string $feedbackId, string $rating, ?string $content): void
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/feedbacks/' . $feedbackId;

        $payload = ['rating' => $rating];
        if (null !== $content && '' !== $content) {
            $payload['content'] = $content;
        }

        $response = $this->httpClient->request('PUT', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'timeout' => $setting->getTimeout(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Failed to update Dify feedback: ' . $response->getContent(false));
        }
    }

    private function deleteDifyFeedback(DifySetting $setting, string $feedbackId): void
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/feedbacks/' . $feedbackId;

        $response = $this->httpClient->request('DELETE', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
            ],
            'timeout' => $setting->getTimeout(),
        ]);

        if (204 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Failed to delete Dify feedback: ' . $response->getContent(false));
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function getDifyFeedbacks(DifySetting $setting, array $params): array
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/feedbacks';

        $queryParams = [];
        if (isset($params['limit']) && is_int($params['limit'])) {
            $queryParams['limit'] = $params['limit'];
        }
        if (isset($params['offset']) && is_int($params['offset'])) {
            $queryParams['offset'] = $params['offset'];
        }
        if (isset($params['rating']) && is_string($params['rating'])) {
            $queryParams['rating'] = $params['rating'];
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
            throw new DifyRuntimeException('Failed to get Dify feedbacks: ' . $response->getContent(false));
        }

        /** @var array<string, mixed> */
        return $response->toArray();
    }

    /** @return array<string, mixed> */
    private function getDifyAppFeedbacks(DifySetting $setting): array
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/feedbacks';

        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
            ],
            'timeout' => $setting->getTimeout(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Failed to fetch Dify app feedbacks: ' . $response->getContent(false));
        }

        /** @var array<string, mixed> */
        return $response->toArray();
    }

    private function handleFeedbackFailure(MessageFeedback $feedback, \Exception $e): void
    {
        // 可以在这里添加错误处理逻辑
        error_log(sprintf('Feedback submission failed: %s', $e->getMessage()));
    }
}
