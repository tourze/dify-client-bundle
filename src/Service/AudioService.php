<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\DifyClientBundle\Entity\AudioTranscription;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Event\AudioTranscriptionFailedEvent;
use Tourze\DifyClientBundle\Event\AudioTranscriptionSuccessEvent;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;
use Tourze\DifyClientBundle\Exception\DifySettingNotFoundException;
use Tourze\DifyClientBundle\Repository\AudioTranscriptionRepository;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;

/**
 * 语音服务
 *
 * 提供语音转文字、文字转语音等音频处理功能
 * 对应 API: POST /audio/speech-to-text, POST /audio/text-to-speech
 */
readonly class AudioService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EventDispatcherInterface $eventDispatcher,
        private DifySettingRepository $settingRepository,
        private AudioTranscriptionRepository $transcriptionRepository,
        private ClockInterface $clock,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 语音转文字
     */
    public function speechToText(
        UploadedFile $audioFile,
        string $userId = 'system',
        ?string $language = null,
    ): AudioTranscription {
        $setting = $this->getActiveSetting();

        // 验证音频文件
        $this->validateAudioFile($audioFile);

        // 创建转录记录
        $transcription = $this->createTranscription($audioFile, $userId, $language);
        $this->persistTranscription($transcription);

        try {
            // 发送到 Dify 进行转录
            $response = $this->sendSpeechToTextRequest($setting, $audioFile, $userId, $language);

            // 更新转录结果
            $this->updateTranscriptionFromResponse($transcription, $response);
            $this->persistTranscription($transcription);

            // 派发成功事件
            $this->eventDispatcher->dispatch(new AudioTranscriptionSuccessEvent(
                $transcription,
                'speech_to_text',
                ['duration' => $transcription->getDuration(), 'language' => $transcription->getLanguage()]
            ));

            return $transcription;
        } catch (\Exception $e) {
            $this->handleTranscriptionFailure($transcription, $e);

            // 派发失败事件
            $this->eventDispatcher->dispatch(new AudioTranscriptionFailedEvent(
                $transcription,
                'speech_to_text',
                $e->getMessage(),
                $e,
                ['file_name' => $audioFile->getClientOriginalName()]
            ));

            throw $e;
        }
    }

    /**
     * 文字转语音
     *
     * @return array<string, mixed>
     */
    public function textToSpeech(
        string $text,
        string $userId = 'system',
        ?string $voice = null,
        ?string $format = 'mp3',
    ): array {
        $setting = $this->getActiveSetting();

        // 验证文本长度
        $this->validateTextForSpeech($text);

        try {
            $result = $this->sendTextToSpeechRequest($setting, $text, $userId, $voice, $format);

            // 创建临时转录实体用于事件（TTS不创建持久化记录）
            $tempTranscription = new AudioTranscription();
            $tempTranscription->setUserId($userId);
            $tempTranscription->setCreateTime($this->clock->now());

            // 派发文本转语音成功事件
            $this->eventDispatcher->dispatch(new AudioTranscriptionSuccessEvent(
                $tempTranscription,
                'text_to_speech',
                ['text_length' => mb_strlen($text), 'voice' => $voice, 'format' => $format]
            ));

            return $result;
        } catch (\Exception $e) {
            // 创建临时转录实体用于事件
            $tempTranscription = new AudioTranscription();
            $tempTranscription->setUserId($userId);
            $tempTranscription->setCreateTime($this->clock->now());

            // 派发文本转语音失败事件
            $this->eventDispatcher->dispatch(new AudioTranscriptionFailedEvent(
                $tempTranscription,
                'text_to_speech',
                $e->getMessage(),
                $e,
                ['text_length' => mb_strlen($text)]
            ));

            throw new DifyRuntimeException(sprintf('Text-to-speech conversion failed: %s', $e->getMessage()));
        }
    }

    /**
     * 文字转语音（别名方法）
     *
     * @param array<string, mixed> $params 请求参数
     * @return array<string, mixed> 转换结果
     */
    public function textToAudio(array $params): array
    {
        $text = $params['text'] ?? '';
        $userId = $params['user'] ?? 'system';
        $voice = $params['voice'] ?? null;
        $format = $params['format'] ?? 'mp3';

        if (!is_string($text) || '' === $text) {
            throw new DifyRuntimeException('Text parameter is required and must be a non-empty string');
        }
        if (!is_string($userId)) {
            throw new DifyRuntimeException('User parameter must be a string');
        }
        if (null !== $voice && !is_string($voice)) {
            throw new DifyRuntimeException('Voice parameter must be a string or null');
        }
        if (!is_string($format)) {
            throw new DifyRuntimeException('Format parameter must be a string');
        }

        return $this->textToSpeech($text, $userId, $voice, $format);
    }

    /**
     * 语音转文字（别名方法）
     *
     * @param array<string, mixed> $params 请求参数
     * @return array<string, mixed> 转换结果
     */
    public function audioToText(array $params): array
    {
        $file = $params['file'] ?? null;
        $userId = $params['user'] ?? 'system';
        $language = $params['language'] ?? null;

        if (!$file instanceof UploadedFile) {
            throw new DifyRuntimeException('File parameter is required and must be an UploadedFile instance');
        }
        if (!is_string($userId)) {
            throw new DifyRuntimeException('User parameter must be a string');
        }
        if (null !== $language && !is_string($language)) {
            throw new DifyRuntimeException('Language parameter must be a string or null');
        }

        $transcription = $this->speechToText($file, $userId, $language);

        // 转换为数组格式返回
        return [
            'id' => $transcription->getId(),
            'text' => $transcription->getText(),
            'duration' => $transcription->getDuration(),
            'language' => $transcription->getLanguage(),
            'confidence' => $transcription->getConfidence(),
            'created_at' => $transcription->getCreateTime()?->format('c'),
            'processed_at' => $transcription->getProcessedAt()?->format('c'),
        ];
    }

    /**
     * 获取用户的转录历史
     *
     * @return array<AudioTranscription>
     */
    public function getUserTranscriptions(string $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->transcriptionRepository->findBy(
            ['userId' => $userId],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * 根据转录ID查找转录记录
     */
    public function findTranscriptionById(string $id): ?AudioTranscription
    {
        return $this->transcriptionRepository->find($id);
    }

    /**
     * 删除转录记录
     */
    public function deleteTranscription(AudioTranscription $transcription): void
    {
        $this->entityManager->remove($transcription);
        $this->entityManager->flush();
    }

    /**
     * 获取转录统计信息
     *
     * @return array<string, mixed>
     */
    public function getTranscriptionStats(): array
    {
        $qb = $this->transcriptionRepository->createQueryBuilder('t');

        $totalTranscriptions = (int) $qb
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $successfulTranscriptions = (int) $qb
            ->select('COUNT(t.id)')
            ->where('t.text IS NOT NULL')
            ->andWhere('t.text != :empty')
            ->setParameter('empty', '')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $failedTranscriptions = $totalTranscriptions - $successfulTranscriptions;

        $avgDurationResult = $qb
            ->select('AVG(t.duration)')
            ->where('t.duration IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult()
        ;
        $avgDuration = null !== $avgDurationResult ? (float) $avgDurationResult : 0.0;

        $totalDurationResult = $qb
            ->select('SUM(t.duration)')
            ->where('t.duration IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult()
        ;
        $totalDuration = null !== $totalDurationResult ? (float) $totalDurationResult : 0.0;

        // 按语言统计
        $languageStats = $qb
            ->select('t.language, COUNT(t.id) as count')
            ->where('t.language IS NOT NULL')
            ->groupBy('t.language')
            ->getQuery()
            ->getArrayResult()
        ;

        // 最近7天的转录趋势
        $sevenDaysAgo = $this->clock->now()->modify('-7 days');
        $dailyStats = $qb
            ->select('DATE(t.createdAt) as date, COUNT(t.id) as count')
            ->where('t.createdAt >= :sevenDaysAgo')
            ->setParameter('sevenDaysAgo', $sevenDaysAgo)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        return [
            'total_transcriptions' => $totalTranscriptions,
            'successful_transcriptions' => $successfulTranscriptions,
            'failed_transcriptions' => $failedTranscriptions,
            'success_rate' => $totalTranscriptions > 0 ? round($successfulTranscriptions / $totalTranscriptions * 100, 2) : 0,
            'average_duration' => round($avgDuration, 2),
            'total_duration' => round($totalDuration, 2),
            'language_distribution' => $languageStats,
            'daily_stats_7_days' => $dailyStats,
        ];
    }

    /**
     * 搜索转录记录
     *
     * @return array<AudioTranscription>
     */
    public function searchTranscriptions(
        ?string $query = null,
        ?string $userId = null,
        ?string $language = null,
        ?\DateTimeInterface $dateFrom = null,
        ?\DateTimeInterface $dateTo = null,
        int $limit = 50,
        int $offset = 0,
    ): array {
        $qb = $this->transcriptionRepository->createQueryBuilder('t');

        if (null !== $query) {
            $qb->andWhere('t.text LIKE :query OR t.originalFilename LIKE :query')
                ->setParameter('query', '%' . $query . '%')
            ;
        }

        if (null !== $userId) {
            $qb->andWhere('t.userId = :userId')
                ->setParameter('userId', $userId)
            ;
        }

        if (null !== $language) {
            $qb->andWhere('t.language = :language')
                ->setParameter('language', $language)
            ;
        }

        if (null !== $dateFrom) {
            $qb->andWhere('t.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom)
            ;
        }

        if (null !== $dateTo) {
            $qb->andWhere('t.createdAt <= :dateTo')
                ->setParameter('dateTo', $dateTo)
            ;
        }

        /** @var array<AudioTranscription> */
        return $qb
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 清理过期的转录记录
     */
    public function cleanupOldTranscriptions(\DateInterval $expiry): int
    {
        $expiredDate = $this->clock->now()->sub($expiry);

        $qb = $this->transcriptionRepository->createQueryBuilder('t');
        /** @var array<AudioTranscription> $expiredTranscriptions */
        $expiredTranscriptions = $qb
            ->where('t.createdAt < :expiredDate')
            ->setParameter('expiredDate', $expiredDate)
            ->getQuery()
            ->getResult()
        ;

        $cleanupCount = 0;

        foreach ($expiredTranscriptions as $transcription) {
            $this->entityManager->remove($transcription);
            ++$cleanupCount;
        }

        if ($cleanupCount > 0) {
            $this->entityManager->flush();
        }

        return $cleanupCount;
    }

    /**
     * 批量转录音频文件
     *
     * @param array<UploadedFile> $audioFiles
     * @return array{success: int, failed: int, results: array<AudioTranscription>, errors: array<string>}
     */
    public function batchSpeechToText(array $audioFiles, string $userId = 'system', ?string $language = null): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'results' => [],
            'errors' => [],
        ];

        foreach ($audioFiles as $index => $audioFile) {
            try {
                $transcription = $this->speechToText($audioFile, $userId, $language);
                $results['results'][] = $transcription;
                ++$results['success'];
            } catch (\Exception $e) {
                ++$results['failed'];
                $results['errors'][] = sprintf('File %d (%s): %s', $index + 1, $audioFile->getClientOriginalName(), $e->getMessage());
            }
        }

        return $results;
    }

    private function getActiveSetting(): DifySetting
    {
        $setting = $this->settingRepository->findActiveSetting();
        if (null === $setting) {
            throw new DifySettingNotFoundException();
        }

        return $setting;
    }

    private function validateAudioFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new DifyRuntimeException('Invalid audio file upload');
        }

        // 检查文件大小限制（50MB）
        if ($file->getSize() > 50 * 1024 * 1024) {
            throw new DifyRuntimeException('Audio file size exceeds 50MB limit');
        }

        // 检查音频文件类型
        $allowedTypes = [
            'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav',
            'audio/m4a', 'audio/aac', 'audio/ogg', 'audio/webm',
            'audio/flac', 'audio/x-flac',
        ];

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $allowedTypes, true)) {
            throw new DifyRuntimeException('Unsupported audio format: ' . $mimeType);
        }
    }

    private function validateTextForSpeech(string $text): void
    {
        if ('' === trim($text)) {
            throw new DifyRuntimeException('Text cannot be empty');
        }

        // 检查文本长度限制（通常TTS服务有字符数限制）
        if (mb_strlen($text) > 5000) {
            throw new DifyRuntimeException('Text exceeds 5000 character limit');
        }
    }

    private function createTranscription(UploadedFile $audioFile, string $userId, ?string $language): AudioTranscription
    {
        $transcription = new AudioTranscription();
        $transcription->setOriginalFilename($audioFile->getClientOriginalName());
        $transcription->setMimeType($audioFile->getMimeType());
        $transcription->setFileSize($audioFile->getSize());
        $transcription->setUserId($userId);
        $transcription->setLanguage($language);
        $transcription->setCreateTime($this->clock->now());

        return $transcription;
    }

    private function persistTranscription(AudioTranscription $transcription): void
    {
        $this->entityManager->persist($transcription);
        $this->entityManager->flush();
    }

    /** @return array<string, mixed> */
    private function sendSpeechToTextRequest(
        DifySetting $setting,
        UploadedFile $audioFile,
        string $userId,
        ?string $language,
    ): array {
        $url = rtrim($setting->getBaseUrl(), '/') . '/audio/speech-to-text';

        $formData = [
            'file' => fopen($audioFile->getPathname(), 'r'),
            'user' => $userId,
        ];

        if (null !== $language) {
            $formData['language'] = $language;
        }

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
            ],
            'body' => $formData,
            'timeout' => max($setting->getTimeout(), 300), // 至少5分钟超时时间，音频处理可能较慢
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Dify speech-to-text request failed: ' . $response->getContent(false));
        }

        /** @var array<string, mixed> */
        return $response->toArray();
    }

    /** @return array<string, mixed> */
    private function sendTextToSpeechRequest(
        DifySetting $setting,
        string $text,
        string $userId,
        ?string $voice,
        ?string $format,
    ): array {
        $url = rtrim($setting->getBaseUrl(), '/') . '/audio/text-to-speech';

        $payload = [
            'text' => $text,
            'user' => $userId,
        ];

        if (null !== $voice) {
            $payload['voice'] = $voice;
        }

        if (null !== $format) {
            $payload['format'] = $format;
        }

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'timeout' => max($setting->getTimeout(), 120), // 至少2分钟超时时间
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Dify text-to-speech request failed: ' . $response->getContent(false));
        }

        /** @var array<string, mixed> */
        return $response->toArray();
    }

    /** @param array<string, mixed> $response */
    private function updateTranscriptionFromResponse(AudioTranscription $transcription, array $response): void
    {
        if (isset($response['text']) && is_string($response['text'])) {
            $transcription->setText($response['text']);
        }

        if (isset($response['duration']) && (is_int($response['duration']) || is_float($response['duration']))) {
            $transcription->setDuration((int) round((float) $response['duration']));
        }

        if (isset($response['language']) && is_string($response['language'])) {
            $transcription->setLanguage($response['language']);
        }

        if (isset($response['confidence']) && (is_int($response['confidence']) || is_float($response['confidence']))) {
            $transcription->setConfidence((float) $response['confidence']);
        }

        $transcription->setProcessedAt($this->clock->now());
    }

    private function handleTranscriptionFailure(AudioTranscription $transcription, \Exception $e): void
    {
        $transcription->setErrorMessage($e->getMessage());
        $this->persistTranscription($transcription);

        error_log(sprintf('Audio transcription failed: %s', $e->getMessage()));
    }
}
