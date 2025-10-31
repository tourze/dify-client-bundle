<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\SuggestedQuestion;

class SuggestedQuestionFixtures extends Fixture implements DependentFixtureInterface
{
    public const SUGGESTED_QUESTION_REFERENCE = 'suggested-question-1';

    public function load(ObjectManager $manager): void
    {
        /** @var Message $message */
        $message = $this->getReference(MessageFixtures::ASSISTANT_MESSAGE_REFERENCE, Message::class);
        /** @var Conversation $conversation */
        $conversation = $this->getReference(ConversationFixtures::CONVERSATION_REFERENCE, Conversation::class);

        $question = new SuggestedQuestion();
        $question->setMessage($message);
        $question->setConversation($conversation);
        $question->setContent('什么是人工智能？');
        $question->setAnswer('人工智能（AI）是计算机科学的一个分支...');
        $question->setUserId('user-123');
        $question->setCategory('AI基础');
        $question->setSortOrder(1);
        $question->setEnabled(true);
        $question->setClickCount(25);
        $question->setTags(['AI', '基础', '入门']);
        $question->setMetadata([
            'difficulty' => 'beginner',
            'language' => 'zh-CN',
        ]);

        $manager->persist($question);
        $manager->flush();

        $this->addReference(self::SUGGESTED_QUESTION_REFERENCE, $question);
    }

    public function getDependencies(): array
    {
        return [
            MessageFixtures::class,
            ConversationFixtures::class,
        ];
    }
}
