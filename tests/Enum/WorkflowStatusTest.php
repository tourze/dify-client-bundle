<?php

namespace Tourze\DifyClientBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Enum\WorkflowStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(WorkflowStatus::class)]
final class WorkflowStatusTest extends AbstractEnumTestCase
{
    public function testEnumCases(): void
    {
        $this->assertEquals('pending', WorkflowStatus::PENDING->value);
        $this->assertEquals('running', WorkflowStatus::RUNNING->value);
        $this->assertEquals('completed', WorkflowStatus::COMPLETED->value);
        $this->assertEquals('failed', WorkflowStatus::FAILED->value);
        $this->assertEquals('stopped', WorkflowStatus::STOPPED->value);
        $this->assertEquals('timeout', WorkflowStatus::TIMEOUT->value);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('待执行', WorkflowStatus::PENDING->getLabel());
        $this->assertEquals('执行中', WorkflowStatus::RUNNING->getLabel());
        $this->assertEquals('已完成', WorkflowStatus::COMPLETED->getLabel());
        $this->assertEquals('执行失败', WorkflowStatus::FAILED->getLabel());
        $this->assertEquals('已停止', WorkflowStatus::STOPPED->getLabel());
        $this->assertEquals('执行超时', WorkflowStatus::TIMEOUT->getLabel());
    }

    public function testGetName(): void
    {
        $this->assertEquals('pending', WorkflowStatus::PENDING->getName());
        $this->assertEquals('running', WorkflowStatus::RUNNING->getName());
        $this->assertEquals('completed', WorkflowStatus::COMPLETED->getName());
        $this->assertEquals('failed', WorkflowStatus::FAILED->getName());
        $this->assertEquals('stopped', WorkflowStatus::STOPPED->getName());
        $this->assertEquals('timeout', WorkflowStatus::TIMEOUT->getName());
    }

    public function testGetDescription(): void
    {
        $this->assertEquals('待执行', WorkflowStatus::PENDING->getDescription());
        $this->assertEquals('执行中', WorkflowStatus::RUNNING->getDescription());
        $this->assertEquals('已完成', WorkflowStatus::COMPLETED->getDescription());
        $this->assertEquals('执行失败', WorkflowStatus::FAILED->getDescription());
        $this->assertEquals('已停止', WorkflowStatus::STOPPED->getDescription());
        $this->assertEquals('执行超时', WorkflowStatus::TIMEOUT->getDescription());
    }

    public function testToArray(): void
    {
        $expected = [
            'pending' => '待执行',
            'running' => '执行中',
            'completed' => '已完成',
            'failed' => '执行失败',
            'stopped' => '已停止',
            'timeout' => '执行超时',
        ];

        $actual = [];
        foreach (WorkflowStatus::cases() as $case) {
            $actual[$case->value] = $case->getLabel();
        }

        $this->assertEquals($expected, $actual);
    }
}
