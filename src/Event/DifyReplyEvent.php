<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;

class DifyReplyEvent extends Event
{
    public function __construct(
        private readonly ?Conversation $conversation,
        private readonly string $reply,
        private readonly ?Message $originalMessage,
        private readonly bool $isComplete = true,
    ) {
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function getReply(): string
    {
        return $this->reply;
    }

    public function getOriginalMessage(): ?Message
    {
        return $this->originalMessage;
    }

    public function isComplete(): bool
    {
        return $this->isComplete;
    }
}
