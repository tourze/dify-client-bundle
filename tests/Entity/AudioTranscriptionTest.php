<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\DifyClientBundle\Entity\AudioTranscription;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AudioTranscription::class)]
final class AudioTranscriptionTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): AudioTranscription
    {
        return new AudioTranscription();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'taskId' => ['taskId', 'task-12345'];
        yield 'type' => ['type', 'text_to_audio'];
        yield 'text' => ['text', '这是一段测试文本'];
        yield 'audioUrl' => ['audioUrl', 'https://example.com/audio.mp3'];
        yield 'audioFormat' => ['audioFormat', 'mp3'];
        yield 'duration' => ['duration', 120];
        yield 'fileSize' => ['fileSize', 2048000];
        yield 'status' => ['status', 'completed'];
        yield 'userId' => ['userId', 'user-123'];
        yield 'errorMessage' => ['errorMessage', '转录失败：格式不支持'];
        yield 'originalFilename' => ['originalFilename', 'recording.wav'];
        yield 'mimeType' => ['mimeType', 'audio/wav'];
        yield 'language' => ['language', 'zh-CN'];
        yield 'confidence' => ['confidence', 0.95];
        yield 'startedAt' => ['startedAt', new \DateTimeImmutable('2024-01-01 10:00:00')];
        yield 'completedAt' => ['completedAt', new \DateTimeImmutable('2024-01-01 10:02:00')];
        yield 'processedAt' => ['processedAt', new \DateTimeImmutable('2024-01-01 10:02:30')];
    }

    public function testCreateAudioTranscriptionWithDefaultValuesShouldSucceed(): void
    {
        $transcription = $this->createEntity();

        $this->assertNull($transcription->getId());
        $this->assertNull($transcription->getMessage());
        $this->assertNull($transcription->getConversation());
        $this->assertNull($transcription->getText());
        $this->assertNull($transcription->getAudioUrl());
        $this->assertNull($transcription->getAudioFormat());
        $this->assertNull($transcription->getDuration());
        $this->assertNull($transcription->getFileSize());
        $this->assertEquals('pending', $transcription->getStatus());
        $this->assertNull($transcription->getUserId());
        $this->assertNull($transcription->getErrorMessage());
        $this->assertNull($transcription->getMetadata());
        $this->assertNull($transcription->getStartedAt());
        $this->assertNull($transcription->getCompletedAt());
        $this->assertNull($transcription->getOriginalFilename());
        $this->assertNull($transcription->getMimeType());
        $this->assertNull($transcription->getLanguage());
        $this->assertNull($transcription->getProcessedAt());
        $this->assertNull($transcription->getConfidence());
    }

    public function testSetTaskIdShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $taskId = 'task-12345';

        $transcription->setTaskId($taskId);

        $this->assertEquals($taskId, $transcription->getTaskId());
    }

    public function testSetTypeShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $type = 'audio_to_text';

        $transcription->setType($type);

        $this->assertEquals($type, $transcription->getType());
    }

    #[TestWith(['text_to_audio'], 'text to audio')]
    #[TestWith(['audio_to_text'], 'audio to text')]
    public function testSetTypeWithValidValuesShouldSucceed(string $type): void
    {
        $transcription = $this->createEntity();

        $transcription->setType($type);

        $this->assertEquals($type, $transcription->getType());
    }

    public function testSetTextShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $text = '这是一段测试语音转文字的结果。';

        $transcription->setText($text);

        $this->assertEquals($text, $transcription->getText());
    }

    public function testSetTextWithNullShouldAcceptNull(): void
    {
        $transcription = $this->createEntity();
        $transcription->setText('原始文本');

        $transcription->setText(null);

        $this->assertNull($transcription->getText());
    }

    public function testSetMessageShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $message = $this->createMock(Message::class);

        $transcription->setMessage($message);

        $this->assertSame($message, $transcription->getMessage());
    }

    public function testSetConversationShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $conversation = $this->createMock(Conversation::class);

        $transcription->setConversation($conversation);

        $this->assertSame($conversation, $transcription->getConversation());
    }

    public function testSetAudioUrlShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $audioUrl = 'https://example.com/audio/test.mp3';

        $transcription->setAudioUrl($audioUrl);

        $this->assertEquals($audioUrl, $transcription->getAudioUrl());
    }

    public function testSetAudioFormatShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $audioFormat = 'wav';

        $transcription->setAudioFormat($audioFormat);

        $this->assertEquals($audioFormat, $transcription->getAudioFormat());
    }

    public function testSetDurationShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $duration = 180;

        $transcription->setDuration($duration);

        $this->assertEquals($duration, $transcription->getDuration());
    }

    public function testSetFileSizeShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $fileSize = 1024000;

        $transcription->setFileSize($fileSize);

        $this->assertEquals($fileSize, $transcription->getFileSize());
    }

    public function testSetStatusShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $status = 'processing';

        $transcription->setStatus($status);

        $this->assertEquals($status, $transcription->getStatus());
    }

    #[TestWith(['pending'], 'pending')]
    #[TestWith(['processing'], 'processing')]
    #[TestWith(['completed'], 'completed')]
    #[TestWith(['failed'], 'failed')]
    public function testSetStatusWithValidValuesShouldSucceed(string $status): void
    {
        $transcription = $this->createEntity();

        $transcription->setStatus($status);

        $this->assertEquals($status, $transcription->getStatus());
    }

    public function testSetUserIdShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $userId = 'user-123';

        $transcription->setUserId($userId);

        $this->assertEquals($userId, $transcription->getUserId());
    }

    public function testSetErrorMessageShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $errorMessage = '音频格式不支持';

        $transcription->setErrorMessage($errorMessage);

        $this->assertEquals($errorMessage, $transcription->getErrorMessage());
    }

    public function testSetMetadataShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $metadata = [
            'language' => 'en-US',
            'confidence' => 0.92,
            'model' => 'whisper-large',
        ];

        $transcription->setMetadata($metadata);

        $this->assertEquals($metadata, $transcription->getMetadata());
    }

    public function testSetStartedAtShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $startedAt = new \DateTimeImmutable('2024-01-01 10:00:00');

        $transcription->setStartedAt($startedAt);

        $this->assertEquals($startedAt, $transcription->getStartedAt());
    }

    public function testSetCompletedAtShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $completedAt = new \DateTimeImmutable('2024-01-01 10:02:30');

        $transcription->setCompletedAt($completedAt);

        $this->assertEquals($completedAt, $transcription->getCompletedAt());
    }

    public function testSetOriginalFilenameShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $filename = 'recording.wav';

        $transcription->setOriginalFilename($filename);

        $this->assertEquals($filename, $transcription->getOriginalFilename());
    }

    public function testSetMimeTypeShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $mimeType = 'audio/wav';

        $transcription->setMimeType($mimeType);

        $this->assertEquals($mimeType, $transcription->getMimeType());
    }

    public function testSetLanguageShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $language = 'en-US';

        $transcription->setLanguage($language);

        $this->assertEquals($language, $transcription->getLanguage());
    }

    public function testSetProcessedAtShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $processedAt = new \DateTimeImmutable('2024-01-01 10:02:30');

        $transcription->setProcessedAt($processedAt);

        $this->assertEquals($processedAt, $transcription->getProcessedAt());
    }

    public function testSetConfidenceShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $confidence = 0.87;

        $transcription->setConfidence($confidence);

        $this->assertEquals($confidence, $transcription->getConfidence());
    }

    public function testIsTextToAudioShouldReturnTrue(): void
    {
        $transcription = $this->createEntity();
        $transcription->setType('text_to_audio');

        $this->assertTrue($transcription->isTextToAudio());
        $this->assertFalse($transcription->isAudioToText());
    }

    public function testIsAudioToTextShouldReturnTrue(): void
    {
        $transcription = $this->createEntity();
        $transcription->setType('audio_to_text');

        $this->assertTrue($transcription->isAudioToText());
        $this->assertFalse($transcription->isTextToAudio());
    }

    public function testIsCompletedShouldReturnTrue(): void
    {
        $transcription = $this->createEntity();
        $transcription->setStatus('completed');

        $this->assertTrue($transcription->isCompleted());
        $this->assertFalse($transcription->isFailed());
    }

    public function testIsFailedShouldReturnTrue(): void
    {
        $transcription = $this->createEntity();
        $transcription->setStatus('failed');

        $this->assertTrue($transcription->isFailed());
        $this->assertFalse($transcription->isCompleted());
    }

    public function testSetCreateTimeShouldUpdateValue(): void
    {
        $transcription = $this->createEntity();
        $createTime = new \DateTimeImmutable('2024-01-01 10:00:00');

        $transcription->setCreateTime($createTime);

        $this->assertEquals($createTime, $transcription->getCreateTime());
    }

    public function testToStringWithTextToAudioShouldReturnCorrectLabel(): void
    {
        $transcription = $this->createEntity();
        $transcription->setType('text_to_audio');
        $transcription->setStatus('completed');

        $result = (string) $transcription;

        $this->assertEquals('文字转语音任务 (completed)', $result);
    }

    public function testToStringWithAudioToTextShouldReturnCorrectLabel(): void
    {
        $transcription = $this->createEntity();
        $transcription->setType('audio_to_text');
        $transcription->setStatus('processing');

        $result = (string) $transcription;

        $this->assertEquals('语音转文字任务 (processing)', $result);
    }

    public function testTranscriptionShouldAcceptLongText(): void
    {
        $transcription = $this->createEntity();
        $longText = str_repeat('这是一段很长的转录文本内容。', 500);

        $transcription->setText($longText);

        $this->assertEquals($longText, $transcription->getText());
    }

    public function testTranscriptionShouldAcceptComplexMetadata(): void
    {
        $transcription = $this->createEntity();
        $complexMetadata = [
            'language' => 'zh-CN',
            'confidence' => 0.95,
            'model' => 'whisper-1',
            'sample_rate' => 44100,
            'channels' => 2,
            'bitrate' => 320,
            'processing_time' => 5.2,
            'segments' => [
                ['start' => 0.0, 'end' => 5.5, 'text' => '第一段文字'],
                ['start' => 5.5, 'end' => 10.2, 'text' => '第二段文字'],
            ],
        ];

        $transcription->setMetadata($complexMetadata);

        $this->assertEquals($complexMetadata, $transcription->getMetadata());
    }
}
