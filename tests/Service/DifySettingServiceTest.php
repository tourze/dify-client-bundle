<?php

namespace Tourze\DifyClientBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Service\DifySettingService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(DifySettingService::class)]
#[RunTestsInSeparateProcesses]
final class DifySettingServiceTest extends AbstractIntegrationTestCase
{
    private DifySettingService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(DifySettingService::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(DifySettingService::class, $this->service);
    }
}
