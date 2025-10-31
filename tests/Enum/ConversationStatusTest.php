<?php

namespace Tourze\DifyClientBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Enum\ConversationStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(ConversationStatus::class)]
final class ConversationStatusTest extends AbstractEnumTestCase
{
    public function testEnumCases(): void
    {
        $this->assertEquals('active', ConversationStatus::ACTIVE->value);
        $this->assertEquals('inactive', ConversationStatus::INACTIVE->value);
        $this->assertEquals('closed', ConversationStatus::CLOSED->value);
        $this->assertEquals('archived', ConversationStatus::ARCHIVED->value);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('活跃', ConversationStatus::ACTIVE->getLabel());
        $this->assertEquals('非活跃', ConversationStatus::INACTIVE->getLabel());
        $this->assertEquals('已关闭', ConversationStatus::CLOSED->getLabel());
        $this->assertEquals('已归档', ConversationStatus::ARCHIVED->getLabel());
    }

    public function testToArray(): void
    {
        $expected = [
            'active' => '活跃',
            'inactive' => '非活跃',
            'closed' => '已关闭',
            'archived' => '已归档',
        ];

        $actual = [];
        foreach (ConversationStatus::cases() as $case) {
            $actual[$case->value] = $case->getLabel();
        }

        $this->assertEquals($expected, $actual);
    }
}
