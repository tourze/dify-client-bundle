<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\DatasetTag;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * Dify 数据集标签仓储类
 *
 * @extends ServiceEntityRepository<DatasetTag>
 */
#[AsRepository(entityClass: DatasetTag::class)]
class DatasetTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DatasetTag::class);
    }

    public function findByTagId(string $tagId): ?DatasetTag
    {
        return $this->findOneBy(['tagId' => $tagId]);
    }

    public function findByName(string $name): ?DatasetTag
    {
        return $this->findOneBy(['name' => $name]);
    }

    /** @return array<DatasetTag> */
    public function findActiveTagsOrderByUsage(): array
    {
        /** @var array<DatasetTag> */
        return $this->createQueryBuilder('dt')
            ->orderBy('dt.usageCount', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function incrementUsageCount(DatasetTag $tag): void
    {
        $currentCount = $tag->getUsageCount();
        $tag->setUsageCount($currentCount + 1);
        $this->getEntityManager()->flush();
    }

    /**
     * 获取流行标签
     * @return array<array{name: string, count: int}>
     */
    public function getPopularTags(int $limit = 10): array
    {
        /** @var array<array{name: string, count: int}> */
        return $this->createQueryBuilder('dt')
            ->select('dt.name', 'dt.usageCount as count')
            ->andWhere('dt.usageCount > 0')
            ->orderBy('dt.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找未使用的标签
     * @return array<DatasetTag>
     */
    public function findUnusedTags(): array
    {
        /** @var array<DatasetTag> */
        return $this->createQueryBuilder('dt')
            ->leftJoin('dt.datasets', 'd')
            ->andWhere('SIZE(dt.datasets) = 0 OR dt.usageCount = 0')
            ->orderBy('dt.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(DatasetTag $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DatasetTag $entity, bool $flush = true): void
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
