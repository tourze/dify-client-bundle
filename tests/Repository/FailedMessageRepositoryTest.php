<?php

namespace Tourze\DifyClientBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\FailedMessage;
use Tourze\DifyClientBundle\Repository\FailedMessageRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(FailedMessageRepository::class)]
#[RunTestsInSeparateProcesses]
final class FailedMessageRepositoryTest extends AbstractRepositoryTestCase
{
    private FailedMessageRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(FailedMessageRepository::class);
    }

    protected function getRepository(): FailedMessageRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): FailedMessage
    {
        $failedMessage = new FailedMessage();
        $failedMessage->setError('Test error ' . uniqid());
        $failedMessage->setAttempts(1);
        $failedMessage->setFailedAt(new \DateTimeImmutable());
        $failedMessage->setRetried(false);

        return $failedMessage;
    }

    public function testSaveShouldPersistEntity(): void
    {
        // Arrange: 创建失败消息
        $failedMessage = new FailedMessage();
        $failedMessage->setError('Test error');
        $failedMessage->setAttempts(1);
        $failedMessage->setFailedAt(new \DateTimeImmutable());
        $failedMessage->setRetried(false);

        // Act: 保存
        $this->repository->save($failedMessage);

        // Assert: 验证已持久化
        $this->assertNotNull($failedMessage->getId());
        $this->assertEntityPersisted($failedMessage);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化失败消息
        $failedMessage = new FailedMessage();
        $failedMessage->setError('To remove');
        $failedMessage->setAttempts(1);
        $failedMessage->setFailedAt(new \DateTimeImmutable());
        $failedMessage->setRetried(false);

        $this->persistAndFlush($failedMessage);
        $messageId = $failedMessage->getId();

        // Act: 删除
        $this->repository->remove($failedMessage);

        // Assert: 验证已删除
        $this->assertEntityNotExists(FailedMessage::class, $messageId);
    }

    public function testFindUnretriedMessagesShouldReturnCorrectMessages(): void
    {
        // Arrange: 清理所有现有数据
        $em = self::getEntityManager();
        $em->getConnection()->executeStatement('DELETE FROM dify_failed_message');
        $em->clear();

        // 创建已重试和未重试的消息
        $retriedMessage = new FailedMessage();
        $retriedMessage->setError('Retried message');
        $retriedMessage->setAttempts(2);
        $retriedMessage->setFailedAt(new \DateTimeImmutable());
        $retriedMessage->setRetried(true);
        $this->persistAndFlush($retriedMessage);

        $unretriedMessage1 = new FailedMessage();
        $unretriedMessage1->setError('Unretried message 1');
        $unretriedMessage1->setAttempts(1);
        $unretriedMessage1->setFailedAt(new \DateTimeImmutable('-2 hours'));
        $unretriedMessage1->setRetried(false);
        $this->persistAndFlush($unretriedMessage1);

        $unretriedMessage2 = new FailedMessage();
        $unretriedMessage2->setError('Unretried message 2');
        $unretriedMessage2->setAttempts(1);
        $unretriedMessage2->setFailedAt(new \DateTimeImmutable('-1 hour'));
        $unretriedMessage2->setRetried(false);
        $this->persistAndFlush($unretriedMessage2);

        // Act: 查找未重试的消息
        $result = $this->repository->findUnretriedMessages(10);

        // Assert: 验证结果
        $this->assertCount(2, $result);
        $this->assertEquals($unretriedMessage1->getId(), $result[0]->getId());
        $this->assertEquals($unretriedMessage2->getId(), $result[1]->getId());
        $this->assertFalse($result[0]->isRetried());
        $this->assertFalse($result[1]->isRetried());
    }

    public function testMarkAsRetriedShouldUpdateStatus(): void
    {
        // Arrange: 创建未重试的消息
        $failedMessage = new FailedMessage();
        $failedMessage->setError('Test message');
        $failedMessage->setAttempts(1);
        $failedMessage->setFailedAt(new \DateTimeImmutable());
        $failedMessage->setRetried(false);
        $this->persistAndFlush($failedMessage);

        // Act: 标记为已重试
        $this->repository->markAsRetried($failedMessage);

        // Assert: 验证状态已更新
        self::getEntityManager()->refresh($failedMessage);
        $this->assertTrue($failedMessage->isRetried());
    }

    public function testCleanupOldMessagesShouldDeleteOldMessages(): void
    {
        // Arrange: 创建新旧消息
        $oldMessage = new FailedMessage();
        $oldMessage->setError('Old message');
        $oldMessage->setAttempts(1);
        $oldMessage->setFailedAt(new \DateTimeImmutable('-45 days'));
        $oldMessage->setRetried(false);
        $this->persistAndFlush($oldMessage);

        $recentMessage = new FailedMessage();
        $recentMessage->setError('Recent message');
        $recentMessage->setAttempts(1);
        $recentMessage->setFailedAt(new \DateTimeImmutable('-15 days'));
        $recentMessage->setRetried(false);
        $this->persistAndFlush($recentMessage);

        // Act: 清理30天前的消息
        $deletedCount = $this->repository->cleanupOldMessages(30);

        // Assert: 验证旧消息被删除，新消息保留
        $this->assertEquals(1, $deletedCount);
        $this->assertEntityNotExists(FailedMessage::class, $oldMessage->getId());
        $found = $this->repository->find($recentMessage->getId());
        $this->assertNotNull($found);
    }

    public function testFlush(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
