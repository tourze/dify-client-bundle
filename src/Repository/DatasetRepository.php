<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Entity\DatasetTag;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * Dify 数据集仓储类
 *
 * @extends ServiceEntityRepository<Dataset>
 */
#[AsRepository(entityClass: Dataset::class)]
class DatasetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dataset::class);
    }

    public function findByDatasetId(string $datasetId): ?Dataset
    {
        return $this->findOneBy(['datasetId' => $datasetId]);
    }

    /**
     * @return array<Dataset>
     */
    public function findByDataSourceType(string $dataSourceType): array
    {
        return $this->findBy(['dataSourceType' => $dataSourceType]);
    }

    /**
     * @return array<Dataset>
     */
    public function findByIndexingTechnique(string $indexingTechnique): array
    {
        return $this->findBy(['indexingTechnique' => $indexingTechnique]);
    }

    /**
     * @return array<Dataset>
     */
    public function findByCreatedBy(string $createdBy): array
    {
        return $this->findBy(['createdBy' => $createdBy]);
    }

    /**
     * @return array<Dataset>
     */
    public function findByTag(DatasetTag $tag): array
    {
        /** @var array<Dataset> */
        return $this->createQueryBuilder('d')
            ->innerJoin('d.tags', 't')
            ->andWhere('t = :tag')
            ->setParameter('tag', $tag)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<Dataset>
     */
    public function findByNameContaining(string $searchTerm): array
    {
        /** @var array<Dataset> */
        return $this->createQueryBuilder('d')
            ->andWhere('d.name LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<Dataset>
     */
    public function findMostUsed(int $limit = 10): array
    {
        /** @var array<Dataset> */
        return $this->createQueryBuilder('d')
            ->orderBy('d.documentCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 搜索数据集
     * @param ?array<DatasetTag> $tags
     * @return array<Dataset>
     */
    public function search(
        ?string $query = null,
        ?array $tags = null,
        ?string $indexingTechnique = null,
        int $limit = 50,
        int $offset = 0,
    ): array {
        $qb = $this->createQueryBuilder('d');

        // 关键词搜索（搜索名称和描述）
        if (null !== $query && '' !== $query) {
            $qb->andWhere('d.name LIKE :query OR d.description LIKE :query')
                ->setParameter('query', '%' . $query . '%')
            ;
        }

        // 根据标签过滤
        if (null !== $tags && [] !== $tags) {
            $qb->innerJoin('d.tags', 't')
                ->andWhere('t IN (:tags)')
                ->setParameter('tags', $tags)
            ;
        }

        // 根据索引技术过滤
        if (null !== $indexingTechnique && '' !== $indexingTechnique) {
            $qb->andWhere('d.indexingTechnique = :indexingTechnique')
                ->setParameter('indexingTechnique', $indexingTechnique)
            ;
        }

        /** @var array<Dataset> */
        return $qb
            ->orderBy('d.createTime', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取数据集统计信息
     * @return array<string, mixed>
     */
    public function getStatistics(?ClockInterface $clock = null): array
    {
        $qb = $this->createQueryBuilder('d');

        // 总数据集数量
        $totalCount = (int) $this->createQueryBuilder('d1')
            ->select('COUNT(d1.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        // 按数据源类型分组统计
        $byDataSourceType = $this->createQueryBuilder('d2')
            ->select('d2.dataSourceType', 'COUNT(d2.id) as count')
            ->groupBy('d2.dataSourceType')
            ->getQuery()
            ->getResult()
        ;

        // 按索引技术分组统计
        $byIndexingTechnique = $this->createQueryBuilder('d3')
            ->select('d3.indexingTechnique', 'COUNT(d3.id) as count')
            ->groupBy('d3.indexingTechnique')
            ->getQuery()
            ->getResult()
        ;

        // 总文档数量和字数
        /** @var array{totalDocuments: string|null, totalWords: string|null} $documentStats */
        $documentStats = $this->createQueryBuilder('d4')
            ->select('SUM(d4.documentCount) as totalDocuments', 'SUM(d4.wordCount) as totalWords')
            ->getQuery()
            ->getSingleResult()
        ;

        // 最近创建的数据集（7天内）
        $recentCount = 0;
        if (null !== $clock) {
            $weekAgo = $clock->now()->modify('-7 days');
            $recentCount = (int) $this->createQueryBuilder('d5')
                ->select('COUNT(d5.id)')
                ->andWhere('d5.createTime >= :weekAgo')
                ->setParameter('weekAgo', $weekAgo)
                ->getQuery()
                ->getSingleScalarResult()
            ;
        }

        return [
            'totalCount' => $totalCount,
            'recentCount' => $recentCount,
            'totalDocuments' => (int) ($documentStats['totalDocuments'] ?? 0),
            'totalWords' => (int) ($documentStats['totalWords'] ?? 0),
            'byDataSourceType' => $byDataSourceType,
            'byIndexingTechnique' => $byIndexingTechnique,
        ];
    }

    public function save(Dataset $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Dataset $entity, bool $flush = true): void
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
