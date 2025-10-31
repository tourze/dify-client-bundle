<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\MessageFeedback;
use Tourze\DifyClientBundle\Enum\ConversationStatus;
use Tourze\DifyClientBundle\Enum\MessageRole;
use Tourze\DifyClientBundle\Enum\MessageStatus;
use Tourze\DifyClientBundle\Repository\MessageFeedbackRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(MessageFeedbackRepository::class)]
#[RunTestsInSeparateProcesses]
final class MessageFeedbackRepositoryTest extends AbstractRepositoryTestCase
{
    private MessageFeedbackRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(MessageFeedbackRepository::class);
    }

    protected function getRepository(): MessageFeedbackRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): MessageFeedback
    {
        // 创建测试会话
        $conversation = new Conversation();
        $conversation->setConversationId('test-conv-' . uniqid());
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $this->persistAndFlush($conversation);

        // 创建测试消息
        $message = new Message();
        $message->setConversation($conversation);
        $message->setRole(MessageRole::ASSISTANT);
        $message->setContent('Test message content');
        $message->setStatus(MessageStatus::SENT);
        $this->persistAndFlush($message);

        $messageFeedback = new MessageFeedback();
        $messageFeedback->setFeedbackId('test-feedback-' . uniqid());
        $messageFeedback->setMessage($message);
        $messageFeedback->setRating('like');
        $messageFeedback->setContent('Good response');
        $messageFeedback->setUserId('test-user');

        return $messageFeedback;
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新消息反馈
        $messageFeedback = $this->createNewEntity();

        // Act: 保存反馈
        $this->repository->save($messageFeedback);

        // Assert: 验证反馈已持久化
        $this->assertNotNull($messageFeedback->getId());
        $this->assertEntityPersisted($messageFeedback);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化反馈
        $messageFeedback = $this->createNewEntity();
        $this->persistAndFlush($messageFeedback);
        $feedbackId = $messageFeedback->getId();

        // Act: 删除反馈
        $this->repository->remove($messageFeedback);

        // Assert: 验证反馈已删除
        $this->assertEntityNotExists(MessageFeedback::class, $feedbackId);
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryHasCorrectEntityClass(): void
    {
        $this->assertSame(MessageFeedback::class, $this->repository->getClassName());
    }

    public function testGetEntityManagerShouldReturnEntityManagerInterface(): void
    {
        $em = self::getEntityManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $em);
    }

    public function testFindByFeedbackId(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByRating(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindUnprocessedFeedbacks(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFlush(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
