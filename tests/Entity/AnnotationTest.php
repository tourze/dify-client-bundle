<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\Annotation;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Annotation::class)]
final class AnnotationTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): Annotation
    {
        return new Annotation();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'annotationId' => ['annotationId', 'ann-12345'];
        yield 'question' => ['question', '这是一个测试问题？'];
        yield 'answer' => ['answer', '这是一个测试答案。'];
        yield 'userId' => ['userId', 'user-123'];
        yield 'hitCount' => ['hitCount', 10];
        yield 'similarityThreshold' => ['similarityThreshold', 0.85];
        yield 'enabled' => ['enabled', false];
        yield 'lastHitAt' => ['lastHitAt', new \DateTimeImmutable('2024-01-01 10:00:00')];
    }

    public function testCreateAnnotationWithDefaultValuesShouldSucceed(): void
    {
        $annotation = $this->createEntity();

        $this->assertNull($annotation->getId());
        $this->assertNull($annotation->getAnnotationId());
        $this->assertNull($annotation->getMessage());
        $this->assertNull($annotation->getConversation());
        $this->assertNull($annotation->getUserId());
        $this->assertEquals(0, $annotation->getHitCount());
        $this->assertNull($annotation->getSimilarityThreshold());
        $this->assertTrue($annotation->isEnabled());
        $this->assertNull($annotation->getMetadata());
        $this->assertNull($annotation->getLastHitAt());
    }

    public function testSetAnnotationIdShouldUpdateValue(): void
    {
        $annotation = $this->createEntity();
        $annotationId = 'ann-12345';

        $annotation->setAnnotationId($annotationId);

        $this->assertEquals($annotationId, $annotation->getAnnotationId());
    }

    public function testSetAnnotationIdWithNullShouldAcceptNull(): void
    {
        $annotation = $this->createEntity();
        $annotation->setAnnotationId('ann-12345');

        $annotation->setAnnotationId(null);

        $this->assertNull($annotation->getAnnotationId());
    }

    public function testSetQuestionShouldUpdateValue(): void
    {
        $annotation = $this->createEntity();
        $question = '什么是人工智能？';

        $annotation->setQuestion($question);

        $this->assertEquals($question, $annotation->getQuestion());
    }

    public function testSetAnswerShouldUpdateValue(): void
    {
        $annotation = $this->createEntity();
        $answer = '人工智能是模拟人类智能的技术。';

        $annotation->setAnswer($answer);

        $this->assertEquals($answer, $annotation->getAnswer());
    }

    public function testSetMessageShouldUpdateValue(): void
    {
        $annotation = $this->createEntity();
        $message = $this->createMock(Message::class);

        $annotation->setMessage($message);

        $this->assertSame($message, $annotation->getMessage());
    }

    public function testSetMessageWithNullShouldAcceptNull(): void
    {
        $annotation = $this->createEntity();
        $message = $this->createMock(Message::class);
        $annotation->setMessage($message);

        $annotation->setMessage(null);

        $this->assertNull($annotation->getMessage());
    }

    public function testSetConversationShouldUpdateValue(): void
    {
        $annotation = $this->createEntity();
        $conversation = $this->createMock(Conversation::class);

        $annotation->setConversation($conversation);

        $this->assertSame($conversation, $annotation->getConversation());
    }

    public function testSetConversationWithNullShouldAcceptNull(): void
    {
        $annotation = $this->createEntity();
        $conversation = $this->createMock(Conversation::class);
        $annotation->setConversation($conversation);

        $annotation->setConversation(null);

        $this->assertNull($annotation->getConversation());
    }

    public function testSetUserIdShouldUpdateValue(): void
    {
        $annotation = $this->createEntity();
        $userId = 'user-123';

        $annotation->setUserId($userId);

        $this->assertEquals($userId, $annotation->getUserId());
    }

    public function testSetUserIdWithNullShouldAcceptNull(): void
    {
        $annotation = $this->createEntity();
        $annotation->setUserId('user-123');

        $annotation->setUserId(null);

        $this->assertNull($annotation->getUserId());
    }

    public function testSetHitCountShouldUpdateValue(): void
    {
        $annotation = $this->createEntity();
        $hitCount = 15;

        $annotation->setHitCount($hitCount);

        $this->assertEquals($hitCount, $annotation->getHitCount());
    }

    public function testSetSimilarityThresholdShouldUpdateValue(): void
    {
        $annotation = $this->createEntity();
        $threshold = 0.75;

        $annotation->setSimilarityThreshold($threshold);

        $this->assertEquals($threshold, $annotation->getSimilarityThreshold());
    }

    public function testSetSimilarityThresholdWithNullShouldAcceptNull(): void
    {
        $annotation = $this->createEntity();
        $annotation->setSimilarityThreshold(0.85);

        $annotation->setSimilarityThreshold(null);

        $this->assertNull($annotation->getSimilarityThreshold());
    }

    public function testSetEnabledShouldUpdateValue(): void
    {
        $annotation = $this->createEntity();

        $annotation->setEnabled(false);

        $this->assertFalse($annotation->isEnabled());

        $annotation->setEnabled(true);
        $this->assertTrue($annotation->isEnabled());
    }

    public function testSetMetadataShouldUpdateValue(): void
    {
        $annotation = $this->createEntity();
        $metadata = ['source' => 'test', 'domain' => 'ai'];

        $annotation->setMetadata($metadata);

        $this->assertEquals($metadata, $annotation->getMetadata());
    }

    public function testSetMetadataWithNullShouldAcceptNull(): void
    {
        $annotation = $this->createEntity();
        $annotation->setMetadata(['key' => 'value']);

        $annotation->setMetadata(null);

        $this->assertNull($annotation->getMetadata());
    }

    public function testSetLastHitAtShouldUpdateValue(): void
    {
        $annotation = $this->createEntity();
        $lastHitAt = new \DateTimeImmutable('2024-01-01 10:00:00');

        $annotation->setLastHitAt($lastHitAt);

        $this->assertEquals($lastHitAt, $annotation->getLastHitAt());
    }

    public function testSetLastHitAtWithNullShouldAcceptNull(): void
    {
        $annotation = $this->createEntity();
        $annotation->setLastHitAt(new \DateTimeImmutable());

        $annotation->setLastHitAt(null);

        $this->assertNull($annotation->getLastHitAt());
    }

    public function testSetCreateTimeShouldUpdateValue(): void
    {
        $annotation = $this->createEntity();
        $createTime = new \DateTimeImmutable('2024-01-01 10:00:00');

        $annotation->setCreateTime($createTime);

        $this->assertEquals($createTime, $annotation->getCreateTime());
    }

    public function testToStringShouldReturnQuestionPreview(): void
    {
        $annotation = $this->createEntity();
        $question = '什么是人工智能？';
        $annotation->setQuestion($question);

        $result = (string) $annotation;

        $this->assertEquals('标注: ' . $question, $result);
    }

    public function testToStringWithLongQuestionShouldTruncate(): void
    {
        $annotation = $this->createEntity();
        $longQuestion = str_repeat('这是一个很长的问题', 10);
        $annotation->setQuestion($longQuestion);

        $result = (string) $annotation;

        $expectedPreview = mb_substr($longQuestion, 0, 50);
        $this->assertEquals('标注: ' . $expectedPreview . '...', $result);
    }

    public function testAnnotationShouldAcceptLongQuestionAndAnswer(): void
    {
        $annotation = $this->createEntity();
        $longQuestion = str_repeat('这是一个很长的问题内容。', 100);
        $longAnswer = str_repeat('这是一个很长的答案内容。', 100);

        $annotation->setQuestion($longQuestion);
        $annotation->setAnswer($longAnswer);

        $this->assertEquals($longQuestion, $annotation->getQuestion());
        $this->assertEquals($longAnswer, $annotation->getAnswer());
    }
}
