<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\AudioTranscription;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * Dify 语音转录仓储类
 *
 * @extends ServiceEntityRepository<AudioTranscription>
 */
#[AsRepository(entityClass: AudioTranscription::class)]
final class AudioTranscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AudioTranscription::class);
    }

    public function findByTaskId(string $taskId): ?AudioTranscription
    {
        return $this->findOneBy(['taskId' => $taskId]);
    }

    /**
     * @return array<AudioTranscription>
     */
    public function findByStatus(string $status): array
    {
        /** @var array<AudioTranscription> */
        return $this->createQueryBuilder('at')
            ->andWhere('at.status = :status')
            ->setParameter('status', $status)
            ->orderBy('at.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return array<AudioTranscription> */
    public function findPendingTasks(): array
    {
        return $this->findByStatus('pending');
    }

    public function save(AudioTranscription $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AudioTranscription $entity, bool $flush = true): void
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
