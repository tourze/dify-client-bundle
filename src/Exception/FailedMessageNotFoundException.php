<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Exception;

class FailedMessageNotFoundException extends DifyException
{
    public function __construct(string $failedMessageId, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Failed message with ID %s not found', $failedMessageId), $code, $previous);
    }
}
