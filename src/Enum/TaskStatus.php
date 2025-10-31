<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 任务状态枚举
 *
 * 定义工作流任务状态：pending、running、completed、failed、stopped
 */
enum TaskStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case STOPPED = 'stopped';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '等待中',
            self::RUNNING => '运行中',
            self::COMPLETED => '已完成',
            self::FAILED => '失败',
            self::STOPPED => '已停止',
        };
    }
}
