<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\Document;
use Tourze\DifyClientBundle\Entity\DocumentChunk;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * Dify 文档块仓储类
 *
 * @extends ServiceEntityRepository<DocumentChunk>
 */
#[AsRepository(entityClass: DocumentChunk::class)]
class DocumentChunkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentChunk::class);
    }

    public function findBySegmentId(string $segmentId): ?DocumentChunk
    {
        return $this->findOneBy(['segmentId' => $segmentId]);
    }

    /** @return array<DocumentChunk> */
    public function findByDocument(Document $document): array
    {
        /** @var array<DocumentChunk> */
        return $this->createQueryBuilder('dc')
            ->andWhere('dc.document = :document')
            ->setParameter('document', $document)
            ->orderBy('dc.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return array<DocumentChunk> */
    public function findEnabledChunks(): array
    {
        /** @var array<DocumentChunk> */
        return $this->createQueryBuilder('dc')
            ->andWhere('dc.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('dc.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(DocumentChunk $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DocumentChunk $entity, bool $flush = true): void
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
