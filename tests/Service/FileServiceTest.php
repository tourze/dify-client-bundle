<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Enum\FileType;
use Tourze\DifyClientBundle\Service\FileService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * FileService 测试类
 *
 * 测试文件服务的核心功能
 * @internal
 */
#[CoversClass(FileService::class)]
#[RunTestsInSeparateProcesses]
final class FileServiceTest extends AbstractIntegrationTestCase
{
    private FileService $fileService;

    protected function onSetUp(): void
    {
        $this->fileService = self::getService(FileService::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(FileService::class, $this->fileService);
    }

    public function testFindByFileIdShouldReturnNullWhenNotFound(): void
    {
        $fileId = 'non-existent-file-' . uniqid();

        $result = $this->fileService->findByFileId($fileId);

        $this->assertNull($result);
    }

    public function testGetUserFilesShouldReturnEmptyArrayWhenNoFiles(): void
    {
        $userId = 'test-user-' . uniqid();
        $limit = 10;
        $offset = 0;

        $result = $this->fileService->getUserFiles($userId, $limit, $offset);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetFilesByTypeShouldReturnEmptyArrayWhenNoFiles(): void
    {
        $fileType = FileType::IMAGE;
        $limit = 20;
        $offset = 5;

        $result = $this->fileService->getFilesByType($fileType, $limit, $offset);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetFileStatsMethodExists(): void
    {
        $reflection = new \ReflectionMethod($this->fileService, 'getFileStats');
        $this->assertTrue($reflection->isPublic());
    }

    public function testUploadFileMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->fileService);
        $this->assertTrue($reflection->hasMethod('uploadFile'));
        $method = $reflection->getMethod('uploadFile');
        $this->assertTrue($method->isPublic());
    }

    public function testDeleteFileMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->fileService);
        $this->assertTrue($reflection->hasMethod('deleteFile'));
        $method = $reflection->getMethod('deleteFile');
        $this->assertTrue($method->isPublic());
    }

    public function testCleanupExpiredFilesMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->fileService);
        $this->assertTrue($reflection->hasMethod('cleanupExpiredFiles'));
        $method = $reflection->getMethod('cleanupExpiredFiles');
        $this->assertTrue($method->isPublic());
    }
}
