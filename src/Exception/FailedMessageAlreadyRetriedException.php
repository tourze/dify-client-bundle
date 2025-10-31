<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Exception;

class FailedMessageAlreadyRetriedException extends DifyException
{
    public function __construct(string $failedMessageId, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Failed message %s has already been retried', $failedMessageId), $code, $previous);
    }
}
