<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\RetrieverResource;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * Dify 检索资源仓储类
 *
 * @extends ServiceEntityRepository<RetrieverResource>
 */
#[AsRepository(entityClass: RetrieverResource::class)]
final class RetrieverResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RetrieverResource::class);
    }

    /** @return array<RetrieverResource> */
    public function findByMessage(Message $message): array
    {
        /** @var array<RetrieverResource> */
        return $this->createQueryBuilder('rr')
            ->andWhere('rr.message = :message')
            ->setParameter('message', $message)
            ->orderBy('rr.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return array<RetrieverResource> */
    public function findByDatasetId(string $datasetId): array
    {
        /** @var array<RetrieverResource> */
        return $this->createQueryBuilder('rr')
            ->andWhere('rr.datasetId = :datasetId')
            ->setParameter('datasetId', $datasetId)
            ->orderBy('rr.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return array<RetrieverResource> */
    public function findTopRelevantResources(int $limit = 10): array
    {
        /** @var array<RetrieverResource> */
        return $this->createQueryBuilder('rr')
            ->orderBy('rr.score', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(RetrieverResource $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RetrieverResource $entity, bool $flush = true): void
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
