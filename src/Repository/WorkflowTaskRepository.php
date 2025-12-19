<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\WorkflowExecution;
use Tourze\DifyClientBundle\Entity\WorkflowTask;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * Dify 工作流任务仓储类
 *
 * @extends ServiceEntityRepository<WorkflowTask>
 */
#[AsRepository(entityClass: WorkflowTask::class)]
final class WorkflowTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkflowTask::class);
    }

    public function findByNodeId(string $nodeId): ?WorkflowTask
    {
        return $this->findOneBy(['nodeId' => $nodeId]);
    }

    public function findByTaskId(string $taskId): ?WorkflowTask
    {
        return $this->findOneBy(['taskId' => $taskId]);
    }

    /** @return array<WorkflowTask> */
    public function findByWorkflowExecution(WorkflowExecution $workflowExecution): array
    {
        /** @var array<WorkflowTask> */
        return $this->createQueryBuilder('wt')
            ->andWhere('wt.workflowExecution = :workflowExecution')
            ->setParameter('workflowExecution', $workflowExecution)
            ->orderBy('wt.executionOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return array<WorkflowTask> */
    public function findByStatus(string $status): array
    {
        /** @var array<WorkflowTask> */
        return $this->createQueryBuilder('wt')
            ->andWhere('wt.status = :status')
            ->setParameter('status', $status)
            ->orderBy('wt.createdAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(WorkflowTask $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WorkflowTask $entity, bool $flush = true): void
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
