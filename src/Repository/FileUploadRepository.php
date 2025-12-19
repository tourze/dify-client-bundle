<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\FileUpload;
use Tourze\DifyClientBundle\Enum\FileTransferMethod;
use Tourze\DifyClientBundle\Enum\FileType;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * Dify 文件上传仓储类
 *
 * @extends ServiceEntityRepository<FileUpload>
 */
#[AsRepository(entityClass: FileUpload::class)]
final class FileUploadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileUpload::class);
    }

    public function findByFileId(string $fileId): ?FileUpload
    {
        return $this->findOneBy(['fileId' => $fileId]);
    }

    /** @return array<FileUpload> */
    public function findByType(FileType $type): array
    {
        return $this->findBy(['type' => $type]);
    }

    /** @return array<FileUpload> */
    public function findByTransferMethod(FileTransferMethod $transferMethod): array
    {
        return $this->findBy(['transferMethod' => $transferMethod]);
    }

    /** @return array<FileUpload> */
    public function findRecentUploads(int $limit = 20): array
    {
        /** @var array<FileUpload> */
        return $this->createQueryBuilder('f')
            ->orderBy('f.uploadedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return array<FileUpload> */
    public function findByName(string $name): array
    {
        /** @var array<FileUpload> */
        return $this->createQueryBuilder('f')
            ->andWhere('f.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(FileUpload $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FileUpload $entity, bool $flush = true): void
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
