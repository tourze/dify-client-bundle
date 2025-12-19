<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Entity\Document;
use Tourze\DifyClientBundle\Service\DocumentFactory;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * DocumentFactory 测试类
 *
 * 测试文档工厂的核心功能
 * @internal
 */
#[CoversClass(DocumentFactory::class)]
#[RunTestsInSeparateProcesses]
final class DocumentFactoryTest extends AbstractIntegrationTestCase
{
    private DocumentFactory $documentFactory;

    protected function onSetUp(): void
    {
        $this->documentFactory = self::getService(DocumentFactory::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(DocumentFactory::class, $this->documentFactory);
    }

    public function testCreateFromText(): void
    {
        $dataset = new Dataset();
        $text = 'Sample document content';
        $name = 'Test Document';
        $indexingTechnique = 'high_quality';
        $userId = 'user123';

        $document = $this->documentFactory->createFromText(
            $dataset,
            $text,
            $name,
            $indexingTechnique,
            $userId
        );

        $this->assertInstanceOf(Document::class, $document);
        $this->assertSame($dataset, $document->getDataset());
        $this->assertEquals($name, $document->getName());
        $this->assertEquals($text, $document->getContent());
        $this->assertEquals($indexingTechnique, $document->getIndexingTechnique());
        $this->assertEquals($userId, $document->getUserId());
        $this->assertEquals('pending', $document->getProcessingStatus());
        $this->assertNotNull($document->getCreateTime());
    }

    public function testCreateFromFile(): void
    {
        $dataset = new Dataset();
        $indexingTechnique = 'economy';
        $userId = 'user456';

        // Mock UploadedFile，因为这是外部依赖
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('test.txt');
        $file->method('getMimeType')->willReturn('text/plain');
        $file->method('getSize')->willReturn(1024);

        $document = $this->documentFactory->createFromFile(
            $dataset,
            $file,
            null,
            $indexingTechnique,
            $userId
        );

        $this->assertInstanceOf(Document::class, $document);
        $this->assertSame($dataset, $document->getDataset());
        $this->assertEquals('test.txt', $document->getName());
        $this->assertEquals('test.txt', $document->getOriginalFilename());
        $this->assertEquals('text/plain', $document->getMimeType());
        $this->assertEquals(1024, $document->getFileSize());
        $this->assertEquals($indexingTechnique, $document->getIndexingTechnique());
        $this->assertEquals($userId, $document->getUserId());
        $this->assertEquals('pending', $document->getProcessingStatus());
        $this->assertNotNull($document->getCreateTime());
    }

    public function testCreateFromFileWithCustomName(): void
    {
        $dataset = new Dataset();
        $customName = 'Custom Document Name';
        $indexingTechnique = 'economy';
        $userId = 'user789';

        // Mock UploadedFile
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('original.txt');
        $file->method('getMimeType')->willReturn('text/plain');
        $file->method('getSize')->willReturn(2048);

        $document = $this->documentFactory->createFromFile(
            $dataset,
            $file,
            $customName,
            $indexingTechnique,
            $userId
        );

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals($customName, $document->getName());
        $this->assertEquals('original.txt', $document->getOriginalFilename());
        $this->assertEquals('text/plain', $document->getMimeType());
        $this->assertEquals(2048, $document->getFileSize());
    }
}
