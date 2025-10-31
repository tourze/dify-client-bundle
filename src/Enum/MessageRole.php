<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum MessageRole: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case USER = 'user';
    case ASSISTANT = 'assistant';

    public function getLabel(): string
    {
        return match ($this) {
            self::USER => '用户',
            self::ASSISTANT => '助手',
        };
    }
}
