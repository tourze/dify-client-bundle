<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\AudioTranscription;

class AudioTranscriptionFixtures extends Fixture
{
    public const AUDIO_TRANSCRIPTION_REFERENCE = 'audio-transcription-1';

    public function load(ObjectManager $manager): void
    {
        $transcription = new AudioTranscription();
        $transcription->setTaskId('task-12345');
        $transcription->setType('audio_to_text');
        $transcription->setText('这是一个语音转文字的测试结果。');
        $transcription->setAudioUrl('https://images.unsplash.com/audio/test.mp3');
        $transcription->setAudioFormat('mp3');
        $transcription->setDuration(30);
        $transcription->setFileSize(1024000);
        $transcription->setStatus('completed');
        $transcription->setUserId('test-user-123');
        $transcription->setOriginalFilename('test-audio.mp3');
        $transcription->setMimeType('audio/mpeg');
        $transcription->setMetadata([
            'language' => 'zh-CN',
            'confidence' => 0.95,
            'model' => 'whisper-1',
            'sample_rate' => 44100,
        ]);
        $transcription->setStartedAt(new \DateTimeImmutable('2024-01-01 10:00:00'));
        $transcription->setCompletedAt(new \DateTimeImmutable('2024-01-01 10:00:30'));

        $manager->persist($transcription);
        $manager->flush();

        $this->addReference(self::AUDIO_TRANSCRIPTION_REFERENCE, $transcription);
    }
}
