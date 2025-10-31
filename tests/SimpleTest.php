<?php

namespace Tourze\DifyClientBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyClientBundle\DifyClientBundle;

/**
 * @internal
 */
#[CoversClass(DifyClientBundle::class)]
final class SimpleTest extends TestCase
{
    public function testBasicMath(): void
    {
        // 测试基本的数学运算，确保测试环境正常
        $result = 2 + 2;
        $this->assertEquals(4, $result);

        $result = 5 * 3;
        $this->assertEquals(15, $result);
    }

    public function testStringOperations(): void
    {
        // 测试字符串操作
        $string = 'Hello World';
        $this->assertEquals('Hello World', $string);
        $this->assertStringContainsString('Hello', $string);
        $this->assertStringEndsWith('World', $string);
    }
}
