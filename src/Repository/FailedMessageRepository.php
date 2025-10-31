<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\FailedMessage;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<FailedMessage>
 */
#[AsRepository(entityClass: FailedMessage::class)]
class FailedMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FailedMessage::class);
    }

    /** @return array<FailedMessage> */
    public function findUnretriedMessages(int $limit = 100): array
    {
        return $this->findBy(
            ['retried' => false],
            ['failTime' => 'ASC'],
            $limit
        );
    }

    public function save(FailedMessage $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FailedMessage $entity, bool $flush = true): void
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

    public function markAsRetried(FailedMessage $failedMessage): void
    {
        $failedMessage->setRetried(true);
        $this->getEntityManager()->flush();
    }

    public function cleanupOldMessages(int $days = 30): int
    {
        $cutoffDate = new \DateTimeImmutable("-{$days} days");

        $qb = $this->createQueryBuilder('f');
        $qb->delete()
            ->where('f.failTime < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
        ;

        /** @var int */
        return $qb->getQuery()->execute();
    }
}
