<?php

namespace Tourze\DifyClientBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Exception\InvalidConfigurationException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidConfigurationException::class)]
final class InvalidConfigurationExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new InvalidConfigurationException('Test configuration error');

        $this->assertSame('Test configuration error', $exception->getMessage());
    }

    public function testExceptionCode(): void
    {
        $exception = new InvalidConfigurationException('Test', 500);

        $this->assertSame(500, $exception->getCode());
    }
}
