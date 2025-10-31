<?php

namespace Tourze\DifyClientBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Enum\MessageStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(MessageStatus::class)]
final class MessageStatusTest extends AbstractEnumTestCase
{
    public function testEnumCases(): void
    {
        $this->assertEquals('pending', MessageStatus::PENDING->value);
        $this->assertEquals('sent', MessageStatus::SENT->value);
        $this->assertEquals('received', MessageStatus::RECEIVED->value);
        $this->assertEquals('failed', MessageStatus::FAILED->value);
        $this->assertEquals('aggregated', MessageStatus::AGGREGATED->value);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('待发送', MessageStatus::PENDING->getLabel());
        $this->assertEquals('已发送', MessageStatus::SENT->getLabel());
        $this->assertEquals('已接收', MessageStatus::RECEIVED->getLabel());
        $this->assertEquals('发送失败', MessageStatus::FAILED->getLabel());
        $this->assertEquals('已聚合', MessageStatus::AGGREGATED->getLabel());
    }

    public function testToArray(): void
    {
        $expected = [
            'pending' => '待发送',
            'sent' => '已发送',
            'received' => '已接收',
            'failed' => '发送失败',
            'aggregated' => '已聚合',
        ];

        $actual = [];
        foreach (MessageStatus::cases() as $case) {
            $actual[$case->value] = $case->getLabel();
        }

        $this->assertEquals($expected, $actual);
    }
}
