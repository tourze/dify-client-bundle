<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Exception;

/**
 * 当实体类型不符合预期时抛出的异常
 */
class EntityTypeException extends DifyException
{
    public static function unexpectedEntityType(string $expected, string $actual): self
    {
        return new self(sprintf('Expected %s entity, got %s', $expected, $actual));
    }
}
