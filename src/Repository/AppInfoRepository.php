<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\AppInfo;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * Dify 应用信息仓储类
 *
 * @extends ServiceEntityRepository<AppInfo>
 */
#[AsRepository(entityClass: AppInfo::class)]
class AppInfoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppInfo::class);
    }

    public function findByAppId(string $appId): ?AppInfo
    {
        return $this->findOneBy(['appId' => $appId]);
    }

    /**
     * @return array<AppInfo>
     */
    public function findByMode(string $mode): array
    {
        return $this->findBy(['mode' => $mode]);
    }

    /**
     * @return array<AppInfo>
     */
    public function findEnabledApps(): array
    {
        /** @var array<AppInfo> */
        return $this->createQueryBuilder('a')
            ->andWhere('a.enableApi = :enableApi')
            ->setParameter('enableApi', true)
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(AppInfo $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AppInfo $entity, bool $flush = true): void
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
