<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\RequestTask;
use Tourze\DifyClientBundle\Enum\RequestTaskStatus;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<RequestTask>
 */
#[AsRepository(entityClass: RequestTask::class)]
final class RequestTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RequestTask::class);
    }

    /** @param array<string, mixed> $metadata */
    public function createRequestTask(string $aggregatedContent, int $messageCount, array $metadata = []): RequestTask
    {
        $task = new RequestTask();
        $task->setTaskId('task_' . uniqid() . '_' . time());
        $task->setAggregatedContent($aggregatedContent);
        $task->setMessageCount($messageCount);
        $task->setMetadata($metadata);
        $task->setStatus(RequestTaskStatus::PENDING);

        $em = $this->getEntityManager();
        $em->persist($task);
        $em->flush();

        return $task;
    }

    /** @return array<RequestTask> */
    public function findPendingTasks(int $limit = 100): array
    {
        return $this->findBy(
            ['status' => RequestTaskStatus::PENDING],
            ['createTime' => 'ASC'],
            $limit
        );
    }

    /** @return array<RequestTask> */
    public function findFailedTasks(int $limit = 100): array
    {
        return $this->findBy(
            ['status' => [RequestTaskStatus::FAILED, RequestTaskStatus::TIMEOUT]],
            ['createTime' => 'ASC'],
            $limit
        );
    }

    public function findTaskByTaskId(string $taskId): ?RequestTask
    {
        return $this->findOneBy(['taskId' => $taskId]);
    }

    public function markTaskAsProcessing(RequestTask $task): void
    {
        $task->markAsProcessed();
        $this->getEntityManager()->flush();
    }

    public function markTaskAsCompleted(RequestTask $task, string $response): void
    {
        $task->markAsCompleted($response);
        $this->getEntityManager()->flush();
    }

    public function markTaskAsFailed(RequestTask $task, string $errorMessage): void
    {
        $task->markAsFailed($errorMessage);
        $this->getEntityManager()->flush();
    }

    public function cleanupOldTasks(int $days = 30): int
    {
        $cutoffDate = new \DateTimeImmutable("-{$days} days");

        $qb = $this->createQueryBuilder('t');
        $qb->delete()
            ->where('t.createTime < :cutoffDate')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('cutoffDate', $cutoffDate)
            ->setParameter('statuses', [RequestTaskStatus::COMPLETED, RequestTaskStatus::FAILED])
        ;

        /** @var int */
        return $qb->getQuery()->execute();
    }

    public function save(RequestTask $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RequestTask $entity, bool $flush = true): void
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
