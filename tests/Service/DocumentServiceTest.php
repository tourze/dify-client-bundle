<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Service\DocumentService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * DocumentService 测试类
 *
 * 测试文档服务的核心功能
 * @internal
 */
#[CoversClass(DocumentService::class)]
#[RunTestsInSeparateProcesses]
final class DocumentServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，测试不需要数据库
    }

    /**
     * 测试服务实例创建
     */
    public function testServiceInstanceCreation(): void
    {
        $service = self::getService(DocumentService::class);
        $this->assertInstanceOf(DocumentService::class, $service);
    }

    public function testGetDocumentsMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('getDocuments'));
    }

    public function testCreateDocumentByTextMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('createDocumentByText'));
    }

    public function testCreateDocumentByFileMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('createDocumentByFile'));
    }

    public function testUpdateDocumentByTextMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('updateDocumentByText'));
    }

    public function testUpdateDocumentByFileMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('updateDocumentByFile'));
    }

    public function testCreateDocumentFromFileMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('createDocumentFromFile'));
    }

    public function testCreateDocumentFromTextMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('createDocumentFromText'));
    }

    public function testUpdateDocumentMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('updateDocument'));
    }

    public function testDeleteDocumentMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('deleteDocument'));
    }

    public function testDeleteDocumentByIdMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('deleteDocumentById'));
    }

    public function testReindexDocumentMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('reindexDocument'));
    }

    public function testGetDatasetDocumentsMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('getDatasetDocuments'));
    }

    public function testSearchDocumentsMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('searchDocuments'));
    }

    public function testGetDocumentStatsMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('getDocumentStats'));
    }

    public function testGetDocumentChunksMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('getDocumentChunks'));
    }

    public function testUpdateDocumentSegmentMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('updateDocumentSegment'));
    }

    public function testUpdateChildChunkMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('updateChildChunk'));
    }

    public function testCreateDocumentSegmentMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('createDocumentSegment'));
    }

    public function testDeleteDocumentSegmentMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('deleteDocumentSegment'));
    }

    public function testCreateChildChunkMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('createChildChunk'));
    }

    public function testDeleteChildChunkMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('deleteChildChunk'));
    }

    public function testUpdateDocumentStatusMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('updateDocumentStatus'));
    }

    public function testBatchImportDocumentsMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('batchImportDocuments'));
    }

    public function testCleanupFailedDocumentsMethodExists(): void
    {
        $service = self::getService(DocumentService::class);
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('cleanupFailedDocuments'));
    }
}
