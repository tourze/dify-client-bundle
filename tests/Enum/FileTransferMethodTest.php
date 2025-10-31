<?php

namespace Tourze\DifyClientBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Enum\FileTransferMethod;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(FileTransferMethod::class)]
final class FileTransferMethodTest extends AbstractEnumTestCase
{
    public function testEnumCases(): void
    {
        $this->assertEquals('remote_url', FileTransferMethod::REMOTE_URL->value);
        $this->assertEquals('local_file', FileTransferMethod::LOCAL_FILE->value);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('远程URL', FileTransferMethod::REMOTE_URL->getLabel());
        $this->assertEquals('本地文件', FileTransferMethod::LOCAL_FILE->getLabel());
    }

    public function testToArray(): void
    {
        $expected = [
            'remote_url' => '远程URL',
            'local_file' => '本地文件',
        ];

        $actual = [];
        foreach (FileTransferMethod::cases() as $case) {
            $actual[$case->value] = $case->getLabel();
        }

        $this->assertEquals($expected, $actual);
    }
}
