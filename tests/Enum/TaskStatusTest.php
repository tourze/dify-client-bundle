<?php

namespace Tourze\DifyClientBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Enum\TaskStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(TaskStatus::class)]
final class TaskStatusTest extends AbstractEnumTestCase
{
    public function testEnumCases(): void
    {
        $this->assertEquals('pending', TaskStatus::PENDING->value);
        $this->assertEquals('running', TaskStatus::RUNNING->value);
        $this->assertEquals('completed', TaskStatus::COMPLETED->value);
        $this->assertEquals('failed', TaskStatus::FAILED->value);
        $this->assertEquals('stopped', TaskStatus::STOPPED->value);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('等待中', TaskStatus::PENDING->getLabel());
        $this->assertEquals('运行中', TaskStatus::RUNNING->getLabel());
        $this->assertEquals('已完成', TaskStatus::COMPLETED->getLabel());
        $this->assertEquals('失败', TaskStatus::FAILED->getLabel());
        $this->assertEquals('已停止', TaskStatus::STOPPED->getLabel());
    }

    public function testToArray(): void
    {
        $expected = [
            'pending' => '等待中',
            'running' => '运行中',
            'completed' => '已完成',
            'failed' => '失败',
            'stopped' => '已停止',
        ];

        $actual = [];
        foreach (TaskStatus::cases() as $case) {
            $actual[$case->value] = $case->getLabel();
        }

        $this->assertEquals($expected, $actual);
    }
}
