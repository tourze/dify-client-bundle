<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Service;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Entity\Document;
use Tourze\DifyClientBundle\Service\DocumentFactory;

/**
 * DocumentFactory 测试类
 *
 * 测试文档工厂的核心功能
 * @internal
 */
#[CoversClass(DocumentFactory::class)]
class DocumentFactoryTest extends TestCase
{
    private DocumentFactory $documentFactory;

    private ClockInterface&MockObject $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clock = $this->createMock(ClockInterface::class);
        $this->documentFactory = new DocumentFactory($this->clock);
    }

    /**
     * 测试从文本创建文档
     */
    public function testCreateFromText(): void
    {
        $dataset = new Dataset();
        $text = 'Sample document content';
        $name = 'Test Document';
        $indexingTechnique = 'high_quality';
        $userId = 'user123';
        $now = new \DateTimeImmutable();

        $this->clock->expects($this->once())
            ->method('now')
            ->willReturn($now)
        ;

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
        $this->assertEquals($now, $document->getCreateTime());
    }

    /**
     * 测试从文件创建文档
     */
    public function testCreateFromFile(): void
    {
        $dataset = new Dataset();
        $indexingTechnique = 'economy';
        $userId = 'user456';
        $now = new \DateTimeImmutable();

        // 创建一个模拟UploadedFile
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('test.txt');
        $file->method('getMimeType')->willReturn('text/plain');
        $file->method('getSize')->willReturn(1024);

        $this->clock->expects($this->once())
            ->method('now')
            ->willReturn($now)
        ;

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
        $this->assertEquals($now, $document->getCreateTime());
    }

    /**
     * 测试从文件创建文档（使用自定义名称）
     */
    public function testCreateFromFileWithCustomName(): void
    {
        $dataset = new Dataset();
        $customName = 'Custom Document Name';
        $indexingTechnique = 'economy';
        $userId = 'user789';
        $now = new \DateTimeImmutable();

        // 创建一个模拟UploadedFile
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('original.txt');
        $file->method('getMimeType')->willReturn('text/plain');
        $file->method('getSize')->willReturn(2048);

        $this->clock->expects($this->once())
            ->method('now')
            ->willReturn($now)
        ;

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
