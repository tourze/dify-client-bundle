<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\WorkflowExecution;
use Tourze\DifyClientBundle\Entity\WorkflowLog;

class WorkflowLogFixtures extends Fixture implements DependentFixtureInterface
{
    public const WORKFLOW_LOG_REFERENCE = 'workflow-log-1';

    public function load(ObjectManager $manager): void
    {
        $execution = $this->getReference(WorkflowExecutionFixtures::WORKFLOW_EXECUTION_REFERENCE, WorkflowExecution::class);

        $log = new WorkflowLog();
        $log->setExecution($execution);
        $log->setNodeId('node-123');
        $log->setNodeType('llm');
        $log->setTitle('大模型执行');
        $log->setMessage('正在处理用户查询...');
        $log->setLevel('info');
        $log->setInputs(['query' => '什么是AI']);
        $log->setOutputs(['answer' => 'AI是人工智能']);
        $log->setElapsedTime(2.5);
        $log->setExecutionMetadata([
            'model' => 'gpt-3.5-turbo',
            'tokens' => 100,
        ]);
        $log->setCreatedAt(new \DateTimeImmutable('2024-01-01 10:00:01'));
        $log->setLoggedAt(new \DateTimeImmutable('2024-01-01 10:00:01'));

        $manager->persist($log);
        $manager->flush();

        $this->addReference(self::WORKFLOW_LOG_REFERENCE, $log);
    }

    /** @return array<class-string<FixtureInterface>> */
    public function getDependencies(): array
    {
        return [WorkflowExecutionFixtures::class];
    }
}
