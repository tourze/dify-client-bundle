<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 响应模式枚举
 *
 * streaming: 流式响应（推荐），基于 SSE
 * blocking: 阻塞响应，等待执行完毕后返回（Cloudflare 100秒超时限制）
 */
enum ResponseMode: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case STREAMING = 'streaming';
    case BLOCKING = 'blocking';

    public function getLabel(): string
    {
        return match ($this) {
            self::STREAMING => '流式响应',
            self::BLOCKING => '阻塞响应',
        };
    }
}
