<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\SuggestedQuestion;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(SuggestedQuestion::class)]
final class SuggestedQuestionTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): SuggestedQuestion
    {
        return new SuggestedQuestion();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'question' => ['question', '如何配置API的访问权限？'];
        yield 'sortOrder' => ['sortOrder', 1];
        yield 'relevanceScore' => ['relevanceScore', 0.85];
        yield 'category' => ['category', 'api_configuration'];
        yield 'clickCount' => ['clickCount', 5];
        yield 'visible' => ['visible', false];
        yield 'answer' => ['answer', '您可以在控制台的API设置中配置访问权限。'];
        yield 'userId' => ['userId', 'user-123'];
        yield 'enabled' => ['enabled', false];
    }

    public function testCreateSuggestedQuestionWithDefaultValuesShouldSucceed(): void
    {
        $question = $this->createEntity();

        $this->assertNull($question->getId());
        $this->assertEquals(0, $question->getSortOrder());
        $this->assertNull($question->getRelevanceScore());
        $this->assertNull($question->getCategory());
        $this->assertEquals(0, $question->getClickCount());
        $this->assertTrue($question->isVisible());
        $this->assertNull($question->getMetadata());
        $this->assertNull($question->getAnswer());
        $this->assertNull($question->getUserId());
        $this->assertTrue($question->isEnabled());
        $this->assertNull($question->getTags());
    }

    public function testSetMessageShouldUpdateValue(): void
    {
        $question = $this->createEntity();
        $message = $this->createMock(Message::class);

        $question->setMessage($message);

        $this->assertEquals($message, $question->getMessage());
    }

    public function testSetConversationShouldUpdateValue(): void
    {
        $question = $this->createEntity();
        $conversation = $this->createMock(Conversation::class);

        $question->setConversation($conversation);

        $this->assertEquals($conversation, $question->getConversation());
    }

    public function testSetQuestionShouldUpdateValue(): void
    {
        $question = $this->createEntity();
        $questionText = '如何优化API的性能表现？';

        $question->setQuestion($questionText);

        $this->assertEquals($questionText, $question->getQuestion());
    }

    public function testSetSortOrderShouldUpdateValue(): void
    {
        $question = $this->createEntity();
        $sortOrder = 3;

        $question->setSortOrder($sortOrder);

        $this->assertEquals($sortOrder, $question->getSortOrder());
    }

    public function testSetRelevanceScoreShouldUpdateValue(): void
    {
        $question = $this->createEntity();
        $relevanceScore = 0.92;

        $question->setRelevanceScore($relevanceScore);

        $this->assertEquals($relevanceScore, $question->getRelevanceScore());
    }

    public function testSetRelevanceScoreWithNullShouldAcceptNull(): void
    {
        $question = $this->createEntity();
        $question->setRelevanceScore(0.75);

        $question->setRelevanceScore(null);

        $this->assertNull($question->getRelevanceScore());
    }

    public function testSetCategoryShouldUpdateValue(): void
    {
        $question = $this->createEntity();
        $category = 'performance_optimization';

        $question->setCategory($category);

        $this->assertEquals($category, $question->getCategory());
    }

    public function testSetCategoryWithNullShouldAcceptNull(): void
    {
        $question = $this->createEntity();
        $question->setCategory('original_category');

        $question->setCategory(null);

        $this->assertNull($question->getCategory());
    }

    public function testSetClickCountShouldUpdateValue(): void
    {
        $question = $this->createEntity();
        $clickCount = 15;

        $question->setClickCount($clickCount);

        $this->assertEquals($clickCount, $question->getClickCount());
    }

    public function testSetVisibleShouldUpdateValue(): void
    {
        $question = $this->createEntity();

        $question->setVisible(false);

        $this->assertFalse($question->isVisible());

        $question->setVisible(true);
        $this->assertTrue($question->isVisible());
    }

    public function testSetMetadataShouldUpdateValue(): void
    {
        $question = $this->createEntity();
        $metadata = [
            'generation_model' => 'gpt-4',
            'confidence_level' => 'high',
            'topic_similarity' => 0.88,
            'user_intent' => 'learn_more',
        ];

        $question->setMetadata($metadata);

        $this->assertEquals($metadata, $question->getMetadata());
    }

    public function testSetMetadataWithNullShouldAcceptNull(): void
    {
        $question = $this->createEntity();
        $question->setMetadata(['key' => 'value']);

        $question->setMetadata(null);

        $this->assertNull($question->getMetadata());
    }

    public function testSetAnswerShouldUpdateValue(): void
    {
        $question = $this->createEntity();
        $answer = '您可以通过优化查询语句、使用缓存机制和负载均衡来提升API性能。';

        $question->setAnswer($answer);

        $this->assertEquals($answer, $question->getAnswer());
    }

    public function testSetAnswerWithNullShouldAcceptNull(): void
    {
        $question = $this->createEntity();
        $question->setAnswer('原始答案');

        $question->setAnswer(null);

        $this->assertNull($question->getAnswer());
    }

    public function testSetUserIdShouldUpdateValue(): void
    {
        $question = $this->createEntity();
        $userId = 'user-456';

        $question->setUserId($userId);

        $this->assertEquals($userId, $question->getUserId());
    }

    public function testSetUserIdWithNullShouldAcceptNull(): void
    {
        $question = $this->createEntity();
        $question->setUserId('user-123');

        $question->setUserId(null);

        $this->assertNull($question->getUserId());
    }

    public function testSetEnabledShouldUpdateValue(): void
    {
        $question = $this->createEntity();

        $question->setEnabled(false);

        $this->assertFalse($question->isEnabled());

        $question->setEnabled(true);
        $this->assertTrue($question->isEnabled());
    }

    public function testSetTagsShouldUpdateValue(): void
    {
        $question = $this->createEntity();
        $tags = ['api', 'performance', 'optimization', 'best_practices'];

        $question->setTags($tags);

        $this->assertEquals($tags, $question->getTags());
    }

    public function testSetTagsWithNullShouldAcceptNull(): void
    {
        $question = $this->createEntity();
        $question->setTags(['tag1', 'tag2']);

        $question->setTags(null);

        $this->assertNull($question->getTags());
    }

    public function testSetContentAliasShouldUpdateQuestion(): void
    {
        $question = $this->createEntity();
        $content = '这是通过别名方法设置的问题内容';

        $question->setContent($content);

        $this->assertEquals($content, $question->getQuestion());
    }

    public function testToStringShouldReturnQuestionPreview(): void
    {
        $question = $this->createEntity();
        $questionText = '如何在生产环境中部署和监控API服务？';

        $question->setQuestion($questionText);
        $result = (string) $question;

        $this->assertStringContainsString('建议问题:', $result);
        $this->assertStringContainsString($questionText, $result);
    }

    public function testToStringWithLongQuestionShouldTruncate(): void
    {
        $question = $this->createEntity();
        $longQuestion = '这是一个非常长的建议问题，包含了大量的技术细节和具体的实现方案，用于测试字符串截断功能是否正常工作。这个问题应该被截断。';

        $question->setQuestion($longQuestion);
        $result = (string) $question;

        $this->assertStringContainsString('建议问题:', $result);
        $this->assertStringEndsWith('...', $result);
        $this->assertTrue(mb_strlen($result) < mb_strlen($longQuestion) + 20); // 考虑前缀
    }

    public function testSuggestedQuestionShouldAcceptLongQuestion(): void
    {
        $question = $this->createEntity();
        $longQuestion = str_repeat('这是一个很长的建议问题，包含了详细的技术说明和使用场景。', 50);

        $question->setQuestion($longQuestion);

        $this->assertEquals($longQuestion, $question->getQuestion());
    }

    public function testSuggestedQuestionShouldAcceptLongAnswer(): void
    {
        $question = $this->createEntity();
        $longAnswer = str_repeat('这是一个详细的答案，包含了完整的解决方案和实施步骤。', 100);

        $question->setAnswer($longAnswer);

        $this->assertEquals($longAnswer, $question->getAnswer());
    }

    public function testSuggestedQuestionShouldAcceptExtensiveTags(): void
    {
        $question = $this->createEntity();
        $extensiveTags = [
            'api', 'rest', 'graphql', 'authentication', 'authorization',
            'performance', 'optimization', 'caching', 'security', 'monitoring',
            'deployment', 'scaling', 'best_practices', 'troubleshooting', 'documentation',
        ];

        $question->setTags($extensiveTags);

        $this->assertEquals($extensiveTags, $question->getTags());
    }

    public function testSuggestedQuestionShouldAcceptComplexMetadata(): void
    {
        $question = $this->createEntity();
        $complexMetadata = [
            'generation_info' => [
                'model' => 'gpt-4-turbo',
                'temperature' => 0.7,
                'max_tokens' => 150,
                'generation_time_ms' => 850,
            ],
            'relevance_analysis' => [
                'semantic_similarity' => 0.89,
                'topic_match' => 0.92,
                'intent_alignment' => 0.87,
                'context_relevance' => 0.94,
            ],
            'user_interaction' => [
                'click_through_rate' => 0.25,
                'avg_time_to_click' => 5.2,
                'follow_up_questions' => 3,
                'satisfaction_score' => 4.2,
            ],
            'content_analysis' => [
                'complexity_level' => 'intermediate',
                'estimated_answer_length' => 280,
                'requires_code_example' => true,
                'related_topics' => ['authentication', 'security', 'deployment'],
            ],
            'display_config' => [
                'priority_score' => 85,
                'category_weight' => 1.2,
                'personalization_factor' => 0.95,
                'a_b_test_variant' => 'variant_b',
            ],
        ];

        $question->setMetadata($complexMetadata);

        $this->assertEquals($complexMetadata, $question->getMetadata());
    }
}
