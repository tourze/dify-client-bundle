<?php

namespace Tourze\DifyClientBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Exception\FailedMessageNotFoundException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(FailedMessageNotFoundException::class)]
final class FailedMessageNotFoundExceptionTest extends AbstractExceptionTestCase
{
}
