<?php

namespace Tourze\DifyClientBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Exception\DifyClientException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(DifyClientException::class)]
final class DifyClientExceptionTest extends AbstractExceptionTestCase
{
}
