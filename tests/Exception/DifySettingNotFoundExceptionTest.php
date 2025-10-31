<?php

namespace Tourze\DifyClientBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Exception\DifySettingNotFoundException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(DifySettingNotFoundException::class)]
final class DifySettingNotFoundExceptionTest extends AbstractExceptionTestCase
{
}
