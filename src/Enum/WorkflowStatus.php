<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 工作流状态枚举
 *
 * 工作流执行的状态管理
 */
enum WorkflowStatus: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case STOPPED = 'stopped';
    case TIMEOUT = 'timeout';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待执行',
            self::RUNNING => '执行中',
            self::COMPLETED => '已完成',
            self::FAILED => '执行失败',
            self::STOPPED => '已停止',
            self::TIMEOUT => '执行超时',
        };
    }

    public function getName(): string
    {
        return $this->value;
    }

    public function getDescription(): string
    {
        return $this->getLabel();
    }
}
