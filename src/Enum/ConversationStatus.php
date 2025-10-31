<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ConversationStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case CLOSED = 'closed';
    case ARCHIVED = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => '活跃',
            self::INACTIVE => '非活跃',
            self::CLOSED => '已关闭',
            self::ARCHIVED => '已归档',
        };
    }
}
