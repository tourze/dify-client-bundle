<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\EmbeddingModel;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * Dify 嵌入模型仓储类
 *
 * @extends ServiceEntityRepository<EmbeddingModel>
 */
#[AsRepository(entityClass: EmbeddingModel::class)]
class EmbeddingModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmbeddingModel::class);
    }

    public function findByModelName(string $modelName): ?EmbeddingModel
    {
        return $this->findOneBy(['modelName' => $modelName]);
    }

    /** @return array<EmbeddingModel> */
    public function findByProvider(string $provider): array
    {
        /** @var array<EmbeddingModel> */
        return $this->createQueryBuilder('em')
            ->andWhere('em.provider = :provider')
            ->setParameter('provider', $provider)
            ->orderBy('em.displayName', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return array<EmbeddingModel> */
    public function findAvailableModels(): array
    {
        /** @var array<EmbeddingModel> */
        return $this->createQueryBuilder('em')
            ->andWhere('em.isAvailable = :available')
            ->setParameter('available', true)
            ->orderBy('em.provider', 'ASC')
            ->addOrderBy('em.displayName', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(EmbeddingModel $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EmbeddingModel $entity, bool $flush = true): void
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
