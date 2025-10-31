<?php

namespace Tourze\DifyClientBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Exception\EntityTypeException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(EntityTypeException::class)]
final class EntityTypeExceptionTest extends AbstractExceptionTestCase
{
    public function testUnexpectedEntityType(): void
    {
        $exception = EntityTypeException::unexpectedEntityType('User', 'Product');

        $this->assertInstanceOf(EntityTypeException::class, $exception);
        $this->assertSame('Expected User entity, got Product', $exception->getMessage());
    }
}
