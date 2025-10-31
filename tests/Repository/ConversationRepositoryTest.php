<?php

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Enum\ConversationStatus;
use Tourze\DifyClientBundle\Repository\ConversationRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(ConversationRepository::class)]
#[RunTestsInSeparateProcesses]
final class ConversationRepositoryTest extends AbstractRepositoryTestCase
{
    private ConversationRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(ConversationRepository::class);
    }

    protected function getRepository(): ConversationRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): Conversation
    {
        $conversation = new Conversation();
        $conversation->setConversationId('test-' . uniqid());
        $conversation->setStatus(ConversationStatus::ACTIVE);

        return $conversation;
    }

    public function testCreateConversationShouldCreateAndPersistNewConversation(): void
    {
        // Act: 创建会话
        $conversation = $this->repository->createConversation();

        // Assert: 验证会话属性
        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertNotNull($conversation->getId());
        $this->assertEquals(ConversationStatus::ACTIVE, $conversation->getStatus());
        $conversationId = $conversation->getConversationId();
        $this->assertNotNull($conversationId);
        $this->assertStringStartsWith('conv_', $conversationId);

        // 验证会话已持久化
        $this->assertEntityPersisted($conversation);
    }

    public function testCreateConversationShouldGenerateUniqueIds(): void
    {
        // Act: 创建多个会话
        $conversation1 = $this->repository->createConversation();
        $conversation2 = $this->repository->createConversation();

        // Assert: 验证ID唯一性
        $this->assertNotEquals($conversation1->getId(), $conversation2->getId());
        $this->assertNotEquals($conversation1->getConversationId(), $conversation2->getConversationId());
    }

    public function testFindActiveConversationsShouldReturnOnlyActiveConversations(): void
    {
        // Arrange: 清理现有数据并创建不同状态的会话
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM dify_conversation');
        self::getEntityManager()->clear();
        $activeConv1 = new Conversation();
        $activeConv1->setConversationId('active-1');
        $activeConv1->setStatus(ConversationStatus::ACTIVE);

        $activeConv2 = new Conversation();
        $activeConv2->setConversationId('active-2');
        $activeConv2->setStatus(ConversationStatus::ACTIVE);

        $inactiveConv = new Conversation();
        $inactiveConv->setConversationId('inactive-1');
        $inactiveConv->setStatus(ConversationStatus::INACTIVE);

        $this->persistAndFlush($activeConv1);
        $this->persistAndFlush($activeConv2);
        $this->persistAndFlush($inactiveConv);

        // Act: 查找激活的会话
        $activeConversations = $this->repository->findActiveConversations();

        // Assert: 只返回激活的会话
        $this->assertCount(2, $activeConversations);

        $conversationIds = array_map(fn ($conv) => $conv->getConversationId(), $activeConversations);
        $this->assertContains('active-1', $conversationIds);
        $this->assertContains('active-2', $conversationIds);
        $this->assertNotContains('inactive-1', $conversationIds);
    }

    public function testFindActiveConversationsWithNoActiveConversationsShouldReturnEmptyArray(): void
    {
        // Arrange: 清理现有数据并创建非激活会话
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM dify_conversation');
        self::getEntityManager()->clear();
        $inactiveConv = new Conversation();
        $inactiveConv->setConversationId('inactive-only');
        $inactiveConv->setStatus(ConversationStatus::INACTIVE);
        $this->persistAndFlush($inactiveConv);

        // Act: 查找激活的会话
        $activeConversations = $this->repository->findActiveConversations();

        // Assert: 返回空数组
        $this->assertEmpty($activeConversations);
    }

    public function testUpdateLastActiveShouldUpdateTimestamp(): void
    {
        // Arrange: 创建会话
        $conversation = new Conversation();
        $conversation->setConversationId('test-update');
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $this->persistAndFlush($conversation);

        $originalLastActive = $conversation->getLastActive();

        // 等待一小段时间确保时间戳不同
        usleep(1000);

        // Act: 更新最后活跃时间
        $this->repository->updateLastActive($conversation);

        // Assert: 验证时间戳已更新
        $this->assertNotNull($conversation->getLastActive());
        $this->assertNotEquals($originalLastActive, $conversation->getLastActive());
        $this->assertGreaterThan($originalLastActive ?? new \DateTimeImmutable('1970-01-01'), $conversation->getLastActive());
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新会话（未持久化）
        $conversation = new Conversation();
        $conversation->setConversationId('test-save');
        $conversation->setStatus(ConversationStatus::ACTIVE);

        // Act: 保存会话
        $this->repository->save($conversation);

        // Assert: 验证会话已持久化
        $this->assertNotNull($conversation->getId());
        $this->assertEntityPersisted($conversation);
    }

    public function testSaveShouldUpdateExistingEntity(): void
    {
        // Arrange: 创建并持久化会话
        $conversation = new Conversation();
        $conversation->setConversationId('test-update-save');
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $this->persistAndFlush($conversation);

        // Act: 修改并保存
        $conversation->setStatus(ConversationStatus::INACTIVE);
        $this->repository->save($conversation);

        // Assert: 验证更新已持久化
        self::getEntityManager()->refresh($conversation);
        $this->assertEquals(ConversationStatus::INACTIVE, $conversation->getStatus());
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化会话
        $conversation = new Conversation();
        $conversation->setConversationId('test-remove');
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $this->persistAndFlush($conversation);

        $conversationId = $conversation->getId();

        // Act: 删除会话
        $this->repository->remove($conversation);

        // Assert: 验证会话已删除
        $this->assertEntityNotExists(Conversation::class, $conversationId);
    }

    public function testRemoveWithoutFlushShouldNotDeleteImmediately(): void
    {
        // Arrange: 创建并持久化会话
        $conversation = new Conversation();
        $conversation->setConversationId('test-remove-no-flush');
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $this->persistAndFlush($conversation);

        $conversationId = $conversation->getId();

        // Act: 删除会话但不刷新
        $this->repository->remove($conversation, false);

        // Assert: 验证会话仍然存在（在数据库中）
        // 注意：不能使用 clear() 因为它会清除所有UnitOfWork状态包括待删除状态
        // 直接从数据库查询验证
        $em = self::getEntityManager();
        $qb = $this->repository->createQueryBuilder('c');
        $qb->select('COUNT(c.id)')
            ->where('c.id = :id')
            ->setParameter('id', $conversationId)
        ;

        $count = (int) $qb->getQuery()->getSingleScalarResult();
        $this->assertEquals(1, $count, '删除未flush时，实体应该仍在数据库中');

        // 手动刷新后应该被删除
        $em->flush();

        $count = (int) $qb->getQuery()->getSingleScalarResult();
        $this->assertEquals(0, $count, 'flush后，实体应该被删除');
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
        $this->assertEquals(Conversation::class, $this->repository->getClassName());
    }

    public function testFlush(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
