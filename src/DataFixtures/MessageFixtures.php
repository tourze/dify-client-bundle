<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Enum\MessageRole;
use Tourze\DifyClientBundle\Enum\MessageStatus;

class MessageFixtures extends Fixture implements DependentFixtureInterface
{
    public const USER_MESSAGE_REFERENCE = 'user-message-1';
    public const ASSISTANT_MESSAGE_REFERENCE = 'assistant-message-1';

    public function load(ObjectManager $manager): void
    {
        /** @var Conversation $conversation */
        $conversation = $this->getReference(ConversationFixtures::CONVERSATION_REFERENCE, Conversation::class);

        // 确保 conversation 已经被持久化并有 ID
        if (null === $conversation->getId() || '' === $conversation->getId()) {
            throw new \RuntimeException('Conversation must be persisted with ID before creating Message');
        }

        $userMessage = new Message();
        $userMessage->setConversation($conversation);
        $userMessage->setRole(MessageRole::USER);
        $userMessage->setContent('Hello, how can you help me?');
        $userMessage->setStatus(MessageStatus::SENT);
        $userMessage->setSentAt(new \DateTimeImmutable());
        $userMessage->setMetadata([
            'user_id' => 'test-user-123',
            'source' => 'web',
        ]);

        $assistantMessage = new Message();
        $assistantMessage->setConversation($conversation);
        $assistantMessage->setRole(MessageRole::ASSISTANT);
        $assistantMessage->setContent('Hello! I can help you with various tasks.');
        $assistantMessage->setStatus(MessageStatus::RECEIVED);
        $assistantMessage->setReceivedAt(new \DateTimeImmutable());
        $assistantMessage->setMetadata([
            'response_time_ms' => 1500,
            'tokens_used' => 25,
        ]);

        $manager->persist($userMessage);
        $manager->persist($assistantMessage);
        $manager->flush();

        $this->addReference(self::USER_MESSAGE_REFERENCE, $userMessage);
        $this->addReference(self::ASSISTANT_MESSAGE_REFERENCE, $assistantMessage);
    }

    public function getDependencies(): array
    {
        return [
            ConversationFixtures::class,
        ];
    }
}
