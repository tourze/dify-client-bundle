<?php

namespace Tourze\DifyClientBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Enum\FileType;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(FileType::class)]
final class FileTypeTest extends AbstractEnumTestCase
{
    public function testEnumCases(): void
    {
        $this->assertEquals('document', FileType::DOCUMENT->value);
        $this->assertEquals('image', FileType::IMAGE->value);
        $this->assertEquals('audio', FileType::AUDIO->value);
        $this->assertEquals('video', FileType::VIDEO->value);
        $this->assertEquals('custom', FileType::CUSTOM->value);
        $this->assertEquals('other', FileType::OTHER->value);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('文档', FileType::DOCUMENT->getLabel());
        $this->assertEquals('图片', FileType::IMAGE->getLabel());
        $this->assertEquals('音频', FileType::AUDIO->getLabel());
        $this->assertEquals('视频', FileType::VIDEO->getLabel());
        $this->assertEquals('自定义', FileType::CUSTOM->getLabel());
        $this->assertEquals('其他', FileType::OTHER->getLabel());
    }

    public function testToArray(): void
    {
        $expected = [
            'document' => '文档',
            'image' => '图片',
            'audio' => '音频',
            'video' => '视频',
            'custom' => '自定义',
            'other' => '其他',
        ];

        $actual = [];
        foreach (FileType::cases() as $case) {
            $actual[$case->value] = $case->getLabel();
        }

        $this->assertEquals($expected, $actual);
    }
}
