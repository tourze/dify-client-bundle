<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\FileUpload;
use Tourze\DifyClientBundle\Enum\FileTransferMethod;
use Tourze\DifyClientBundle\Enum\FileType;

class FileUploadFixtures extends Fixture
{
    public const FILE_UPLOAD_REFERENCE = 'file-upload-1';

    public function load(ObjectManager $manager): void
    {
        $upload = new FileUpload();
        $upload->setFileId('file-123');
        $upload->setType(FileType::DOCUMENT);
        $upload->setTransferMethod(FileTransferMethod::REMOTE_URL);
        $upload->setName('document.pdf');
        $upload->setOriginalName('document.pdf');
        $upload->setMimeType('application/pdf');
        $upload->setSize(1024000);
        $upload->setUrl('https://test.localhost/uploads/file-123.pdf');
        $upload->setUploadStatus('completed');
        $upload->setUserId('user-123');
        $upload->setMetadata([
            'uploader' => 'web_client',
            'compression' => true,
        ]);
        $upload->setUploadedAt(new \DateTimeImmutable('2024-01-01 10:00:00'));
        $upload->setProcessedAt(new \DateTimeImmutable('2024-01-01 10:00:30'));

        $manager->persist($upload);
        $manager->flush();

        $this->addReference(self::FILE_UPLOAD_REFERENCE, $upload);
    }
}
