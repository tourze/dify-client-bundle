<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\ConversationVariable;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * Dify 会话变量仓储类
 *
 * @extends ServiceEntityRepository<ConversationVariable>
 */
#[AsRepository(entityClass: ConversationVariable::class)]
class ConversationVariableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConversationVariable::class);
    }

    /** @return array<ConversationVariable> */
    public function findByConversation(Conversation $conversation): array
    {
        return $this->findBy(['conversation' => $conversation]);
    }

    public function findByConversationAndName(Conversation $conversation, string $name): ?ConversationVariable
    {
        return $this->findOneBy([
            'conversation' => $conversation,
            'name' => $name,
        ]);
    }

    /** @return array<ConversationVariable> */
    public function findRequiredByConversation(Conversation $conversation): array
    {
        return $this->findBy([
            'conversation' => $conversation,
            'required' => true,
        ]);
    }

    /** @return array<ConversationVariable> */
    public function findByType(string $type): array
    {
        return $this->findBy(['type' => $type]);
    }

    public function save(ConversationVariable $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ConversationVariable $entity, bool $flush = true): void
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
