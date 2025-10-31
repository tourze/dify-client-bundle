<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;
use Tourze\DifyClientBundle\Repository\DocumentChunkRepository;
use Tourze\DifyClientBundle\Repository\DocumentRepository;
use Tourze\DifyClientBundle\Service\DocumentService;

/**
 * DocumentService 测试类
 *
 * 测试文档服务的核心功能
 * @internal
 */
#[CoversClass(DocumentService::class)]
class DocumentServiceTest extends TestCase
{
    private DocumentService $documentService;

    private EventDispatcherInterface&MockObject $eventDispatcher;

    private DifySettingRepository&MockObject $settingRepository;

    private DocumentRepository&MockObject $documentRepository;

    private DocumentChunkRepository&MockObject $chunkRepository;

    private ClockInterface&MockObject $clock;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->settingRepository = $this->createMock(DifySettingRepository::class);
        $this->documentRepository = $this->createMock(DocumentRepository::class);
        $this->chunkRepository = $this->createMock(DocumentChunkRepository::class);
        $this->clock = $this->createMock(ClockInterface::class);

        $this->documentService = new DocumentService(
            $this->eventDispatcher,
            $this->settingRepository,
            $this->documentRepository,
            $this->chunkRepository,
            $this->clock
        );
    }

    /**
     * 测试服务实例创建
     */
    public function testServiceInstanceCreation(): void
    {
        $this->assertInstanceOf(DocumentService::class, $this->documentService);
    }

    public function testGetDocumentsMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('getDocuments'));
    }

    public function testCreateDocumentByTextMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('createDocumentByText'));
    }

    public function testCreateDocumentByFileMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('createDocumentByFile'));
    }

    public function testUpdateDocumentByTextMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('updateDocumentByText'));
    }

    public function testUpdateDocumentByFileMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('updateDocumentByFile'));
    }

    public function testCreateDocumentFromFileMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('createDocumentFromFile'));
    }

    public function testCreateDocumentFromTextMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('createDocumentFromText'));
    }

    public function testUpdateDocumentMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('updateDocument'));
    }

    public function testDeleteDocumentMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('deleteDocument'));
    }

    public function testDeleteDocumentByIdMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('deleteDocumentById'));
    }

    public function testReindexDocumentMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('reindexDocument'));
    }

    public function testGetDatasetDocumentsMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('getDatasetDocuments'));
    }

    public function testSearchDocumentsMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('searchDocuments'));
    }

    public function testGetDocumentStatsMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('getDocumentStats'));
    }

    public function testGetDocumentChunksMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('getDocumentChunks'));
    }

    public function testUpdateDocumentSegmentMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('updateDocumentSegment'));
    }

    public function testUpdateChildChunkMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('updateChildChunk'));
    }

    public function testCreateDocumentSegmentMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('createDocumentSegment'));
    }

    public function testDeleteDocumentSegmentMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('deleteDocumentSegment'));
    }

    public function testCreateChildChunkMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('createChildChunk'));
    }

    public function testDeleteChildChunkMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('deleteChildChunk'));
    }

    public function testUpdateDocumentStatusMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('updateDocumentStatus'));
    }

    public function testBatchImportDocumentsMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('batchImportDocuments'));
    }

    public function testCleanupFailedDocumentsMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->documentService);
        $this->assertTrue($reflection->hasMethod('cleanupFailedDocuments'));
    }
}
