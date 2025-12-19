<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\Annotation;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * Dify 标注仓储类
 *
 * @extends ServiceEntityRepository<Annotation>
 */
#[AsRepository(entityClass: Annotation::class)]
final class AnnotationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Annotation::class);
    }

    public function findByAnnotationId(string $annotationId): ?Annotation
    {
        return $this->findOneBy(['annotationId' => $annotationId]);
    }

    /**
     * @return array<Annotation>
     */
    public function findEnabledAnnotations(): array
    {
        /** @var array<Annotation> */
        return $this->createQueryBuilder('a')
            ->andWhere('a.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('a.hitCount', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<Annotation>
     */
    public function search(?string $keyword = null, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('a');

        if (null !== $keyword && '' !== $keyword) {
            $qb->andWhere('a.question LIKE :keyword OR a.answer LIKE :keyword')
                ->setParameter('keyword', '%' . $keyword . '%')
            ;
        }

        /** @var array<Annotation> */
        return $qb
            ->orderBy('a.createTime', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult()
        ;
    }

    public function recordHit(Annotation $annotation): void
    {
        $annotation->setHitCount($annotation->getHitCount() + 1);
        $annotation->setLastHitAt(new \DateTimeImmutable());
        $this->getEntityManager()->flush();
    }

    public function save(Annotation $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Annotation $entity, bool $flush = true): void
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
