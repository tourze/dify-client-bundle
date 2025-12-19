<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Exception\DifyRuntimeException;
use Tourze\DifyClientBundle\Service\DocumentValidator;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/** * @internal
 */
#[CoversClass(DocumentValidator::class)]
#[RunTestsInSeparateProcesses]
final class DocumentValidatorTest extends AbstractIntegrationTestCase
{
    private DocumentValidator $validator;

    protected function onSetUp(): void
    {
        $this->validator = self::getService(DocumentValidator::class);
    }

    public function testValidatorExists(): void
    {
        $this->assertInstanceOf(DocumentValidator::class, $this->validator);
    }

    public function testValidatorHasValidationMethods(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $this->assertTrue($reflection->hasMethod('validateText'));
        $this->assertTrue($reflection->hasMethod('validateFile'));
        $this->assertTrue($reflection->hasMethod('validateDataset'));
    }

    public function testValidateTextShouldPassForValidText(): void
    {
        $validText = 'This is a valid document text content.';

        // Should not throw exception
        $this->validator->validateText($validText);
        $this->assertTrue(true);
    }

    public function testValidateTextShouldThrowExceptionForEmptyText(): void
    {
        $this->expectException(DifyRuntimeException::class);
        $this->expectExceptionMessage('Document text cannot be empty');

        $this->validator->validateText('   ');
    }

    public function testValidateTextShouldThrowExceptionForTooLongText(): void
    {
        $this->expectException(DifyRuntimeException::class);
        $this->expectExceptionMessage('Document text exceeds 1M character limit');

        // Generate text longer than 1M characters
        $longText = str_repeat('a', 1000001);
        $this->validator->validateText($longText);
    }

    public function testValidateDatasetShouldPassForValidDataset(): void
    {
        $mockDataset = $this->createMock(Dataset::class);
        $mockDataset->method('getDatasetId')->willReturn('valid-dataset-id');

        // Should not throw exception
        $this->validator->validateDataset($mockDataset);
        $this->assertTrue(true);
    }

    public function testValidateDatasetShouldThrowExceptionForInvalidDataset(): void
    {
        $mockDataset = $this->createMock(Dataset::class);
        $mockDataset->method('getDatasetId')->willReturn(null);

        $this->expectException(DifyRuntimeException::class);
        $this->expectExceptionMessage('Dataset must have a valid Dify dataset ID');

        $this->validator->validateDataset($mockDataset);
    }

    public function testValidateFileShouldThrowExceptionForInvalidFile(): void
    {
        $mockFile = $this->createMock(UploadedFile::class);
        $mockFile->method('isValid')->willReturn(false);

        $this->expectException(DifyRuntimeException::class);
        $this->expectExceptionMessage('Invalid document file upload');

        $this->validator->validateFile($mockFile);
    }
}
