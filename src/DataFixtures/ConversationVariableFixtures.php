<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\ConversationVariable;

class ConversationVariableFixtures extends Fixture implements DependentFixtureInterface
{
    public const CONVERSATION_VARIABLE_REFERENCE = 'conversation-variable-1';

    public function load(ObjectManager $manager): void
    {
        /** @var Conversation $conversation */
        $conversation = $this->getReference(ConversationFixtures::CONVERSATION_REFERENCE, Conversation::class);

        // 确保 conversation 已经被持久化并有 ID
        if (null === $conversation->getId() || '' === $conversation->getId()) {
            throw new \RuntimeException('Conversation must be persisted with ID before creating ConversationVariable');
        }

        $variable = new ConversationVariable();
        $variable->setConversation($conversation);
        $variable->setName('user_preference');
        $variable->setValue('{"theme": "dark", "language": "zh-CN"}');
        $variable->setType('object');
        $variable->setDescription('用户偏好设置，包含主题和语言');
        $variable->setRequired(false);
        $variable->setConfig([
            'default' => '{"theme": "light", "language": "zh-CN"}',
            'validation' => [
                'theme' => ['light', 'dark'],
                'language' => ['zh-CN', 'en-US'],
            ],
        ]);

        $manager->persist($variable);
        $manager->flush();

        $this->addReference(self::CONVERSATION_VARIABLE_REFERENCE, $variable);
    }

    /** @return array<class-string<FixtureInterface>> */
    public function getDependencies(): array
    {
        return [ConversationFixtures::class];
    }
}
