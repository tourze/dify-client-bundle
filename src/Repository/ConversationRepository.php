<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Enum\ConversationStatus;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
#[AsRepository(entityClass: Conversation::class)]
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    public function createConversation(): Conversation
    {
        $conversation = new Conversation();
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $conversation->setConversationId(uniqid('conv_', true));

        $em = $this->getEntityManager();
        $em->persist($conversation);
        $em->flush();

        return $conversation;
    }

    /** @return array<Conversation> */
    public function findActiveConversations(): array
    {
        return $this->findBy([
            'status' => ConversationStatus::ACTIVE,
        ]);
    }

    public function updateLastActive(Conversation $conversation): void
    {
        $conversation->setLastActive(new \DateTimeImmutable());
        $this->getEntityManager()->flush();
    }

    public function save(Conversation $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Conversation $entity, bool $flush = true): void
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
