<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Entity\FileUpload;
use Tourze\DifyClientBundle\Enum\FileTransferMethod;
use Tourze\DifyClientBundle\Enum\FileType;
use Tourze\DifyClientBundle\Exception\DifyException;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;
use Tourze\DifyClientBundle\Exception\DifySettingNotFoundException;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;
use Tourze\DifyClientBundle\Repository\FileUploadRepository;

/**
 * 文件操作服务
 *
 * 提供文件上传、预览、管理等功能
 * 对应 API: POST /files/upload, GET /files/{file_id}, DELETE /files/{file_id}
 */
final readonly class FileService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private DifySettingRepository $settingRepository,
        private FileUploadRepository $fileUploadRepository,
        private ClockInterface $clock,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 上传文件到 Dify
     */
    public function uploadFile(
        UploadedFile $file,
        string $userId = 'system',
        FileTransferMethod $transferMethod = FileTransferMethod::REMOTE_URL,
    ): FileUpload {
        $setting = $this->getActiveSetting();

        // 验证文件
        $this->validateFile($file);

        // 创建文件实体
        $fileUpload = $this->createFileUpload($file, $userId, $transferMethod);
        $this->persistFileUpload($fileUpload);

        try {
            // 上传到 Dify
            $response = $this->uploadToDify($setting, $file, $userId);

            // 更新文件信息
            $this->updateFileFromResponse($fileUpload, $response);
            $this->persistFileUpload($fileUpload);

            return $fileUpload;
        } catch (\Exception $e) {
            $this->handleUploadFailure($fileUpload, $e);
            throw $e;
        }
    }

    /**
     * 获取文件预览信息
     *
     * @return array<string, mixed>|null
     */
    public function getFilePreview(string $fileId, bool $asAttachment = false): ?array
    {
        $setting = $this->getActiveSetting();

        try {
            return $this->getDifyFileInfo($setting, $fileId, $asAttachment);
        } catch (\Exception $e) {
            throw new DifyRuntimeException(sprintf('Failed to get file preview for %s: %s', $fileId, $e->getMessage()));
        }
    }

    /**
     * 删除文件
     */
    public function deleteFile(FileUpload $fileUpload): void
    {
        $setting = $this->getActiveSetting();

        // 如果有 fileId，尝试删除 Dify 端的文件
        $fileId = $fileUpload->getFileId();
        if (null !== $fileId) {
            try {
                $this->deleteDifyFile($setting, $fileId);
            } catch (\Exception $e) {
                // 记录错误但不阻止本地删除
                error_log(sprintf('Failed to delete Dify file %s: %s', $fileId, $e->getMessage()));
            }
        }

        $fileUpload->setDeletedAt($this->clock->now());
        $this->persistFileUpload($fileUpload);
    }

    /**
     * 根据文件ID查找文件
     */
    public function findByFileId(string $fileId): ?FileUpload
    {
        return $this->fileUploadRepository->findOneBy(['fileId' => $fileId]);
    }

    /**
     * 获取用户的文件列表
     *
     * @return array<FileUpload>
     */
    public function getUserFiles(string $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->fileUploadRepository->findBy(
            ['userId' => $userId, 'deletedAt' => null],
            ['createTime' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * 根据文件类型获取文件列表
     *
     * @return array<FileUpload>
     */
    public function getFilesByType(FileType $fileType, int $limit = 50, int $offset = 0): array
    {
        return $this->fileUploadRepository->findBy(
            ['type' => $fileType, 'deletedAt' => null],
            ['createTime' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * 获取文件统计信息
     *
     * @return array<string, mixed>
     */
    public function getFileStats(): array
    {
        $qb = $this->fileUploadRepository->createQueryBuilder('f');

        $totalFiles = (int) $qb
            ->select('COUNT(f.id)')
            ->where('f.deletedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $totalSize = (int) $qb
            ->select('SUM(f.size)')
            ->where('f.deletedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $typeStats = $qb
            ->select('f.type, COUNT(f.id) as count, SUM(f.size) as size')
            ->where('f.deletedAt IS NULL')
            ->groupBy('f.type')
            ->getQuery()
            ->getArrayResult()
        ;

        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'by_type' => $typeStats,
        ];
    }

    /**
     * 清理过期的文件记录
     */
    public function cleanupExpiredFiles(\DateInterval $expiry): int
    {
        $expiredDate = $this->clock->now()->sub($expiry);

        $qb = $this->fileUploadRepository->createQueryBuilder('f');
        $expiredFiles = $qb
            ->where('f.deletedAt IS NOT NULL')
            ->andWhere('f.deletedAt < :expiredDate')
            ->setParameter('expiredDate', $expiredDate)
            ->getQuery()
            ->getResult()
        ;

        $cleanupCount = 0;

        if (is_iterable($expiredFiles)) {
            foreach ($expiredFiles as $file) {
                if ($file instanceof FileUpload) {
                    $this->entityManager->remove($file);
                    ++$cleanupCount;
                }
            }
        }

        if ($cleanupCount > 0) {
            $this->entityManager->flush();
        }

        return $cleanupCount;
    }

    private function getActiveSetting(): DifySetting
    {
        $setting = $this->settingRepository->findActiveSetting();
        if (null === $setting) {
            throw new DifySettingNotFoundException();
        }

        return $setting;
    }

    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new DifyRuntimeException('Invalid file upload');
        }

        // 检查文件大小限制（100MB）
        if ($file->getSize() > 100 * 1024 * 1024) {
            throw new DifyRuntimeException('File size exceeds 100MB limit');
        }

        // 检查文件类型
        $allowedTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif',
            'text/plain', 'text/markdown', 'text/html', 'text/csv',
            'application/pdf',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        if (!in_array($file->getMimeType(), $allowedTypes, true)) {
            throw new DifyRuntimeException('Unsupported file type: ' . $file->getMimeType());
        }
    }

    private function createFileUpload(UploadedFile $file, string $userId, FileTransferMethod $transferMethod): FileUpload
    {
        $fileUpload = new FileUpload();
        $fileUpload->setOriginalName($file->getClientOriginalName());
        $fileUpload->setMimeType($file->getMimeType());
        $fileUpload->setFileSize($file->getSize());
        $fileUpload->setUserId($userId);
        $fileUpload->setTransferMethod($transferMethod);
        $fileUpload->setCreateTime($this->clock->now());

        // 推断文件类型
        $mimeType = $file->getMimeType();
        if (null !== $mimeType) {
            $fileType = $this->inferFileType($mimeType);
            $fileUpload->setFileType($fileType);
        }

        return $fileUpload;
    }

    private function inferFileType(string $mimeType): FileType
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => FileType::IMAGE,
            str_starts_with($mimeType, 'text/') => FileType::DOCUMENT,
            str_contains($mimeType, 'pdf') => FileType::DOCUMENT,
            str_contains($mimeType, 'word') || str_contains($mimeType, 'document') => FileType::DOCUMENT,
            str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet') => FileType::DOCUMENT,
            str_contains($mimeType, 'powerpoint') || str_contains($mimeType, 'presentation') => FileType::DOCUMENT,
            default => FileType::OTHER,
        };
    }

    private function persistFileUpload(FileUpload $fileUpload): void
    {
        $this->entityManager->persist($fileUpload);
        $this->entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    private function uploadToDify(DifySetting $setting, UploadedFile $file, string $userId): array
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/files/upload';

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
            ],
            'body' => [
                'file' => fopen($file->getPathname(), 'r'),
                'user' => $userId,
            ],
            'timeout' => $setting->getTimeout(),
        ]);

        if (201 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Dify file upload failed: ' . $response->getContent(false));
        }

        /** @var array<string, mixed> */
        return $response->toArray();
    }

    /** @param array<string, mixed> $response */
    private function updateFileFromResponse(FileUpload $fileUpload, array $response): void
    {
        if (isset($response['id']) && is_string($response['id'])) {
            $fileUpload->setFileId($response['id']);
        }
        if (array_key_exists('name', $response) && (is_string($response['name']) || is_null($response['name']))) {
            $fileUpload->setStoredName($response['name']);
        }
        if (array_key_exists('url', $response) && (is_string($response['url']) || is_null($response['url']))) {
            $fileUpload->setUrl($response['url']);
        }
        if (array_key_exists('extension', $response) && (is_string($response['extension']) || is_null($response['extension']))) {
            $fileUpload->setExtension($response['extension']);
        }

        $fileUpload->setUploadedAt($this->clock->now());
    }

    /** @return array<string, mixed> */
    private function getDifyFileInfo(DifySetting $setting, string $fileId, bool $asAttachment = false): array
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/files/' . $fileId . '/preview';

        if ($asAttachment) {
            $url .= '?as_attachment=true';
        }

        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
            ],
            'timeout' => $setting->getTimeout(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Failed to get Dify file info: ' . $response->getContent(false));
        }

        /** @var array<string, mixed> */
        return $response->toArray();
    }

    private function deleteDifyFile(DifySetting $setting, string $fileId): void
    {
        $url = rtrim($setting->getBaseUrl(), '/') . '/files/' . $fileId;

        $response = $this->httpClient->request('DELETE', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $setting->getApiKey(),
            ],
            'timeout' => $setting->getTimeout(),
        ]);

        if (204 !== $response->getStatusCode()) {
            throw new DifyRuntimeException('Failed to delete Dify file: ' . $response->getContent(false));
        }
    }

    private function handleUploadFailure(FileUpload $fileUpload, \Exception $e): void
    {
        $fileUpload->setErrorMessage($e->getMessage());
        $this->persistFileUpload($fileUpload);
    }
}
