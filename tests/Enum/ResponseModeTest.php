<?php

namespace Tourze\DifyClientBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Enum\ResponseMode;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(ResponseMode::class)]
final class ResponseModeTest extends AbstractEnumTestCase
{
    public function testEnumCases(): void
    {
        $this->assertEquals('streaming', ResponseMode::STREAMING->value);
        $this->assertEquals('blocking', ResponseMode::BLOCKING->value);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('流式响应', ResponseMode::STREAMING->getLabel());
        $this->assertEquals('阻塞响应', ResponseMode::BLOCKING->getLabel());
    }

    public function testToArray(): void
    {
        $expected = [
            'streaming' => '流式响应',
            'blocking' => '阻塞响应',
        ];

        $actual = [];
        foreach (ResponseMode::cases() as $case) {
            $actual[$case->value] = $case->getLabel();
        }

        $this->assertEquals($expected, $actual);
    }
}
