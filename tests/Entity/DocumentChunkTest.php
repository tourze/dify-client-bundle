<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\Document;
use Tourze\DifyClientBundle\Entity\DocumentChunk;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(DocumentChunk::class)]
final class DocumentChunkTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): DocumentChunk
    {
        return new DocumentChunk();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'segmentId' => ['segmentId', 'segment-12345'];
        yield 'position' => ['position', 1];
        yield 'content' => ['content', '这是文档分块的内容，包含重要的技术信息。'];
        yield 'characterCount' => ['characterCount', 250];
        yield 'wordCount' => ['wordCount', 45];
        yield 'tokenCount' => ['tokenCount', 60];
        yield 'hash' => ['hash', 'abc123def456'];
        yield 'enabled' => ['enabled', false];
        yield 'createdBy' => ['createdBy', 'user-123'];
        yield 'hitCount' => ['hitCount', 5];
    }

    public function testCreateDocumentChunkWithDefaultValuesShouldSucceed(): void
    {
        $chunk = $this->createEntity();

        $this->assertNull($chunk->getId());
        $this->assertNull($chunk->getDocument());
        $this->assertNull($chunk->getHash());
        $this->assertTrue($chunk->isEnabled());
        $this->assertNull($chunk->getCreatedBy());
        $this->assertEquals(0, $chunk->getHitCount());
        $this->assertNull($chunk->getMetadata());
        $this->assertNull($chunk->getIndexedAt());
        $this->assertNull($chunk->getLastHitAt());
    }

    public function testSetSegmentIdShouldUpdateValue(): void
    {
        $chunk = $this->createEntity();
        $segmentId = 'segment-12345';

        $chunk->setSegmentId($segmentId);

        $this->assertEquals($segmentId, $chunk->getSegmentId());
    }

    public function testSetDocumentShouldUpdateValue(): void
    {
        $chunk = $this->createEntity();
        $document = $this->createMock(Document::class);

        $chunk->setDocument($document);

        $this->assertEquals($document, $chunk->getDocument());
    }

    public function testSetDocumentWithNullShouldAcceptNull(): void
    {
        $chunk = $this->createEntity();
        $document = $this->createMock(Document::class);
        $chunk->setDocument($document);

        $chunk->setDocument(null);

        $this->assertNull($chunk->getDocument());
    }

    public function testSetPositionShouldUpdateValue(): void
    {
        $chunk = $this->createEntity();
        $position = 5;

        $chunk->setPosition($position);

        $this->assertEquals($position, $chunk->getPosition());
    }

    public function testSetContentShouldUpdateValue(): void
    {
        $chunk = $this->createEntity();
        $content = '这是一个文档分块的内容，包含了重要的技术信息和说明。';

        $chunk->setContent($content);

        $this->assertEquals($content, $chunk->getContent());
    }

    public function testSetCharacterCountShouldUpdateValue(): void
    {
        $chunk = $this->createEntity();
        $characterCount = 1500;

        $chunk->setCharacterCount($characterCount);

        $this->assertEquals($characterCount, $chunk->getCharacterCount());
    }

    public function testSetWordCountShouldUpdateValue(): void
    {
        $chunk = $this->createEntity();
        $wordCount = 250;

        $chunk->setWordCount($wordCount);

        $this->assertEquals($wordCount, $chunk->getWordCount());
    }

    public function testSetTokenCountShouldUpdateValue(): void
    {
        $chunk = $this->createEntity();
        $tokenCount = 200;

        $chunk->setTokenCount($tokenCount);

        $this->assertEquals($tokenCount, $chunk->getTokenCount());
    }

    public function testSetHashShouldUpdateValue(): void
    {
        $chunk = $this->createEntity();
        $hash = 'abc123def456789';

        $chunk->setHash($hash);

        $this->assertEquals($hash, $chunk->getHash());
    }

    public function testSetHashWithNullShouldAcceptNull(): void
    {
        $chunk = $this->createEntity();
        $chunk->setHash('original-hash');

        $chunk->setHash(null);

        $this->assertNull($chunk->getHash());
    }

    public function testSetEnabledShouldUpdateValue(): void
    {
        $chunk = $this->createEntity();

        $chunk->setEnabled(false);

        $this->assertFalse($chunk->isEnabled());

        $chunk->setEnabled(true);
        $this->assertTrue($chunk->isEnabled());
    }

    public function testSetCreatedByShouldUpdateValue(): void
    {
        $chunk = $this->createEntity();
        $createdBy = 'user-456';

        $chunk->setCreatedBy($createdBy);

        $this->assertEquals($createdBy, $chunk->getCreatedBy());
    }

    public function testSetCreatedByWithNullShouldAcceptNull(): void
    {
        $chunk = $this->createEntity();
        $chunk->setCreatedBy('user-123');

        $chunk->setCreatedBy(null);

        $this->assertNull($chunk->getCreatedBy());
    }

    public function testSetHitCountShouldUpdateValue(): void
    {
        $chunk = $this->createEntity();
        $hitCount = 15;

        $chunk->setHitCount($hitCount);

        $this->assertEquals($hitCount, $chunk->getHitCount());
    }

    public function testSetMetadataShouldUpdateValue(): void
    {
        $chunk = $this->createEntity();
        $metadata = [
            'keywords' => ['API', '接口', '文档'],
            'summary' => '这是关于API接口的技术文档分块',
            'importance' => 'high',
            'topic' => 'authentication',
        ];

        $chunk->setMetadata($metadata);

        $this->assertEquals($metadata, $chunk->getMetadata());
    }

    public function testSetMetadataWithNullShouldAcceptNull(): void
    {
        $chunk = $this->createEntity();
        $chunk->setMetadata(['key' => 'value']);

        $chunk->setMetadata(null);

        $this->assertNull($chunk->getMetadata());
    }

    public function testSetIndexedAtShouldUpdateValue(): void
    {
        $chunk = $this->createEntity();
        $indexedAt = new \DateTimeImmutable('2024-01-01 10:00:00');

        $chunk->setIndexedAt($indexedAt);

        $this->assertEquals($indexedAt, $chunk->getIndexedAt());
    }

    public function testSetIndexedAtWithNullShouldAcceptNull(): void
    {
        $chunk = $this->createEntity();
        $indexedAt = new \DateTimeImmutable('2024-01-01 10:00:00');
        $chunk->setIndexedAt($indexedAt);

        $chunk->setIndexedAt(null);

        $this->assertNull($chunk->getIndexedAt());
    }

    public function testSetLastHitAtShouldUpdateValue(): void
    {
        $chunk = $this->createEntity();
        $lastHitAt = new \DateTimeImmutable('2024-01-15 14:30:00');

        $chunk->setLastHitAt($lastHitAt);

        $this->assertEquals($lastHitAt, $chunk->getLastHitAt());
    }

    public function testSetLastHitAtWithNullShouldAcceptNull(): void
    {
        $chunk = $this->createEntity();
        $lastHitAt = new \DateTimeImmutable('2024-01-15 14:30:00');
        $chunk->setLastHitAt($lastHitAt);

        $chunk->setLastHitAt(null);

        $this->assertNull($chunk->getLastHitAt());
    }

    public function testGetContentPreviewShouldReturnTruncatedContent(): void
    {
        $chunk = $this->createEntity();
        $longContent = '这是一个很长的文档分块内容，包含了大量的技术信息和详细的说明文档，用于测试内容预览功能是否正常工作。';
        $chunk->setContent($longContent);

        $preview = $chunk->getContentPreview(20);

        $this->assertEquals('这是一个很长的文档分块内容，包含了大量的...', $preview);
    }

    public function testGetContentPreviewWithShortContentShouldReturnFullContent(): void
    {
        $chunk = $this->createEntity();
        $shortContent = '短内容';
        $chunk->setContent($shortContent);

        $preview = $chunk->getContentPreview(100);

        $this->assertEquals('短内容', $preview);
    }

    public function testGetContentPreviewWithDefaultLengthShouldUse100Characters(): void
    {
        $chunk = $this->createEntity();
        $longContent = str_repeat('这是测试内容。', 20); // 创建超过100字符的内容
        $chunk->setContent($longContent);

        $preview = $chunk->getContentPreview();

        $this->assertTrue(mb_strlen($preview) <= 103); // 100字符 + "..."
        $this->assertStringEndsWith('...', $preview);
    }

    public function testToStringShouldReturnPositionAndPreview(): void
    {
        $chunk = $this->createEntity();
        $chunk->setPosition(3);
        $chunk->setContent('这是一个很长的文档分块内容，用于测试toString方法的输出格式是否正确。');

        $result = (string) $chunk;

        $this->assertEquals('分块 #3: 这是一个很长的文档分块内容，用于测试toString方法的输出格式是否正确。', $result);
    }

    public function testDocumentChunkShouldAcceptLongContent(): void
    {
        $chunk = $this->createEntity();
        $longContent = str_repeat('这是一个很长的文档分块内容，包含了详细的技术说明和示例代码。', 100);

        $chunk->setContent($longContent);

        $this->assertEquals($longContent, $chunk->getContent());
    }

    public function testDocumentChunkShouldAcceptComplexMetadata(): void
    {
        $chunk = $this->createEntity();
        $complexMetadata = [
            'keywords' => ['API', 'REST', 'authentication', 'authorization'],
            'summary' => '关于REST API认证和授权的技术文档分块',
            'topics' => ['security', 'web development', 'backend'],
            'difficulty' => 'intermediate',
            'estimated_reading_time' => 3,
            'code_examples' => [
                'language' => 'php',
                'count' => 2,
                'complexity' => 'medium',
            ],
            'related_chunks' => ['segment-001', 'segment-003'],
            'version' => '2.1.0',
            'last_updated' => '2024-01-15T10:30:00Z',
            'quality_score' => 8.5,
        ];

        $chunk->setMetadata($complexMetadata);

        $this->assertEquals($complexMetadata, $chunk->getMetadata());
    }
}
