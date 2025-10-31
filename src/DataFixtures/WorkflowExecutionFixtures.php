<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\WorkflowExecution;
use Tourze\DifyClientBundle\Enum\WorkflowStatus;

class WorkflowExecutionFixtures extends Fixture
{
    public const WORKFLOW_EXECUTION_REFERENCE = 'workflow-execution-1';

    public function load(ObjectManager $manager): void
    {
        $execution = new WorkflowExecution();
        $execution->setWorkflowRunId('run-123');
        $execution->setTaskId('task-456');
        $execution->setWorkflowId('workflow-789');
        $execution->setBlocking(false);
        $execution->setStatus(WorkflowStatus::COMPLETED);
        $execution->setUserId('user-123');
        $execution->setInputs(['query' => '什么是AI']);
        $execution->setOutputs(['answer' => 'AI是人工智能']);
        $execution->setElapsedTime(5.2);
        $execution->setTotalTokens(150);
        $execution->setStartedAt(new \DateTimeImmutable('2024-01-01 10:00:00'));
        $execution->setFinishedAt(new \DateTimeImmutable('2024-01-01 10:00:05'));

        $manager->persist($execution);
        $manager->flush();

        $this->addReference(self::WORKFLOW_EXECUTION_REFERENCE, $execution);
    }
}
