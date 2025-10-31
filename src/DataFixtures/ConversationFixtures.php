<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Enum\ConversationStatus;

class ConversationFixtures extends Fixture
{
    public const CONVERSATION_REFERENCE = 'conversation-1';

    public function load(ObjectManager $manager): void
    {
        $conversation = new Conversation();
        $conversation->setUserId('test-user-123');
        $conversation->setConversationId('dify-conv-123');
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $conversation->setMetadata([
            'source' => 'web',
            'user_agent' => 'test-browser',
        ]);

        $manager->persist($conversation);
        $manager->flush();

        $this->addReference(self::CONVERSATION_REFERENCE, $conversation);
    }
}
