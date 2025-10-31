<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 文件类型枚举
 *
 * 定义支持的文件类型：document、image、audio、video、custom、other
 */
enum FileType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case DOCUMENT = 'document';
    case IMAGE = 'image';
    case AUDIO = 'audio';
    case VIDEO = 'video';
    case CUSTOM = 'custom';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::DOCUMENT => '文档',
            self::IMAGE => '图片',
            self::AUDIO => '音频',
            self::VIDEO => '视频',
            self::CUSTOM => '自定义',
            self::OTHER => '其他',
        };
    }
}
