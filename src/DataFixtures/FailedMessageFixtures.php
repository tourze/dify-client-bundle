<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\FailedMessage;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\RequestTask;

class FailedMessageFixtures extends Fixture implements DependentFixtureInterface
{
    public const FAILED_MESSAGE_REFERENCE = 'failed-message-1';

    public function load(ObjectManager $manager): void
    {
        $conversation = $this->getReference(ConversationFixtures::CONVERSATION_REFERENCE, Conversation::class);
        $message = $this->getReference(MessageFixtures::USER_MESSAGE_REFERENCE, Message::class);
        $requestTask = $this->getReference(RequestTaskFixtures::REQUEST_TASK_REFERENCE, RequestTask::class);

        $failedMessage = new FailedMessage();
        $failedMessage->setConversation($conversation);
        $failedMessage->setMessage($message);
        $failedMessage->setRequestTask($requestTask);
        $failedMessage->setError('Connection timeout to Dify API');
        $failedMessage->setAttempts(1);
        $failedMessage->setRetried(false);
        $failedMessage->setFailedAt(new \DateTimeImmutable());
        $failedMessage->setContext([
            'error_type' => 'timeout',
            'api_endpoint' => 'https://api.dify.ai/chat-messages',
            'timeout_duration' => 30,
        ]);
        $failedMessage->addRetryAttempt(new \DateTimeImmutable(), 'initial_failure');

        $manager->persist($failedMessage);
        $manager->flush();

        $this->addReference(self::FAILED_MESSAGE_REFERENCE, $failedMessage);
    }

    public function getDependencies(): array
    {
        return [
            ConversationFixtures::class,
            MessageFixtures::class,
            RequestTaskFixtures::class,
        ];
    }
}
