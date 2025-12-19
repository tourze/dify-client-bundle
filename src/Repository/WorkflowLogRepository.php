<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\DifyClientBundle\Entity\WorkflowExecution;
use Tourze\DifyClientBundle\Entity\WorkflowLog;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * Dify 工作流日志仓储类
 *
 * @extends ServiceEntityRepository<WorkflowLog>
 */
#[AsRepository(entityClass: WorkflowLog::class)]
final class WorkflowLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkflowLog::class);
    }

    /** @return array<WorkflowLog> */
    public function findByWorkflowExecution(WorkflowExecution $workflowExecution): array
    {
        /** @var array<WorkflowLog> */
        return $this->createQueryBuilder('wl')
            ->andWhere('wl.workflowExecution = :workflowExecution')
            ->setParameter('workflowExecution', $workflowExecution)
            ->orderBy('wl.createdAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return array<WorkflowLog> */
    public function findByLogLevel(string $logLevel): array
    {
        /** @var array<WorkflowLog> */
        return $this->createQueryBuilder('wl')
            ->andWhere('wl.logLevel = :logLevel')
            ->setParameter('logLevel', $logLevel)
            ->orderBy('wl.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return array<WorkflowLog> */
    public function findErrorLogs(): array
    {
        return $this->findByLogLevel('error');
    }

    public function save(WorkflowLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WorkflowLog $entity, bool $flush = true): void
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
