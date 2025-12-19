<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\SuggestedQuestion;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * Dify 建议问题仓储类
 *
 * @extends ServiceEntityRepository<SuggestedQuestion>
 */
#[AsRepository(entityClass: SuggestedQuestion::class)]
final class SuggestedQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SuggestedQuestion::class);
    }

    /** @return array<SuggestedQuestion> */
    public function findByMessage(Message $message): array
    {
        /** @var array<SuggestedQuestion> */
        return $this->createQueryBuilder('sq')
            ->andWhere('sq.message = :message')
            ->andWhere('sq.visible = :visible')
            ->setParameter('message', $message)
            ->setParameter('visible', true)
            ->orderBy('sq.sortOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return array<SuggestedQuestion> */
    public function findByConversation(Conversation $conversation): array
    {
        /** @var array<SuggestedQuestion> */
        return $this->createQueryBuilder('sq')
            ->andWhere('sq.conversation = :conversation')
            ->andWhere('sq.visible = :visible')
            ->setParameter('conversation', $conversation)
            ->setParameter('visible', true)
            ->orderBy('sq.sortOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function incrementClickCount(SuggestedQuestion $question): void
    {
        $question->setClickCount($question->getClickCount() + 1);
        $this->getEntityManager()->flush();
    }

    public function save(SuggestedQuestion $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SuggestedQuestion $entity, bool $flush = true): void
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
