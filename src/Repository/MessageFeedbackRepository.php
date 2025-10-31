<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\MessageFeedback;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * Dify 消息反馈仓储类
 *
 * @extends ServiceEntityRepository<MessageFeedback>
 */
#[AsRepository(entityClass: MessageFeedback::class)]
class MessageFeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageFeedback::class);
    }

    public function findByFeedbackId(string $feedbackId): ?MessageFeedback
    {
        return $this->findOneBy(['feedbackId' => $feedbackId]);
    }

    /** @return array<MessageFeedback> */
    public function findByRating(string $rating): array
    {
        /** @var array<MessageFeedback> */
        return $this->createQueryBuilder('mf')
            ->andWhere('mf.rating = :rating')
            ->setParameter('rating', $rating)
            ->orderBy('mf.submittedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return array<MessageFeedback> */
    public function findUnprocessedFeedbacks(): array
    {
        /** @var array<MessageFeedback> */
        return $this->createQueryBuilder('mf')
            ->andWhere('mf.processed = :processed')
            ->setParameter('processed', false)
            ->orderBy('mf.submittedAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(MessageFeedback $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MessageFeedback $entity, bool $flush = true): void
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
