<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\MessageFeedback;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(MessageFeedback::class)]
final class MessageFeedbackTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): MessageFeedback
    {
        return new MessageFeedback();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'feedbackId' => ['feedbackId', 'feedback-12345'];
        yield 'rating' => ['rating', 'like'];
        yield 'userId' => ['userId', 'user-123'];
        yield 'content' => ['content', '这个回答很有帮助，解决了我的问题。'];
        yield 'processed' => ['processed', true];
    }

    public function testCreateMessageFeedbackWithDefaultValuesShouldSucceed(): void
    {
        $feedback = $this->createEntity();

        $this->assertNull($feedback->getId());
        $this->assertNull($feedback->getFeedbackId());
        $this->assertNull($feedback->getUserId());
        $this->assertNull($feedback->getContent());
        $this->assertNull($feedback->getTags());
        $this->assertNull($feedback->getMetadata());
        $this->assertFalse($feedback->isProcessed());
        $this->assertNull($feedback->getSubmittedAt());
    }

    public function testSetFeedbackIdShouldUpdateValue(): void
    {
        $feedback = $this->createEntity();
        $feedbackId = 'feedback-12345';

        $feedback->setFeedbackId($feedbackId);

        $this->assertEquals($feedbackId, $feedback->getFeedbackId());
    }

    public function testSetFeedbackIdWithNullShouldAcceptNull(): void
    {
        $feedback = $this->createEntity();
        $feedback->setFeedbackId('feedback-123');

        $feedback->setFeedbackId(null);

        $this->assertNull($feedback->getFeedbackId());
    }

    public function testSetMessageShouldUpdateValue(): void
    {
        $feedback = $this->createEntity();
        $message = $this->createMock(Message::class);

        $feedback->setMessage($message);

        $this->assertEquals($message, $feedback->getMessage());
    }

    public function testSetConversationShouldUpdateValue(): void
    {
        $feedback = $this->createEntity();
        $conversation = $this->createMock(Conversation::class);

        $feedback->setConversation($conversation);

        $this->assertEquals($conversation, $feedback->getConversation());
    }

    public function testSetRatingShouldUpdateValue(): void
    {
        $feedback = $this->createEntity();
        $rating = 'like';

        $feedback->setRating($rating);

        $this->assertEquals($rating, $feedback->getRating());
    }

    #[TestWith(['like'], 'like')]
    #[TestWith(['dislike'], 'dislike')]
    public function testSetRatingWithValidValuesShouldSucceed(string $rating): void
    {
        $feedback = $this->createEntity();

        $feedback->setRating($rating);

        $this->assertEquals($rating, $feedback->getRating());
    }

    public function testSetUserIdShouldUpdateValue(): void
    {
        $feedback = $this->createEntity();
        $userId = 'user-456';

        $feedback->setUserId($userId);

        $this->assertEquals($userId, $feedback->getUserId());
    }

    public function testSetUserIdWithNullShouldAcceptNull(): void
    {
        $feedback = $this->createEntity();
        $feedback->setUserId('user-123');

        $feedback->setUserId(null);

        $this->assertNull($feedback->getUserId());
    }

    public function testSetContentShouldUpdateValue(): void
    {
        $feedback = $this->createEntity();
        $content = '这个回答非常准确，提供了我需要的所有信息。';

        $feedback->setContent($content);

        $this->assertEquals($content, $feedback->getContent());
    }

    public function testSetContentWithNullShouldAcceptNull(): void
    {
        $feedback = $this->createEntity();
        $feedback->setContent('原始内容');

        $feedback->setContent(null);

        $this->assertNull($feedback->getContent());
    }

    public function testSetTagsShouldUpdateValue(): void
    {
        $feedback = $this->createEntity();
        $tags = ['响应速度', '内容准确性', '帮助性'];

        $feedback->setTags($tags);

        $this->assertEquals($tags, $feedback->getTags());
    }

    public function testSetTagsWithNullShouldAcceptNull(): void
    {
        $feedback = $this->createEntity();
        $feedback->setTags(['tag1', 'tag2']);

        $feedback->setTags(null);

        $this->assertNull($feedback->getTags());
    }

    public function testSetMetadataShouldUpdateValue(): void
    {
        $feedback = $this->createEntity();
        $metadata = [
            'source' => 'web_interface',
            'device_type' => 'desktop',
            'session_duration' => 150,
            'previous_interactions' => 3,
        ];

        $feedback->setMetadata($metadata);

        $this->assertEquals($metadata, $feedback->getMetadata());
    }

    public function testSetMetadataWithNullShouldAcceptNull(): void
    {
        $feedback = $this->createEntity();
        $feedback->setMetadata(['key' => 'value']);

        $feedback->setMetadata(null);

        $this->assertNull($feedback->getMetadata());
    }

    public function testSetProcessedShouldUpdateValue(): void
    {
        $feedback = $this->createEntity();

        $feedback->setProcessed(true);

        $this->assertTrue($feedback->isProcessed());

        $feedback->setProcessed(false);
        $this->assertFalse($feedback->isProcessed());
    }

    public function testSetSubmittedAtShouldUpdateValue(): void
    {
        $feedback = $this->createEntity();
        $submittedAt = new \DateTimeImmutable('2024-01-15 14:30:00');

        $feedback->setSubmittedAt($submittedAt);

        $this->assertEquals($submittedAt, $feedback->getSubmittedAt());
    }

    public function testSetSubmittedAtWithNullShouldAcceptNull(): void
    {
        $feedback = $this->createEntity();
        $submittedAt = new \DateTimeImmutable('2024-01-15 14:30:00');
        $feedback->setSubmittedAt($submittedAt);

        $feedback->setSubmittedAt(null);

        $this->assertNull($feedback->getSubmittedAt());
    }

    public function testIsPositiveShouldReturnCorrectValue(): void
    {
        $feedback = $this->createEntity();

        $feedback->setRating('like');
        $this->assertTrue($feedback->isPositive());
        $this->assertFalse($feedback->isNegative());

        $feedback->setRating('dislike');
        $this->assertFalse($feedback->isPositive());
        $this->assertTrue($feedback->isNegative());
    }

    public function testIsNegativeShouldReturnCorrectValue(): void
    {
        $feedback = $this->createEntity();

        $feedback->setRating('dislike');
        $this->assertTrue($feedback->isNegative());
        $this->assertFalse($feedback->isPositive());

        $feedback->setRating('like');
        $this->assertFalse($feedback->isNegative());
        $this->assertTrue($feedback->isPositive());
    }

    public function testSetCreateTimeShouldUpdateValue(): void
    {
        $feedback = $this->createEntity();
        $createTime = new \DateTimeImmutable('2024-01-15 14:00:00');

        $feedback->setCreateTime($createTime);

        $this->assertEquals($createTime, $feedback->getCreateTime());
    }

    public function testSetUpdatedAtShouldUpdateValue(): void
    {
        $feedback = $this->createEntity();
        $updatedAt = new \DateTimeImmutable('2024-01-15 15:00:00');

        $feedback->setUpdatedAt($updatedAt);

        $this->assertEquals($updatedAt, $feedback->getUpdateTime());
    }

    public function testSetCommentAliasShouldUpdateContent(): void
    {
        $feedback = $this->createEntity();
        $comment = '这是通过别名方法设置的评论内容';

        $feedback->setComment($comment);

        $this->assertEquals($comment, $feedback->getContent());
    }

    public function testToStringShouldReturnRatingAndUserId(): void
    {
        $feedback = $this->createEntity();
        $feedback->setRating('like');
        $feedback->setUserId('user-123');

        $result = (string) $feedback;

        $this->assertStringContainsString('点赞反馈', $result);
        $this->assertStringContainsString('user-123', $result);

        $feedback->setRating('dislike');
        $result = (string) $feedback;
        $this->assertStringContainsString('点踩反馈', $result);
    }

    public function testToStringWithAnonymousUserShouldShowAnonymous(): void
    {
        $feedback = $this->createEntity();
        $feedback->setRating('like');
        $feedback->setUserId(null);

        $result = (string) $feedback;

        $this->assertStringContainsString('点赞反馈', $result);
        $this->assertStringContainsString('匿名', $result);
    }

    public function testMessageFeedbackShouldAcceptLongContent(): void
    {
        $feedback = $this->createEntity();
        $longContent = str_repeat('这是一个很长的反馈内容，用户提供了详细的意见和建议。', 100);

        $feedback->setContent($longContent);

        $this->assertEquals($longContent, $feedback->getContent());
    }

    public function testMessageFeedbackShouldAcceptExtensiveTags(): void
    {
        $feedback = $this->createEntity();
        $extensiveTags = [
            '响应速度', '内容准确性', '帮助性', '完整性', '清晰度',
            '相关性', '创新性', '实用性', '友好性', '专业性',
            '时效性', '深度分析', '易理解', '有条理', '详细',
        ];

        $feedback->setTags($extensiveTags);

        $this->assertEquals($extensiveTags, $feedback->getTags());
    }

    public function testMessageFeedbackShouldAcceptComplexMetadata(): void
    {
        $feedback = $this->createEntity();
        $complexMetadata = [
            'session_info' => [
                'session_id' => 'session-12345',
                'duration_seconds' => 180,
                'interaction_count' => 5,
                'first_visit' => false,
            ],
            'user_context' => [
                'user_type' => 'premium',
                'subscription_level' => 'pro',
                'account_age_days' => 365,
                'previous_feedback_count' => 12,
            ],
            'technical_info' => [
                'browser' => 'Chrome 120.0',
                'device_type' => 'desktop',
                'screen_resolution' => '1920x1080',
                'connection_type' => 'wifi',
            ],
            'content_analysis' => [
                'query_complexity' => 'medium',
                'response_length' => 450,
                'response_time_ms' => 1250,
                'confidence_score' => 0.92,
            ],
            'feedback_context' => [
                'trigger_event' => 'completion_prompt',
                'time_since_response' => 30,
                'page_scroll_percentage' => 75,
                'copy_paste_actions' => 2,
            ],
        ];

        $feedback->setMetadata($complexMetadata);

        $this->assertEquals($complexMetadata, $feedback->getMetadata());
    }
}
