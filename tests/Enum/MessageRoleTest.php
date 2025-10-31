<?php

namespace Tourze\DifyClientBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Enum\MessageRole;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(MessageRole::class)]
final class MessageRoleTest extends AbstractEnumTestCase
{
    public function testEnumCases(): void
    {
        $this->assertEquals('user', MessageRole::USER->value);
        $this->assertEquals('assistant', MessageRole::ASSISTANT->value);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('用户', MessageRole::USER->getLabel());
        $this->assertEquals('助手', MessageRole::ASSISTANT->getLabel());
    }

    public function testToArray(): void
    {
        $expected = [
            'user' => '用户',
            'assistant' => '助手',
        ];

        $actual = [];
        foreach (MessageRole::cases() as $case) {
            $actual[$case->value] = $case->getLabel();
        }

        $this->assertEquals($expected, $actual);
    }
}
