<?php

namespace Tourze\DifyClientBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Exception\FailedMessageAlreadyRetriedException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(FailedMessageAlreadyRetriedException::class)]
final class FailedMessageAlreadyRetriedExceptionTest extends AbstractExceptionTestCase
{
}
