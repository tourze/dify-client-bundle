<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * DifyRuntimeException 测试
 * @internal
 */
#[CoversClass(DifyRuntimeException::class)]
class DifyRuntimeExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCanBeCreated(): void
    {
        $exception = new DifyRuntimeException('Test message');

        $this->assertInstanceOf(DifyRuntimeException::class, $exception);
        $this->assertInstanceOf(DifyException::class, $exception);
        $this->assertSame('Test message', $exception->getMessage());
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new DifyRuntimeException('Test message', 500, $previous);

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
