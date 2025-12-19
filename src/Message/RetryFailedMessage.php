<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Message;

final class RetryFailedMessage
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private readonly string $failedMessageId,
        private readonly ?string $taskId = null,
        private readonly array $context = [],
        private readonly bool $retryWholeBatch = false,
    ) {
    }

    public function getFailedMessageId(): string
    {
        return $this->failedMessageId;
    }

    public function getTaskId(): ?string
    {
        return $this->taskId;
    }

    /** @return array<string, mixed> */
    public function getContext(): array
    {
        return $this->context;
    }

    public function shouldRetryWholeBatch(): bool
    {
        return $this->retryWholeBatch;
    }
}
