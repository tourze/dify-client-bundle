<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\DifyClientBundle\Entity\FileUpload;
use Tourze\DifyClientBundle\Enum\FileTransferMethod;
use Tourze\DifyClientBundle\Enum\FileType;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(FileUpload::class)]
final class FileUploadTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): FileUpload
    {
        return new FileUpload();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'fileId' => ['fileId', 'file-12345'];
        yield 'name' => ['name', 'document.pdf'];
        yield 'url' => ['url', 'https://example.com/files/document.pdf'];
        yield 'size' => ['size', 2048000];
        yield 'mimeType' => ['mimeType', 'application/pdf'];
        yield 'localPath' => ['localPath', '/tmp/uploads/document.pdf'];
        yield 'originalName' => ['originalName', '原始文档.pdf'];
        yield 'storedName' => ['storedName', 'stored_document_123.pdf'];
        yield 'extension' => ['extension', 'pdf'];
        yield 'userId' => ['userId', 'user-456'];
        yield 'errorMessage' => ['errorMessage', '文件上传失败：网络错误'];
        yield 'uploadStatus' => ['uploadStatus', 'completed'];
    }

    public function testCreateFileUploadWithDefaultValuesShouldSucceed(): void
    {
        $fileUpload = $this->createEntity();

        $this->assertNull($fileUpload->getId());
        $this->assertNull($fileUpload->getFileId());
        $this->assertNull($fileUpload->getUrl());
        $this->assertNull($fileUpload->getSize());
        $this->assertNull($fileUpload->getMimeType());
        $this->assertNull($fileUpload->getLocalPath());
        $this->assertNull($fileUpload->getMetadata());
        $this->assertNull($fileUpload->getUploadedAt());
        $this->assertNull($fileUpload->getOriginalName());
        $this->assertNull($fileUpload->getStoredName());
        $this->assertNull($fileUpload->getExtension());
        $this->assertNull($fileUpload->getUserId());
        $this->assertNull($fileUpload->getDeletedAt());
        $this->assertNull($fileUpload->getErrorMessage());
        $this->assertNull($fileUpload->getUploadStatus());
        $this->assertNull($fileUpload->getProcessedAt());
    }

    public function testSetFileIdShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $fileId = 'file-12345';

        $fileUpload->setFileId($fileId);

        $this->assertEquals($fileId, $fileUpload->getFileId());
    }

    public function testSetFileIdWithNullShouldAcceptNull(): void
    {
        $fileUpload = $this->createEntity();
        $fileUpload->setFileId('file-123');

        $fileUpload->setFileId(null);

        $this->assertNull($fileUpload->getFileId());
    }

    public function testSetTypeShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $type = FileType::DOCUMENT;

        $fileUpload->setType($type);

        $this->assertEquals($type, $fileUpload->getType());
    }

    public function testSetTransferMethodShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $transferMethod = FileTransferMethod::REMOTE_URL;

        $fileUpload->setTransferMethod($transferMethod);

        $this->assertEquals($transferMethod, $fileUpload->getTransferMethod());
    }

    public function testSetNameShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $name = 'important-document.pdf';

        $fileUpload->setName($name);

        $this->assertEquals($name, $fileUpload->getName());
    }

    public function testSetUrlShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $url = 'https://example.com/files/document.pdf';

        $fileUpload->setUrl($url);

        $this->assertEquals($url, $fileUpload->getUrl());
    }

    public function testSetUrlWithNullShouldAcceptNull(): void
    {
        $fileUpload = $this->createEntity();
        $fileUpload->setUrl('https://example.com/test.pdf');

        $fileUpload->setUrl(null);

        $this->assertNull($fileUpload->getUrl());
    }

    public function testSetSizeShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $size = 2048000;

        $fileUpload->setSize($size);

        $this->assertEquals($size, $fileUpload->getSize());
    }

    public function testSetSizeWithNullShouldAcceptNull(): void
    {
        $fileUpload = $this->createEntity();
        $fileUpload->setSize(1024000);

        $fileUpload->setSize(null);

        $this->assertNull($fileUpload->getSize());
    }

    public function testSetMimeTypeShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $mimeType = 'application/pdf';

        $fileUpload->setMimeType($mimeType);

        $this->assertEquals($mimeType, $fileUpload->getMimeType());
    }

    public function testSetMimeTypeWithNullShouldAcceptNull(): void
    {
        $fileUpload = $this->createEntity();
        $fileUpload->setMimeType('application/pdf');

        $fileUpload->setMimeType(null);

        $this->assertNull($fileUpload->getMimeType());
    }

    public function testSetLocalPathShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $localPath = '/var/uploads/documents/file.pdf';

        $fileUpload->setLocalPath($localPath);

        $this->assertEquals($localPath, $fileUpload->getLocalPath());
    }

    public function testSetLocalPathWithNullShouldAcceptNull(): void
    {
        $fileUpload = $this->createEntity();
        $fileUpload->setLocalPath('/tmp/test.pdf');

        $fileUpload->setLocalPath(null);

        $this->assertNull($fileUpload->getLocalPath());
    }

    public function testSetMetadataShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $metadata = [
            'checksum' => 'abc123def456',
            'source' => 'user_upload',
            'tags' => ['document', 'important'],
            'upload_session' => 'session-789',
        ];

        $fileUpload->setMetadata($metadata);

        $this->assertEquals($metadata, $fileUpload->getMetadata());
    }

    public function testSetMetadataWithNullShouldAcceptNull(): void
    {
        $fileUpload = $this->createEntity();
        $fileUpload->setMetadata(['key' => 'value']);

        $fileUpload->setMetadata(null);

        $this->assertNull($fileUpload->getMetadata());
    }

    public function testSetUploadedAtShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $uploadedAt = new \DateTimeImmutable('2024-01-15 10:30:00');

        $fileUpload->setUploadedAt($uploadedAt);

        $this->assertEquals($uploadedAt, $fileUpload->getUploadedAt());
    }

    public function testSetUploadedAtWithNullShouldAcceptNull(): void
    {
        $fileUpload = $this->createEntity();
        $uploadedAt = new \DateTimeImmutable('2024-01-15 10:30:00');
        $fileUpload->setUploadedAt($uploadedAt);

        $fileUpload->setUploadedAt(null);

        $this->assertNull($fileUpload->getUploadedAt());
    }

    public function testSetOriginalNameShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $originalName = '中文文档.pdf';

        $fileUpload->setOriginalName($originalName);

        $this->assertEquals($originalName, $fileUpload->getOriginalName());
    }

    public function testSetStoredNameShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $storedName = 'stored_document_123456.pdf';

        $fileUpload->setStoredName($storedName);

        $this->assertEquals($storedName, $fileUpload->getStoredName());
    }

    public function testSetExtensionShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $extension = 'docx';

        $fileUpload->setExtension($extension);

        $this->assertEquals($extension, $fileUpload->getExtension());
    }

    public function testSetUserIdShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $userId = 'user-789';

        $fileUpload->setUserId($userId);

        $this->assertEquals($userId, $fileUpload->getUserId());
    }

    public function testSetDeletedAtShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $deletedAt = new \DateTimeImmutable('2024-01-20 15:45:00');

        $fileUpload->setDeletedAt($deletedAt);

        $this->assertEquals($deletedAt, $fileUpload->getDeletedAt());
    }

    public function testSetErrorMessageShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $errorMessage = '文件上传失败：文件大小超过限制';

        $fileUpload->setErrorMessage($errorMessage);

        $this->assertEquals($errorMessage, $fileUpload->getErrorMessage());
    }

    public function testSetUploadStatusShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $uploadStatus = 'processing';

        $fileUpload->setUploadStatus($uploadStatus);

        $this->assertEquals($uploadStatus, $fileUpload->getUploadStatus());
    }

    #[TestWith(['pending'], 'pending')]
    #[TestWith(['processing'], 'processing')]
    #[TestWith(['completed'], 'completed')]
    #[TestWith(['failed'], 'failed')]
    public function testSetUploadStatusWithValidValuesShouldSucceed(string $uploadStatus): void
    {
        $fileUpload = $this->createEntity();

        $fileUpload->setUploadStatus($uploadStatus);

        $this->assertEquals($uploadStatus, $fileUpload->getUploadStatus());
    }

    public function testSetProcessedAtShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $processedAt = new \DateTimeImmutable('2024-01-15 10:35:00');

        $fileUpload->setProcessedAt($processedAt);

        $this->assertEquals($processedAt, $fileUpload->getProcessedAt());
    }

    public function testSetCreateTimeShouldUpdateValue(): void
    {
        $fileUpload = $this->createEntity();
        $createTime = new \DateTimeImmutable('2024-01-15 10:00:00');

        $fileUpload->setCreateTime($createTime);

        $this->assertEquals($createTime, $fileUpload->getCreateTime());
    }

    public function testAliasMethods(): void
    {
        $fileUpload = $this->createEntity();

        // Test setFileSize alias
        $fileUpload->setFileSize(1024);
        $this->assertEquals(1024, $fileUpload->getSize());

        // Test setFileType alias
        $fileType = FileType::IMAGE;
        $fileUpload->setFileType($fileType);
        $this->assertEquals($fileType, $fileUpload->getType());

        // Test setOriginalFilename alias
        $originalFilename = 'test.jpg';
        $fileUpload->setOriginalFilename($originalFilename);
        $this->assertEquals($originalFilename, $fileUpload->getOriginalName());

        // Test setFileUrl alias
        $fileUrl = 'https://example.com/test.jpg';
        $fileUpload->setFileUrl($fileUrl);
        $this->assertEquals($fileUrl, $fileUpload->getUrl());
    }

    public function testToStringShouldReturnNameAndTypeLabel(): void
    {
        $fileUpload = $this->createEntity();
        $fileUpload->setName('document.pdf');
        $fileUpload->setType(FileType::DOCUMENT);

        $result = (string) $fileUpload;

        $this->assertStringContainsString('document.pdf', $result);
        $this->assertStringContainsString('(', $result);
        $this->assertStringContainsString(')', $result);
    }

    public function testFileUploadShouldAcceptLongPaths(): void
    {
        $fileUpload = $this->createEntity();
        $longPath = '/very/long/path/to/uploaded/files/in/deep/directory/structure/document.pdf';

        $fileUpload->setLocalPath($longPath);

        $this->assertEquals($longPath, $fileUpload->getLocalPath());
    }

    public function testFileUploadShouldAcceptComplexMetadata(): void
    {
        $fileUpload = $this->createEntity();
        $complexMetadata = [
            'upload_info' => [
                'session_id' => 'session-12345',
                'client_ip' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 Chrome/120.0',
                'upload_method' => 'drag_drop',
            ],
            'file_validation' => [
                'checksum_md5' => 'd41d8cd98f00b204e9800998ecf8427e',
                'checksum_sha256' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                'virus_scan_result' => 'clean',
                'content_type_verified' => true,
            ],
            'processing_options' => [
                'auto_convert' => true,
                'extract_text' => true,
                'generate_thumbnail' => false,
                'compress' => true,
            ],
            'business_context' => [
                'department' => 'marketing',
                'project' => 'Q1-2024-campaign',
                'tags' => ['important', 'confidential'],
                'retention_policy' => '7_years',
            ],
        ];

        $fileUpload->setMetadata($complexMetadata);

        $this->assertEquals($complexMetadata, $fileUpload->getMetadata());
    }
}
