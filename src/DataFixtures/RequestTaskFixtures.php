<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\RequestTask;
use Tourze\DifyClientBundle\Enum\RequestTaskStatus;

class RequestTaskFixtures extends Fixture implements DependentFixtureInterface
{
    public const REQUEST_TASK_REFERENCE = 'request-task-1';

    public function load(ObjectManager $manager): void
    {
        $task = new RequestTask();
        $task->setTaskId('task-123-' . uniqid());
        $task->setStatus(RequestTaskStatus::PENDING);
        $task->setAggregatedContent('Aggregate content for testing');
        $task->setMessageCount(2);
        $task->setMetadata([
            'batch_id' => 'batch-123',
            'priority' => 'normal',
        ]);

        $manager->persist($task);
        $manager->flush();

        $this->addReference(self::REQUEST_TASK_REFERENCE, $task);
    }

    public function getDependencies(): array
    {
        return [
            MessageFixtures::class,
        ];
    }
}
