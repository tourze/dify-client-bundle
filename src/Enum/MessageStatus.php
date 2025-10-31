<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum MessageStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case SENT = 'sent';
    case RECEIVED = 'received';
    case FAILED = 'failed';
    case AGGREGATED = 'aggregated';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待发送',
            self::SENT => '已发送',
            self::RECEIVED => '已接收',
            self::FAILED => '发送失败',
            self::AGGREGATED => '已聚合',
        };
    }
}
