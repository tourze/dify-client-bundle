<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\RetrieverResource;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(RetrieverResource::class)]
final class RetrieverResourceTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): RetrieverResource
    {
        return new RetrieverResource();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'query' => ['query', '用户查询的问题'];
        yield 'position' => ['position', 1];
        yield 'datasetId' => ['datasetId', 'dataset-12345'];
        yield 'datasetName' => ['datasetName', 'API文档数据集'];
        yield 'documentId' => ['documentId', 'doc-12345'];
        yield 'documentName' => ['documentName', 'REST API 指南'];
        yield 'segmentId' => ['segmentId', 'segment-12345'];
        yield 'score' => ['score', 0.85];
        yield 'content' => ['content', '这是检索到的相关内容，包含了用户查询问题的答案。'];
        yield 'sourceUrl' => ['sourceUrl', 'https://example.com/docs/api-guide.pdf'];
        yield 'resourceId' => ['resourceId', 'resource-12345'];
        yield 'resourceType' => ['resourceType', 'document'];
        yield 'resourceUrl' => ['resourceUrl', 'https://example.com/resources/doc.pdf'];
        yield 'title' => ['title', 'API认证指南'];
    }

    public function testCreateRetrieverResourceWithDefaultValuesShouldSucceed(): void
    {
        $resource = $this->createEntity();

        $this->assertNull($resource->getId());
        $this->assertNull($resource->getMessage());
        $this->assertNull($resource->getConversation());
        $this->assertNull($resource->getDataset());
        $this->assertNull($resource->getQuery());
        $this->assertNull($resource->getSourceUrl());
        $this->assertNull($resource->getMetadata());
        $this->assertNull($resource->getRetrievedAt());
        $this->assertNull($resource->getResourceId());
        $this->assertNull($resource->getResourceType());
        $this->assertNull($resource->getResourceUrl());
        $this->assertNull($resource->getTitle());
    }

    public function testSetMessageShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $message = $this->createMock(Message::class);

        $resource->setMessage($message);

        $this->assertEquals($message, $resource->getMessage());
    }

    public function testSetMessageWithNullShouldAcceptNull(): void
    {
        $resource = $this->createEntity();
        $message = $this->createMock(Message::class);
        $resource->setMessage($message);

        $resource->setMessage(null);

        $this->assertNull($resource->getMessage());
    }

    public function testSetConversationShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $conversation = $this->createMock(Conversation::class);

        $resource->setConversation($conversation);

        $this->assertEquals($conversation, $resource->getConversation());
    }

    public function testSetConversationWithNullShouldAcceptNull(): void
    {
        $resource = $this->createEntity();
        $conversation = $this->createMock(Conversation::class);
        $resource->setConversation($conversation);

        $resource->setConversation(null);

        $this->assertNull($resource->getConversation());
    }

    public function testSetDatasetShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $dataset = $this->createMock(Dataset::class);

        $resource->setDataset($dataset);

        $this->assertEquals($dataset, $resource->getDataset());
    }

    public function testSetDatasetWithNullShouldAcceptNull(): void
    {
        $resource = $this->createEntity();
        $dataset = $this->createMock(Dataset::class);
        $resource->setDataset($dataset);

        $resource->setDataset(null);

        $this->assertNull($resource->getDataset());
    }

    public function testSetQueryShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $query = '如何使用REST API进行身份验证？';

        $resource->setQuery($query);

        $this->assertEquals($query, $resource->getQuery());
    }

    public function testSetQueryWithNullShouldAcceptNull(): void
    {
        $resource = $this->createEntity();
        $resource->setQuery('原始查询');

        $resource->setQuery(null);

        $this->assertNull($resource->getQuery());
    }

    public function testSetPositionShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $position = 3;

        $resource->setPosition($position);

        $this->assertEquals($position, $resource->getPosition());
    }

    public function testSetDatasetIdShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $datasetId = 'dataset-67890';

        $resource->setDatasetId($datasetId);

        $this->assertEquals($datasetId, $resource->getDatasetId());
    }

    public function testSetDatasetNameShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $datasetName = '技术文档知识库';

        $resource->setDatasetName($datasetName);

        $this->assertEquals($datasetName, $resource->getDatasetName());
    }

    public function testSetDocumentIdShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $documentId = 'doc-67890';

        $resource->setDocumentId($documentId);

        $this->assertEquals($documentId, $resource->getDocumentId());
    }

    public function testSetDocumentNameShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $documentName = 'API安全最佳实践';

        $resource->setDocumentName($documentName);

        $this->assertEquals($documentName, $resource->getDocumentName());
    }

    public function testSetSegmentIdShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $segmentId = 'segment-67890';

        $resource->setSegmentId($segmentId);

        $this->assertEquals($segmentId, $resource->getSegmentId());
    }

    public function testSetScoreShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $score = 0.92;

        $resource->setScore($score);

        $this->assertEquals($score, $resource->getScore());
    }

    public function testSetContentShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $content = '这是从知识库检索到的相关内容，详细说明了API认证的最佳实践。';

        $resource->setContent($content);

        $this->assertEquals($content, $resource->getContent());
    }

    public function testSetSourceUrlShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $sourceUrl = 'https://docs.example.com/api/authentication.html';

        $resource->setSourceUrl($sourceUrl);

        $this->assertEquals($sourceUrl, $resource->getSourceUrl());
    }

    public function testSetSourceUrlWithNullShouldAcceptNull(): void
    {
        $resource = $this->createEntity();
        $resource->setSourceUrl('https://example.com/test.html');

        $resource->setSourceUrl(null);

        $this->assertNull($resource->getSourceUrl());
    }

    public function testSetMetadataShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $metadata = [
            'retrieval_method' => 'semantic_search',
            'query_expansion' => true,
            'rerank_score' => 0.88,
            'chunk_overlap' => 200,
        ];

        $resource->setMetadata($metadata);

        $this->assertEquals($metadata, $resource->getMetadata());
    }

    public function testSetMetadataWithNullShouldAcceptNull(): void
    {
        $resource = $this->createEntity();
        $resource->setMetadata(['key' => 'value']);

        $resource->setMetadata(null);

        $this->assertNull($resource->getMetadata());
    }

    public function testSetRetrievedAtShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $retrievedAt = new \DateTimeImmutable('2024-01-15 14:30:00');

        $resource->setRetrievedAt($retrievedAt);

        $this->assertEquals($retrievedAt, $resource->getRetrievedAt());
    }

    public function testSetRetrievedAtWithNullShouldAcceptNull(): void
    {
        $resource = $this->createEntity();
        $retrievedAt = new \DateTimeImmutable('2024-01-15 14:30:00');
        $resource->setRetrievedAt($retrievedAt);

        $resource->setRetrievedAt(null);

        $this->assertNull($resource->getRetrievedAt());
    }

    public function testSetResourceIdShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $resourceId = 'resource-67890';

        $resource->setResourceId($resourceId);

        $this->assertEquals($resourceId, $resource->getResourceId());
    }

    public function testSetResourceTypeShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $resourceType = 'webpage';

        $resource->setResourceType($resourceType);

        $this->assertEquals($resourceType, $resource->getResourceType());
    }

    public function testSetResourceUrlShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $resourceUrl = 'https://example.com/resources/guide.pdf';

        $resource->setResourceUrl($resourceUrl);

        $this->assertEquals($resourceUrl, $resource->getResourceUrl());
    }

    public function testSetTitleShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $title = 'REST API 安全认证完整指南';

        $resource->setTitle($title);

        $this->assertEquals($title, $resource->getTitle());
    }

    public function testSetCreateTimeShouldUpdateValue(): void
    {
        $resource = $this->createEntity();
        $createTime = new \DateTimeImmutable('2024-01-15 14:00:00');

        $resource->setCreateTime($createTime);

        $this->assertEquals($createTime, $resource->getCreateTime());
    }

    public function testToStringShouldReturnPositionScoreAndContent(): void
    {
        $resource = $this->createEntity();
        $resource->setPosition(2);
        $resource->setScore(0.89);
        $resource->setContent('这是一个很长的检索内容，包含了用户查询问题的详细答案和相关信息。');

        $result = (string) $resource;

        $this->assertStringContainsString('检索资源 #2', $result);
        $this->assertStringContainsString('0.890', $result);
        $this->assertStringContainsString('这是一个很长的检索内容，包含了用户查询问题的详细答案和相关信息。', $result);
    }

    public function testRetrieverResourceShouldAcceptLongContent(): void
    {
        $resource = $this->createEntity();
        $longContent = str_repeat('这是从知识库检索到的详细内容，包含了大量的技术信息和操作指南。', 100);

        $resource->setContent($longContent);

        $this->assertEquals($longContent, $resource->getContent());
    }

    public function testRetrieverResourceShouldAcceptComplexMetadata(): void
    {
        $resource = $this->createEntity();
        $complexMetadata = [
            'retrieval_config' => [
                'method' => 'hybrid_search',
                'semantic_weight' => 0.7,
                'keyword_weight' => 0.3,
                'rerank_enabled' => true,
            ],
            'search_metrics' => [
                'initial_score' => 0.82,
                'rerank_score' => 0.89,
                'boost_factor' => 1.15,
                'query_tokens' => 8,
            ],
            'content_analysis' => [
                'language' => 'zh-CN',
                'readability_score' => 7.2,
                'technical_level' => 'intermediate',
                'content_type' => 'tutorial',
            ],
            'source_info' => [
                'document_version' => '2.1.0',
                'last_updated' => '2024-01-10T10:00:00Z',
                'author' => 'Technical Team',
                'review_status' => 'approved',
            ],
        ];

        $resource->setMetadata($complexMetadata);

        $this->assertEquals($complexMetadata, $resource->getMetadata());
    }
}
