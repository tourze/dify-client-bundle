<?php

namespace Tourze\DifyClientBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Enum\FeedbackRating;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(FeedbackRating::class)]
final class FeedbackRatingTest extends AbstractEnumTestCase
{
    public function testEnumCases(): void
    {
        $this->assertEquals('like', FeedbackRating::LIKE->value);
        $this->assertEquals('dislike', FeedbackRating::DISLIKE->value);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('赞', FeedbackRating::LIKE->getLabel());
        $this->assertEquals('踩', FeedbackRating::DISLIKE->getLabel());
    }

    public function testToArray(): void
    {
        $expected = [
            'like' => '赞',
            'dislike' => '踩',
        ];

        $actual = [];
        foreach (FeedbackRating::cases() as $case) {
            $actual[$case->value] = $case->getLabel();
        }

        $this->assertEquals($expected, $actual);
    }
}
