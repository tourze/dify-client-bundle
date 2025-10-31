<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\FileUpload;
use Tourze\DifyClientBundle\Enum\FileTransferMethod;
use Tourze\DifyClientBundle\Enum\FileType;
use Tourze\DifyClientBundle\Repository\FileUploadRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(FileUploadRepository::class)]
#[RunTestsInSeparateProcesses]
final class FileUploadRepositoryTest extends AbstractRepositoryTestCase
{
    private FileUploadRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(FileUploadRepository::class);
    }

    protected function getRepository(): FileUploadRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): FileUpload
    {
        $fileUpload = new FileUpload();
        $fileUpload->setFileId('test-file-' . uniqid());
        $fileUpload->setName('test-file.txt');
        $fileUpload->setType(FileType::DOCUMENT);
        $fileUpload->setTransferMethod(FileTransferMethod::LOCAL_FILE);
        $fileUpload->setMimeType('text/plain');
        $fileUpload->setSize(1024);
        $fileUpload->setUserId('test-user');
        $fileUpload->setUploadedAt(new \DateTimeImmutable());

        return $fileUpload;
    }

    public function testFindByFileIdShouldReturnCorrectFile(): void
    {
        // Arrange: 创建并持久化文件
        $fileId = 'test-file-id-' . uniqid();
        $fileUpload = new FileUpload();
        $fileUpload->setFileId($fileId);
        $fileUpload->setName('document.pdf');
        $fileUpload->setType(FileType::DOCUMENT);
        $fileUpload->setTransferMethod(FileTransferMethod::LOCAL_FILE);
        $fileUpload->setMimeType('application/pdf');
        $fileUpload->setSize(2048);
        $fileUpload->setUserId('user123');
        $fileUpload->setUploadedAt(new \DateTimeImmutable());
        $this->persistAndFlush($fileUpload);

        // Act: 根据文件ID查找
        $foundFile = $this->repository->findByFileId($fileId);

        // Assert: 验证找到正确的文件
        $this->assertNotNull($foundFile);
        $this->assertSame($fileId, $foundFile->getFileId());
        $this->assertSame('document.pdf', $foundFile->getName());
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新文件上传记录
        $fileUpload = $this->createNewEntity();

        // Act: 保存文件
        $this->repository->save($fileUpload);

        // Assert: 验证文件已持久化
        $this->assertNotNull($fileUpload->getId());
        $this->assertEntityPersisted($fileUpload);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化文件
        $fileUpload = $this->createNewEntity();
        $this->persistAndFlush($fileUpload);
        $fileId = $fileUpload->getId();

        // Act: 删除文件
        $this->repository->remove($fileUpload);

        // Assert: 验证文件已删除
        $this->assertEntityNotExists(FileUpload::class, $fileId);
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryHasCorrectEntityClass(): void
    {
        $this->assertSame(FileUpload::class, $this->repository->getClassName());
    }

    public function testGetEntityManagerShouldReturnEntityManagerInterface(): void
    {
        $em = self::getEntityManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $em);
    }

    public function testFindByName(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByTransferMethod(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindByType(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFindRecentUploads(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }

    public function testFlush(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
