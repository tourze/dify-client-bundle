<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 文件传输方式枚举
 *
 * remote_url: 远程URL方式，用于图片URL
 * local_file: 本地文件方式，用于文件上传
 */
enum FileTransferMethod: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case REMOTE_URL = 'remote_url';
    case LOCAL_FILE = 'local_file';

    public function getLabel(): string
    {
        return match ($this) {
            self::REMOTE_URL => '远程URL',
            self::LOCAL_FILE => '本地文件',
        };
    }
}
