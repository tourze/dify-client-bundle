<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum RequestTaskStatus: string implements Labelable, Itemable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case TIMEOUT = 'timeout';
    case RETRYING = 'retrying';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待处理',
            self::PROCESSING => '处理中',
            self::COMPLETED => '已完成',
            self::FAILED => '失败',
            self::TIMEOUT => '超时',
            self::RETRYING => '重试中',
        };
    }

    public function getBadgeType(): string
    {
        return match ($this) {
            self::PENDING => 'secondary',
            self::PROCESSING => 'primary',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::TIMEOUT => 'warning',
            self::RETRYING => 'info',
        };
    }

    public function getBadge(): string
    {
        return $this->getLabel();
    }
}
