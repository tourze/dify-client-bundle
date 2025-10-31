<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Exception;

class DifySettingNotFoundException extends DifyException
{
    public function __construct(string $message = 'No active Dify configuration found', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
