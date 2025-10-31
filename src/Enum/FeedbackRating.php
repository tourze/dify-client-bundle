<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 反馈评级枚举
 *
 * 定义消息反馈的评级：like、dislike
 */
enum FeedbackRating: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case LIKE = 'like';
    case DISLIKE = 'dislike';

    public function getLabel(): string
    {
        return match ($this) {
            self::LIKE => '赞',
            self::DISLIKE => '踩',
        };
    }
}
