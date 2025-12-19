<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Entity\Document;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * Dify 文档仓储类
 *
 * @extends ServiceEntityRepository<Document>
 */
#[AsRepository(entityClass: Document::class)]
final class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    public function findByDocumentId(string $documentId): ?Document
    {
        return $this->findOneBy(['documentId' => $documentId]);
    }

    /** @return array<Document> */
    public function findByDataset(Dataset $dataset): array
    {
        /** @var array<Document> */
        return $this->createQueryBuilder('d')
            ->andWhere('d.dataset = :dataset')
            ->setParameter('dataset', $dataset)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return array<Document> */
    public function findByDataSource(string $dataSource): array
    {
        /** @var array<Document> */
        return $this->createQueryBuilder('d')
            ->andWhere('d.dataSource = :dataSource')
            ->setParameter('dataSource', $dataSource)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(Document $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Document $entity, bool $flush = true): void
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

    /**
     * 搜索文档
     * @return array<Document>
     */
    public function search(
        ?string $query = null,
        ?Dataset $dataset = null,
        ?string $status = null,
        int $limit = 50,
        int $offset = 0,
    ): array {
        $qb = $this->createQueryBuilder('d');

        if (null !== $query) {
            $qb->andWhere('d.name LIKE :query OR d.content LIKE :query')
                ->setParameter('query', '%' . $query . '%')
            ;
        }

        if (null !== $dataset) {
            $qb->andWhere('d.dataset = :dataset')
                ->setParameter('dataset', $dataset)
            ;
        }

        if (null !== $status) {
            $qb->andWhere('d.processingStatus = :status')
                ->setParameter('status', $status)
            ;
        }

        /** @var array<Document> */
        return $qb
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取文档统计信息
     * @return array<string, mixed>
     */
    public function getStatistics(?Dataset $dataset = null, ?ClockInterface $clock = null): array
    {
        $qb = $this->createQueryBuilder('d');

        if (null !== $dataset) {
            $qb->andWhere('d.dataset = :dataset')
                ->setParameter('dataset', $dataset)
            ;
        }

        $totalDocuments = (int) $qb
            ->select('COUNT(d.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $processedDocuments = (int) $qb
            ->select('COUNT(d.id)')
            ->where('d.processingStatus = :status')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $failedDocuments = (int) $qb
            ->select('COUNT(d.id)')
            ->where('d.processingStatus = :status')
            ->setParameter('status', 'failed')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $totalTokens = (int) $qb
            ->select('SUM(d.tokens)')
            ->where('d.tokens IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $totalWordCount = (int) $qb
            ->select('SUM(d.wordCount)')
            ->where('d.wordCount IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return [
            'total_documents' => $totalDocuments,
            'processed_documents' => $processedDocuments,
            'failed_documents' => $failedDocuments,
            'processing_rate' => $totalDocuments > 0 ? round($processedDocuments / $totalDocuments * 100, 2) : 0,
            'total_tokens' => $totalTokens,
            'total_word_count' => $totalWordCount,
        ];
    }

    /**
     * 查找失败的文档
     * @return array<Document>
     */
    public function findFailedDocuments(\DateTimeInterface $expiredDate): array
    {
        /** @var array<Document> */
        return $this->createQueryBuilder('d')
            ->where('d.processingStatus = :status')
            ->andWhere('d.createdAt < :expiredDate')
            ->setParameter('status', 'failed')
            ->setParameter('expiredDate', $expiredDate)
            ->getQuery()
            ->getResult()
        ;
    }
}
