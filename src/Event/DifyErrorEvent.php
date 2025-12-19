<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Throwable;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;

final class DifyErrorEvent extends Event
{
    public function __construct(
        private readonly ?Conversation $conversation,
        private readonly ?Message $message,
        private readonly string $errorMessage,
        private readonly ?\Throwable $exception = null,
    ) {
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function getException(): ?\Throwable
    {
        return $this->exception;
    }
}
