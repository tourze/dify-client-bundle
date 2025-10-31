<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(DifySetting::class)]
final class DifySettingTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): DifySetting
    {
        return new DifySetting();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', 'Test Dify Setting'];
        yield 'apiKey' => ['apiKey', 'app-test-key-123456'];
        yield 'baseUrl' => ['baseUrl', 'https://api.test.dify.ai/v1'];
        yield 'batchThreshold' => ['batchThreshold', 10];
        yield 'timeout' => ['timeout', 30];
        yield 'retryAttempts' => ['retryAttempts', 3];
        yield 'active' => ['active', true];
    }

    public function testCreateDifySettingWithDefaultValuesShouldSucceed(): void
    {
        $setting = $this->createEntity();

        $this->assertNull($setting->getId());
        $this->assertEquals(5, $setting->getBatchThreshold());
        $this->assertEquals(30, $setting->getTimeout());
        $this->assertEquals(3, $setting->getRetryAttempts());
        $this->assertFalse($setting->isActive());
    }

    public function testSetNameShouldUpdateValue(): void
    {
        $setting = $this->createEntity();
        $name = 'Test Dify App';

        $setting->setName($name);

        $this->assertEquals($name, $setting->getName());
    }

    public function testSetApiKeyShouldUpdateValue(): void
    {
        $setting = $this->createEntity();
        $apiKey = 'app-12345678901234567890';

        $setting->setApiKey($apiKey);

        $this->assertEquals($apiKey, $setting->getApiKey());
    }

    public function testSetBaseUrlShouldUpdateValue(): void
    {
        $setting = $this->createEntity();
        $baseUrl = 'https://api.dify.ai/v1';

        $setting->setBaseUrl($baseUrl);

        $this->assertEquals($baseUrl, $setting->getBaseUrl());
    }

    public function testSetBatchThresholdShouldUpdateValue(): void
    {
        $setting = $this->createEntity();
        $threshold = 10;

        $setting->setBatchThreshold($threshold);

        $this->assertEquals($threshold, $setting->getBatchThreshold());
    }

    public function testSetBatchThresholdWithMinimumValueShouldWork(): void
    {
        $setting = $this->createEntity();

        $setting->setBatchThreshold(1);

        $this->assertEquals(1, $setting->getBatchThreshold());
    }

    public function testSetBatchThresholdWithMaximumValueShouldWork(): void
    {
        $setting = $this->createEntity();

        $setting->setBatchThreshold(1000);

        $this->assertEquals(1000, $setting->getBatchThreshold());
    }

    public function testSetTimeoutShouldUpdateValue(): void
    {
        $setting = $this->createEntity();
        $timeout = 60;

        $setting->setTimeout($timeout);

        $this->assertEquals($timeout, $setting->getTimeout());
    }

    public function testSetTimeoutWithMinimumValueShouldWork(): void
    {
        $setting = $this->createEntity();

        $setting->setTimeout(1);

        $this->assertEquals(1, $setting->getTimeout());
    }

    public function testSetTimeoutWithMaximumValueShouldWork(): void
    {
        $setting = $this->createEntity();

        $setting->setTimeout(300);

        $this->assertEquals(300, $setting->getTimeout());
    }

    public function testSetRetryAttemptsShouldUpdateValue(): void
    {
        $setting = $this->createEntity();
        $retryAttempts = 5;

        $setting->setRetryAttempts($retryAttempts);

        $this->assertEquals($retryAttempts, $setting->getRetryAttempts());
    }

    public function testSetRetryAttemptsWithZeroShouldWork(): void
    {
        $setting = $this->createEntity();

        $setting->setRetryAttempts(0);

        $this->assertEquals(0, $setting->getRetryAttempts());
    }

    public function testSetRetryAttemptsWithMaximumValueShouldWork(): void
    {
        $setting = $this->createEntity();

        $setting->setRetryAttempts(10);

        $this->assertEquals(10, $setting->getRetryAttempts());
    }

    public function testSetActiveShouldUpdateValue(): void
    {
        $setting = $this->createEntity();

        $setting->setActive(true);

        $this->assertTrue($setting->isActive());
    }

    public function testSetActiveWithFalseShouldWork(): void
    {
        $setting = $this->createEntity();
        $setting->setActive(true);

        $setting->setActive(false);

        $this->assertFalse($setting->isActive());
    }

    public function testToStringShouldReturnName(): void
    {
        $setting = $this->createEntity();
        $name = 'Production Dify App';
        $setting->setName($name);

        $result = (string) $setting;

        $this->assertEquals($name, $result);
    }

    public function testCompleteConfigurationShouldWork(): void
    {
        $setting = $this->createEntity();

        $setting->setName('Test Configuration');
        $setting->setApiKey('app-test-key-123456');
        $setting->setBaseUrl('https://api.test.dify.ai/v1');
        $setting->setBatchThreshold(15);
        $setting->setTimeout(45);
        $setting->setRetryAttempts(5);
        $setting->setActive(true);

        $this->assertEquals('Test Configuration', $setting->getName());
        $this->assertEquals('app-test-key-123456', $setting->getApiKey());
        $this->assertEquals('https://api.test.dify.ai/v1', $setting->getBaseUrl());
        $this->assertEquals(15, $setting->getBatchThreshold());
        $this->assertEquals(45, $setting->getTimeout());
        $this->assertEquals(5, $setting->getRetryAttempts());
        $this->assertTrue($setting->isActive());
    }

    public function testSetterMethodsShouldReturnVoid(): void
    {
        $setting = $this->createEntity();

        // Test that all setter methods work correctly (they return void)
        $setting->setName('Fluent Test');
        $setting->setApiKey('app-fluent-123');
        $setting->setBaseUrl('https://api.fluent.test/v1');
        $setting->setBatchThreshold(8);
        $setting->setTimeout(25);
        $setting->setRetryAttempts(2);
        $setting->setActive(false);

        // Verify values were set
        $this->assertEquals('Fluent Test', $setting->getName());
        $this->assertEquals('app-fluent-123', $setting->getApiKey());
        $this->assertEquals('https://api.fluent.test/v1', $setting->getBaseUrl());
        $this->assertEquals(8, $setting->getBatchThreshold());
        $this->assertEquals(25, $setting->getTimeout());
        $this->assertEquals(2, $setting->getRetryAttempts());
        $this->assertFalse($setting->isActive());
    }

    public function testLongApiKeyShouldBeAccepted(): void
    {
        $setting = $this->createEntity();
        $longApiKey = 'app-' . str_repeat('1234567890', 24); // 250 characters

        $setting->setApiKey($longApiKey);

        $this->assertEquals($longApiKey, $setting->getApiKey());
    }

    public function testLongNameShouldBeAccepted(): void
    {
        $setting = $this->createEntity();
        $longName = str_repeat('Test Configuration Name ', 10); // About 230 characters

        $setting->setName($longName);

        $this->assertEquals($longName, $setting->getName());
    }
}
