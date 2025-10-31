<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\AudioTranscription;
use Tourze\DifyClientBundle\Repository\AudioTranscriptionRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AudioTranscriptionRepository::class)]
#[RunTestsInSeparateProcesses]
final class AudioTranscriptionRepositoryTest extends AbstractRepositoryTestCase
{
    private AudioTranscriptionRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(AudioTranscriptionRepository::class);
    }

    protected function getRepository(): AudioTranscriptionRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): AudioTranscription
    {
        $audioTranscription = new AudioTranscription();
        $audioTranscription->setTaskId('test-task-' . uniqid());
        $audioTranscription->setType('audio_to_text');
        $audioTranscription->setText('Test transcription');
        $audioTranscription->setStatus('pending');
        $audioTranscription->setUserId('user-' . uniqid());

        return $audioTranscription;
    }

    public function testFindByTaskIdShouldReturnCorrectAudioTranscription(): void
    {
        // Arrange: 创建并持久化语音转录记录
        $taskId = 'test-task-id-' . uniqid();
        $audioTranscription = new AudioTranscription();
        $audioTranscription->setTaskId($taskId);
        $audioTranscription->setType('text_to_audio');
        $audioTranscription->setText('Hello, world!');
        $audioTranscription->setStatus('completed');
        $audioTranscription->setUserId('user-123');
        $audioTranscription->setAudioUrl('https://example.com/audio.mp3');
        $audioTranscription->setAudioFormat('mp3');
        $audioTranscription->setDuration(120);
        $this->persistAndFlush($audioTranscription);

        // Act: 根据任务ID查找
        $foundTranscription = $this->repository->findByTaskId($taskId);

        // Assert: 验证找到正确的转录记录
        $this->assertNotNull($foundTranscription);
        $this->assertSame($taskId, $foundTranscription->getTaskId());
        $this->assertSame('text_to_audio', $foundTranscription->getType());
        $this->assertSame('Hello, world!', $foundTranscription->getText());
        $this->assertSame('completed', $foundTranscription->getStatus());
        $this->assertSame('user-123', $foundTranscription->getUserId());
    }

    public function testFindByTaskIdWithNonExistentTaskIdShouldReturnNull(): void
    {
        // Act: 查找不存在的任务ID
        $foundTranscription = $this->repository->findByTaskId('non-existent-task-id');

        // Assert: 应该返回null
        $this->assertNull($foundTranscription);
    }

    public function testFindByStatusShouldReturnCorrectTranscriptions(): void
    {
        // Arrange: 清理现有数据并创建不同状态的转录记录
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM dify_audio_transcription');
        self::getEntityManager()->clear();

        $completedTranscription1 = new AudioTranscription();
        $completedTranscription1->setTaskId('completed-1');
        $completedTranscription1->setType('audio_to_text');
        $completedTranscription1->setText('Completed text 1');
        $completedTranscription1->setStatus('completed');

        $completedTranscription2 = new AudioTranscription();
        $completedTranscription2->setTaskId('completed-2');
        $completedTranscription2->setType('audio_to_text');
        $completedTranscription2->setText('Completed text 2');
        $completedTranscription2->setStatus('completed');

        $pendingTranscription = new AudioTranscription();
        $pendingTranscription->setTaskId('pending-1');
        $pendingTranscription->setType('text_to_audio');
        $pendingTranscription->setText('Pending text');
        $pendingTranscription->setStatus('pending');

        $failedTranscription = new AudioTranscription();
        $failedTranscription->setTaskId('failed-1');
        $failedTranscription->setType('audio_to_text');
        $failedTranscription->setText('Failed text');
        $failedTranscription->setStatus('failed');

        // 设置时间确保排序测试
        $now = new \DateTimeImmutable();
        $completedTranscription1->setCreateTime($now->modify('-2 hours'));
        $completedTranscription2->setCreateTime($now->modify('-1 hour'));
        $pendingTranscription->setCreateTime($now);
        $failedTranscription->setCreateTime($now->modify('+1 hour'));

        $this->persistAndFlush($completedTranscription1);
        $this->persistAndFlush($completedTranscription2);
        $this->persistAndFlush($pendingTranscription);
        $this->persistAndFlush($failedTranscription);

        // Act: 查找已完成的转录记录
        $completedTranscriptions = $this->repository->findByStatus('completed');

        // Assert: 只返回已完成状态的转录记录，按创建时间升序排列
        $this->assertCount(2, $completedTranscriptions);

        $taskIds = array_map(fn ($t) => $t->getTaskId(), $completedTranscriptions);
        $this->assertContains('completed-1', $taskIds);
        $this->assertContains('completed-2', $taskIds);
        $this->assertNotContains('pending-1', $taskIds);
        $this->assertNotContains('failed-1', $taskIds);

        // 验证按创建时间升序排列
        $this->assertSame('completed-1', $completedTranscriptions[0]->getTaskId()); // 较早创建的在前
        $this->assertSame('completed-2', $completedTranscriptions[1]->getTaskId());
    }

    public function testFindByStatusWithNonExistentStatusShouldReturnEmptyArray(): void
    {
        // Arrange: 创建一些转录记录但状态不匹配
        $audioTranscription = new AudioTranscription();
        $audioTranscription->setTaskId('test-task');
        $audioTranscription->setType('audio_to_text');
        $audioTranscription->setText('Test text');
        $audioTranscription->setStatus('completed');
        $this->persistAndFlush($audioTranscription);

        // Act: 查找不存在的状态
        $transcriptions = $this->repository->findByStatus('unknown_status');

        // Assert: 返回空数组
        $this->assertEmpty($transcriptions);
    }

    public function testFindPendingTasksShouldReturnOnlyPendingTranscriptions(): void
    {
        // Arrange: 清理现有数据并创建不同状态的转录记录
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM dify_audio_transcription');
        self::getEntityManager()->clear();

        $pendingTranscription1 = new AudioTranscription();
        $pendingTranscription1->setTaskId('pending-task-1');
        $pendingTranscription1->setType('audio_to_text');
        $pendingTranscription1->setText('Pending text 1');
        $pendingTranscription1->setStatus('pending');

        $pendingTranscription2 = new AudioTranscription();
        $pendingTranscription2->setTaskId('pending-task-2');
        $pendingTranscription2->setType('text_to_audio');
        $pendingTranscription2->setText('Pending text 2');
        $pendingTranscription2->setStatus('pending');

        $processingTranscription = new AudioTranscription();
        $processingTranscription->setTaskId('processing-task');
        $processingTranscription->setType('audio_to_text');
        $processingTranscription->setText('Processing text');
        $processingTranscription->setStatus('processing');

        $completedTranscription = new AudioTranscription();
        $completedTranscription->setTaskId('completed-task');
        $completedTranscription->setType('audio_to_text');
        $completedTranscription->setText('Completed text');
        $completedTranscription->setStatus('completed');

        $this->persistAndFlush($pendingTranscription1);
        $this->persistAndFlush($pendingTranscription2);
        $this->persistAndFlush($processingTranscription);
        $this->persistAndFlush($completedTranscription);

        // Act: 查找待处理的任务
        $pendingTasks = $this->repository->findPendingTasks();

        // Assert: 只返回待处理状态的转录记录
        $this->assertCount(2, $pendingTasks);

        $taskIds = array_map(fn ($t) => $t->getTaskId(), $pendingTasks);
        $this->assertContains('pending-task-1', $taskIds);
        $this->assertContains('pending-task-2', $taskIds);
        $this->assertNotContains('processing-task', $taskIds);
        $this->assertNotContains('completed-task', $taskIds);
    }

    public function testFindPendingTasksWithNoPendingTasksShouldReturnEmptyArray(): void
    {
        // Arrange: 清理数据并创建非待处理的任务
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM dify_audio_transcription');
        self::getEntityManager()->clear();

        $completedTranscription = new AudioTranscription();
        $completedTranscription->setTaskId('completed-only');
        $completedTranscription->setType('audio_to_text');
        $completedTranscription->setText('Completed text');
        $completedTranscription->setStatus('completed');
        $this->persistAndFlush($completedTranscription);

        // Act: 查找待处理的任务
        $pendingTasks = $this->repository->findPendingTasks();

        // Assert: 返回空数组
        $this->assertEmpty($pendingTasks);
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新转录记录（未持久化）
        $audioTranscription = new AudioTranscription();
        $audioTranscription->setTaskId('test-save-new');
        $audioTranscription->setType('audio_to_text');
        $audioTranscription->setText('Save test text');
        $audioTranscription->setStatus('pending');
        $audioTranscription->setUserId('user-save-test');

        // Act: 保存转录记录
        $this->repository->save($audioTranscription);

        // Assert: 验证转录记录已持久化
        $this->assertNotNull($audioTranscription->getId());
        $this->assertEntityPersisted($audioTranscription);
    }

    public function testSaveShouldUpdateExistingEntity(): void
    {
        // Arrange: 创建并持久化转录记录
        $audioTranscription = new AudioTranscription();
        $audioTranscription->setTaskId('test-update-save');
        $audioTranscription->setType('audio_to_text');
        $audioTranscription->setText('Original text');
        $audioTranscription->setStatus('pending');
        $this->persistAndFlush($audioTranscription);

        // Act: 修改并保存
        $audioTranscription->setText('Updated text');
        $audioTranscription->setStatus('completed');
        $this->repository->save($audioTranscription);

        // Assert: 验证更新已持久化
        self::getEntityManager()->refresh($audioTranscription);
        $this->assertSame('Updated text', $audioTranscription->getText());
        $this->assertSame('completed', $audioTranscription->getStatus());
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化转录记录
        $audioTranscription = new AudioTranscription();
        $audioTranscription->setTaskId('test-remove');
        $audioTranscription->setType('audio_to_text');
        $audioTranscription->setText('Remove test text');
        $audioTranscription->setStatus('pending');
        $this->persistAndFlush($audioTranscription);

        $transcriptionId = $audioTranscription->getId();

        // Act: 删除转录记录
        $this->repository->remove($audioTranscription);

        // Assert: 验证转录记录已删除
        $this->assertEntityNotExists(AudioTranscription::class, $transcriptionId);
    }

    public function testRemoveWithoutFlushShouldNotDeleteImmediately(): void
    {
        // Arrange: 创建并持久化转录记录
        $audioTranscription = new AudioTranscription();
        $audioTranscription->setTaskId('test-remove-no-flush');
        $audioTranscription->setType('audio_to_text');
        $audioTranscription->setText('Remove no flush test');
        $audioTranscription->setStatus('pending');
        $this->persistAndFlush($audioTranscription);

        $transcriptionId = $audioTranscription->getId();

        // Act: 删除转录记录但不刷新
        $this->repository->remove($audioTranscription, false);

        // Assert: 验证转录记录仍然存在（在数据库中）
        $em = self::getEntityManager();
        $qb = $this->repository->createQueryBuilder('at');
        $qb->select('COUNT(at.id)')
            ->where('at.id = :id')
            ->setParameter('id', $transcriptionId)
        ;

        $count = (int) $qb->getQuery()->getSingleScalarResult();
        $this->assertSame(1, $count, '删除未flush时，实体应该仍在数据库中');

        // 手动刷新后应该被删除
        $em->flush();

        $count = (int) $qb->getQuery()->getSingleScalarResult();
        $this->assertSame(0, $count, 'flush后，实体应该被删除');
    }

    public function testGetEntityManagerShouldReturnEntityManagerInterface(): void
    {
        // Act: 获取实体管理器
        $em = self::getEntityManager();

        // Assert: 验证返回类型
        $this->assertInstanceOf(EntityManagerInterface::class, $em);
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        // Assert: 验证继承关系
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryHasCorrectEntityClass(): void
    {
        // Assert: 验证实体类
        $this->assertSame(AudioTranscription::class, $this->repository->getClassName());
    }

    public function testEntityHelperMethodsShouldWorkCorrectly(): void
    {
        // Arrange: 创建转录记录并测试辅助方法
        $textToAudio = new AudioTranscription();
        $textToAudio->setTaskId('test-text-to-audio');
        $textToAudio->setType('text_to_audio');
        $textToAudio->setText('Test text to audio');
        $textToAudio->setStatus('completed');

        $audioToText = new AudioTranscription();
        $audioToText->setTaskId('test-audio-to-text');
        $audioToText->setType('audio_to_text');
        $audioToText->setText('Test audio to text');
        $audioToText->setStatus('failed');

        // Act & Assert: 测试类型判断方法
        $this->assertTrue($textToAudio->isTextToAudio());
        $this->assertFalse($textToAudio->isAudioToText());
        $this->assertTrue($textToAudio->isCompleted());
        $this->assertFalse($textToAudio->isFailed());

        $this->assertFalse($audioToText->isTextToAudio());
        $this->assertTrue($audioToText->isAudioToText());
        $this->assertFalse($audioToText->isCompleted());
        $this->assertTrue($audioToText->isFailed());
    }

    public function testEntityStringRepresentationShouldBeCorrect(): void
    {
        // Arrange: 创建不同类型的转录记录
        $textToAudio = new AudioTranscription();
        $textToAudio->setTaskId('test-text-to-audio-string');
        $textToAudio->setType('text_to_audio');
        $textToAudio->setStatus('completed');

        $audioToText = new AudioTranscription();
        $audioToText->setTaskId('test-audio-to-text-string');
        $audioToText->setType('audio_to_text');
        $audioToText->setStatus('pending');

        // Act & Assert: 测试字符串表示
        $this->assertSame('文字转语音任务 (completed)', (string) $textToAudio);
        $this->assertSame('语音转文字任务 (pending)', (string) $audioToText);
    }

    public function testFlush(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
