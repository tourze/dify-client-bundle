<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Message;

use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\RequestTask;

final class ProcessDifyMessage
{
    /**
     * @param array<Message> $originalMessages
     */
    public function __construct(
        private readonly RequestTask $requestTask,
        private readonly string $content,
        private readonly array $originalMessages = [],
    ) {
    }

    public function getRequestTask(): RequestTask
    {
        return $this->requestTask;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /** @return array<Message> */
    public function getOriginalMessages(): array
    {
        return $this->originalMessages;
    }
}
