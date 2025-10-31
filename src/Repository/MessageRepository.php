<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Enum\MessageRole;
use Tourze\DifyClientBundle\Enum\MessageStatus;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Message>
 */
#[AsRepository(entityClass: Message::class)]
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /** @return array<Message> */
    public function findPendingMessages(int $limit = 100): array
    {
        return $this->findBy(
            ['status' => MessageStatus::PENDING],
            ['createTime' => 'ASC'],
            $limit
        );
    }

    /** @return array<Message> */
    public function findMessagesByConversation(Conversation $conversation): array
    {
        return $this->findBy(
            ['conversation' => $conversation],
            ['createTime' => 'ASC']
        );
    }

    /** @return array<Message> */
    public function findConversationHistory(Conversation $conversation, int $limit = 50, int $offset = 0): array
    {
        return $this->findBy(
            ['conversation' => $conversation],
            ['createTime' => 'DESC'],
            $limit,
            $offset
        );
    }

    /** @return array<Message> */
    public function findUserMessagesForAggregation(Conversation $conversation, \DateTimeImmutable $cutoffTime): array
    {
        /** @var array<Message> */
        return $this->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->andWhere('m.role = :role')
            ->andWhere('m.status = :status')
            ->andWhere('m.createTime >= :cutoffTime')
            ->setParameter('conversation', $conversation)
            ->setParameter('role', MessageRole::USER)
            ->setParameter('status', MessageStatus::PENDING)
            ->setParameter('cutoffTime', $cutoffTime)
            ->orderBy('m.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @param array<Message> $messages */
    public function markMessagesAsAggregated(array $messages): void
    {
        $em = $this->getEntityManager();

        foreach ($messages as $message) {
            $message->setStatus(MessageStatus::AGGREGATED);
            $em->persist($message);
        }

        $em->flush();
    }

    /** @return array<Message> */
    public function findFailedMessages(int $maxAttempts = 3): array
    {
        /** @var array<Message> */
        return $this->createQueryBuilder('m')
            ->where('m.retryCount < :maxAttempts')
            ->andWhere('m.status = :status')
            ->setParameter('maxAttempts', $maxAttempts)
            ->setParameter('status', MessageStatus::FAILED)
            ->orderBy('m.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(Message $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Message $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return parent::getEntityManager();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
