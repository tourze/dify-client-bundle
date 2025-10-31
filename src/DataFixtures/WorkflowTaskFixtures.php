<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\WorkflowExecution;
use Tourze\DifyClientBundle\Entity\WorkflowTask;
use Tourze\DifyClientBundle\Enum\WorkflowStatus;

class WorkflowTaskFixtures extends Fixture implements DependentFixtureInterface
{
    public const WORKFLOW_TASK_REFERENCE = 'workflow-task-1';

    public function load(ObjectManager $manager): void
    {
        $execution = $this->getReference(WorkflowExecutionFixtures::WORKFLOW_EXECUTION_REFERENCE, WorkflowExecution::class);

        $task = new WorkflowTask();
        $task->setExecution($execution);
        $task->setTaskId('task-123');
        $task->setNodeId('node-456');
        $task->setNodeName('LLM对话节点');
        $task->setNodeType('llm');
        $task->setStepIndex(1);
        $task->setTitle('LLM对话任务');
        $task->setStatus(WorkflowStatus::COMPLETED);
        $task->setInputs(['query' => '什么是AI']);
        $task->setOutputs(['answer' => 'AI是人工智能']);
        $task->setExecutionMetadata([
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
        ]);
        $task->setElapsedTime(3.2);
        $task->setTotalTokens(120);
        $task->setStartedAt(new \DateTimeImmutable('2024-01-01 10:00:00'));
        $task->setFinishedAt(new \DateTimeImmutable('2024-01-01 10:00:03'));

        $manager->persist($task);
        $manager->flush();

        $this->addReference(self::WORKFLOW_TASK_REFERENCE, $task);
    }

    /** @return array<class-string<FixtureInterface>> */
    public function getDependencies(): array
    {
        return [WorkflowExecutionFixtures::class];
    }
}
