<?php

namespace Tourze\DifyClientBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Enum\RequestTaskStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(RequestTaskStatus::class)]
final class RequestTaskStatusTest extends AbstractEnumTestCase
{
    public function testEnumCases(): void
    {
        $this->assertEquals('pending', RequestTaskStatus::PENDING->value);
        $this->assertEquals('processing', RequestTaskStatus::PROCESSING->value);
        $this->assertEquals('completed', RequestTaskStatus::COMPLETED->value);
        $this->assertEquals('failed', RequestTaskStatus::FAILED->value);
        $this->assertEquals('timeout', RequestTaskStatus::TIMEOUT->value);
        $this->assertEquals('retrying', RequestTaskStatus::RETRYING->value);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('待处理', RequestTaskStatus::PENDING->getLabel());
        $this->assertEquals('处理中', RequestTaskStatus::PROCESSING->getLabel());
        $this->assertEquals('已完成', RequestTaskStatus::COMPLETED->getLabel());
        $this->assertEquals('失败', RequestTaskStatus::FAILED->getLabel());
        $this->assertEquals('超时', RequestTaskStatus::TIMEOUT->getLabel());
        $this->assertEquals('重试中', RequestTaskStatus::RETRYING->getLabel());
    }

    public function testToArray(): void
    {
        $expected = [
            'pending' => '待处理',
            'processing' => '处理中',
            'completed' => '已完成',
            'failed' => '失败',
            'timeout' => '超时',
            'retrying' => '重试中',
        ];

        $actual = [];
        foreach (RequestTaskStatus::cases() as $case) {
            $actual[$case->value] = $case->getLabel();
        }

        $this->assertEquals($expected, $actual);
    }
}
