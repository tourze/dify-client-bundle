<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\WorkflowExecution;
use Tourze\DifyClientBundle\Enum\WorkflowStatus;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * Dify 工作流执行仓储类
 *
 * @extends ServiceEntityRepository<WorkflowExecution>
 */
#[AsRepository(entityClass: WorkflowExecution::class)]
class WorkflowExecutionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkflowExecution::class);
    }

    public function findByWorkflowRunId(string $workflowRunId): ?WorkflowExecution
    {
        return $this->findOneBy(['workflowRunId' => $workflowRunId]);
    }

    public function findByTaskId(string $taskId): ?WorkflowExecution
    {
        return $this->findOneBy(['taskId' => $taskId]);
    }

    /** @return array<WorkflowExecution> */
    public function findByStatus(WorkflowStatus $status): array
    {
        /** @var array<WorkflowExecution> */
        return $this->createQueryBuilder('we')
            ->andWhere('we.status = :status')
            ->setParameter('status', $status)
            ->orderBy('we.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return array<WorkflowExecution> */
    public function findRunningWorkflows(): array
    {
        return $this->findByStatus(WorkflowStatus::RUNNING);
    }

    public function save(WorkflowExecution $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WorkflowExecution $entity, bool $flush = true): void
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
