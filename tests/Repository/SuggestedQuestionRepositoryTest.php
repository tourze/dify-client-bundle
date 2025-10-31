<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\SuggestedQuestion;
use Tourze\DifyClientBundle\Enum\ConversationStatus;
use Tourze\DifyClientBundle\Enum\MessageRole;
use Tourze\DifyClientBundle\Enum\MessageStatus;
use Tourze\DifyClientBundle\Repository\SuggestedQuestionRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(SuggestedQuestionRepository::class)]
#[RunTestsInSeparateProcesses]
final class SuggestedQuestionRepositoryTest extends AbstractRepositoryTestCase
{
    private SuggestedQuestionRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(SuggestedQuestionRepository::class);
    }

    protected function getRepository(): SuggestedQuestionRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): SuggestedQuestion
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

        $suggestedQuestion = new SuggestedQuestion();
        $suggestedQuestion->setMessage($message);
        $suggestedQuestion->setConversation($conversation);
        $suggestedQuestion->setQuestion('What is AI?');
        $suggestedQuestion->setSortOrder(1);
        $suggestedQuestion->setEnabled(true);

        return $suggestedQuestion;
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新建议问题
        $suggestedQuestion = $this->createNewEntity();

        // Act: 保存建议问题
        $this->repository->save($suggestedQuestion);

        // Assert: 验证建议问题已持久化
        $this->assertNotNull($suggestedQuestion->getId());
        $this->assertEntityPersisted($suggestedQuestion);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化建议问题
        $suggestedQuestion = $this->createNewEntity();
        $this->persistAndFlush($suggestedQuestion);
        $questionId = $suggestedQuestion->getId();

        // Act: 删除建议问题
        $this->repository->remove($suggestedQuestion);

        // Assert: 验证建议问题已删除
        $this->assertEntityNotExists(SuggestedQuestion::class, $questionId);
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryHasCorrectEntityClass(): void
    {
        $this->assertSame(SuggestedQuestion::class, $this->repository->getClassName());
    }

    public function testGetEntityManagerShouldReturnEntityManagerInterface(): void
    {
        $em = self::getEntityManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $em);
    }

    public function testFindByConversation(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByMessage(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFlush(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testIncrementClickCount(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
