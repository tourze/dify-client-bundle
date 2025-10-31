<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\MessageFeedback;
use Tourze\DifyClientBundle\Enum\FeedbackRating;

class MessageFeedbackFixtures extends Fixture implements DependentFixtureInterface
{
    public const MESSAGE_FEEDBACK_REFERENCE = 'message-feedback-1';

    public function load(ObjectManager $manager): void
    {
        /** @var Message $message */
        $message = $this->getReference(MessageFixtures::ASSISTANT_MESSAGE_REFERENCE, Message::class);
        /** @var Conversation $conversation */
        $conversation = $this->getReference(ConversationFixtures::CONVERSATION_REFERENCE, Conversation::class);

        $feedback = new MessageFeedback();
        $feedback->setMessage($message);
        $feedback->setConversation($conversation);
        $feedback->setFeedbackId('feedback-123');
        $feedback->setRating(FeedbackRating::LIKE->value);
        $feedback->setComment('回答非常有帮助，谢谢！');
        $feedback->setUserId('user-123');
        $feedback->setSubmittedAt(new \DateTimeImmutable());
        $feedback->setMetadata([
            'source' => 'web',
            'helpful' => true,
        ]);

        $manager->persist($feedback);
        $manager->flush();

        $this->addReference(self::MESSAGE_FEEDBACK_REFERENCE, $feedback);
    }

    /** @return array<class-string<FixtureInterface>> */
    public function getDependencies(): array
    {
        return [MessageFixtures::class, ConversationFixtures::class];
    }
}
