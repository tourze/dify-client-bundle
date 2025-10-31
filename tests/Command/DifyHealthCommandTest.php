<?php

namespace Tourze\DifyClientBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\Builder\InvocationMocker;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\DifyClientBundle\Command\DifyHealthCommand;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(DifyHealthCommand::class)]
#[RunTestsInSeparateProcesses]
final class DifyHealthCommandTest extends AbstractCommandTestCase
{
    private HttpClientInterface $httpClient;

    /**
     * 获取注入了 Mock HttpClient 的 CommandTester
     * 注意：此方法会清除已有的 commandTester 缓存，确保每次都使用新的 Mock
     */
    protected function getCommandTester(): CommandTester
    {
        // 每次都重新创建 Mock，确保测试隔离
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        // 替换容器中的 HttpClient 为 Mock
        // 注意：必须在获取命令实例之前完成替换
        self::getContainer()->set(HttpClientInterface::class, $this->httpClient);

        // 从容器中获取命令实例（此时会注入 Mock 的 HttpClient）
        $command = self::getContainer()->get(DifyHealthCommand::class);
        self::assertInstanceOf(Command::class, $command);

        return new CommandTester($command);
    }

    protected function onSetUp(): void
    {
        // Mock 的设置在 getCommandTester() 中完成
    }

    private function clearAllDifySettings(): void
    {
        $settingRepository = self::getContainer()->get(DifySettingRepository::class);
        self::assertInstanceOf(DifySettingRepository::class, $settingRepository);

        // 使用公共API而非访问protected方法
        $em = self::getEntityManager();

        $em->getConnection()->executeStatement('DELETE FROM dify_setting');
        $em->clear();
    }

    public function testExecuteWithNoActiveSettingShouldReturnFailure(): void
    {
        // Arrange: 清理并确保没有激活的配置
        $this->clearAllDifySettings();

        // Act: 执行命令
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        // Assert: 返回失败状态码
        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('没有找到激活的 Dify 配置', $output);
        $this->assertStringContainsString('发现问题', $output);
    }

    public function testExecuteWithActiveSettingAndHealthyApiShouldReturnSuccess(): void
    {
        // Arrange: 清理数据并创建激活的配置
        $this->clearAllDifySettings();
        $setting = new DifySetting();
        $setting->setName('测试配置');
        $setting->setBaseUrl('https://api.dify.ai');
        $setting->setApiKey('test-api-key');
        $setting->setActive(true);

        $this->persistAndFlush($setting);

        // 先调用 getCommandTester() 以初始化 Mock
        $commandTester = $this->getCommandTester();

        // Mock HTTP 响应
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        // 设置 Mock 期望：使用 callback 验证第三个参数为数组类型
        /** @var InvocationMocker $requestExpectation */
        $requestExpectation = $this->httpClient->expects($this->once());
        $requestExpectation->method('request')->with(
            'GET',
            'https://api.dify.ai/parameters',
            self::callback(static fn (mixed $value): bool => is_array($value))
        )->willReturn($response);

        // Act: 执行命令
        $exitCode = $commandTester->execute([]);

        // Assert: 返回成功状态码
        $output = $commandTester->getDisplay();
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('找到激活的配置：测试配置', $output);
        $this->assertStringContainsString('Dify API 连接正常', $output);
        $this->assertStringContainsString('数据库连接正常', $output);
        $this->assertStringContainsString('消息队列状态正常', $output);
        $this->assertStringContainsString('所有检查通过', $output);
    }

    public function testExecuteWithApiConnectionFailureShouldReturnFailure(): void
    {
        // Arrange: 清理数据并创建激活的配置
        $this->clearAllDifySettings();
        $setting = new DifySetting();
        $setting->setName('测试配置');
        $setting->setBaseUrl('https://api.dify.ai');
        $setting->setApiKey('invalid-key');
        $setting->setActive(true);

        $this->persistAndFlush($setting);

        // 先调用 getCommandTester() 以初始化 Mock
        $commandTester = $this->getCommandTester();

        // Mock HTTP 异常
        /** @var InvocationMocker $requestExpectation */
        $requestExpectation = $this->httpClient->expects($this->once());
        $requestExpectation->method('request')->willThrowException(new \Exception('Connection failed'));

        // Act: 执行命令
        $exitCode = $commandTester->execute([]);

        // Assert: 返回失败状态码
        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Dify API 连接失败：Connection failed', $output);
        $this->assertStringContainsString('发现问题', $output);
    }

    public function testExecuteWithApiNon200ResponseShouldReturnFailure(): void
    {
        // Arrange: 清理数据并创建激活的配置
        $this->clearAllDifySettings();
        $setting = new DifySetting();
        $setting->setName('测试配置');
        $setting->setBaseUrl('https://api.dify.ai');
        $setting->setApiKey('unauthorized-key');
        $setting->setActive(true);

        $this->persistAndFlush($setting);

        // 先调用 getCommandTester() 以初始化 Mock
        $commandTester = $this->getCommandTester();

        // Mock HTTP 响应返回 401
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(401);

        /** @var InvocationMocker $requestExpectation */
        $requestExpectation = $this->httpClient->expects($this->once());
        $requestExpectation->method('request')->willReturn($response);

        // Act: 执行命令
        $exitCode = $commandTester->execute([]);

        // Assert: 返回失败状态码
        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Dify API 返回错误状态码：401', $output);
        $this->assertStringContainsString('发现问题', $output);
    }

    public function testExecuteWithBaseUrlTrailingSlashShouldNormalizeUrl(): void
    {
        // Arrange: 清理数据并创建带尾部斜杠的配置
        $this->clearAllDifySettings();
        $setting = new DifySetting();
        $setting->setName('测试配置');
        $setting->setBaseUrl('https://api.dify.ai/');
        $setting->setApiKey('test-key');
        $setting->setActive(true);

        $this->persistAndFlush($setting);

        // 先调用 getCommandTester() 以初始化 Mock
        $commandTester = $this->getCommandTester();

        // Mock HTTP 响应
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        /** @var InvocationMocker $requestExpectation */
        $requestExpectation = $this->httpClient->expects($this->once());
        $requestExpectation->method('request')->with(
            'GET',
            'https://api.dify.ai/parameters',
            self::callback(static fn (mixed $value): bool => is_array($value))
        )->willReturn($response);

        // Act: 执行命令
        $exitCode = $commandTester->execute([]);

        // Assert: 成功执行
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function testExecuteWithMultipleInactiveSettingsShouldStillWork(): void
    {
        // Arrange: 清理数据并创建多个非激活配置和一个激活配置
        $this->clearAllDifySettings();
        $inactiveSetting1 = new DifySetting();
        $inactiveSetting1->setName('非激活配置1');
        $inactiveSetting1->setBaseUrl('https://api1.dify.ai');
        $inactiveSetting1->setApiKey('key1');
        $inactiveSetting1->setActive(false);

        $activeSetting = new DifySetting();
        $activeSetting->setName('激活配置');
        $activeSetting->setBaseUrl('https://api.dify.ai');
        $activeSetting->setApiKey('active-key');
        $activeSetting->setActive(true);

        $inactiveSetting2 = new DifySetting();
        $inactiveSetting2->setName('非激活配置2');
        $inactiveSetting2->setBaseUrl('https://api2.dify.ai');
        $inactiveSetting2->setApiKey('key2');
        $inactiveSetting2->setActive(false);

        $this->persistAndFlush($inactiveSetting1);
        $this->persistAndFlush($activeSetting);
        $this->persistAndFlush($inactiveSetting2);

        // 先调用 getCommandTester() 以初始化 Mock
        $commandTester = $this->getCommandTester();

        // Mock HTTP 响应
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        /** @var InvocationMocker $requestExpectation */
        $requestExpectation = $this->httpClient->expects($this->once());
        $requestExpectation->method('request')->with(
            'GET',
            'https://api.dify.ai/parameters',
            self::callback(static fn (mixed $value): bool => is_array($value))
        )->willReturn($response);

        // Act: 执行命令
        $exitCode = $commandTester->execute([]);

        // Assert: 使用正确的激活配置
        $output = $commandTester->getDisplay();

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('找到激活的配置：激活配置', $output);
        $this->assertStringContainsString('数据库连接正常', $output);
    }

    public function testCommandNameConstant(): void
    {
        // Assert: 验证命令名称常量
        $this->assertEquals('dify:health', DifyHealthCommand::NAME);
    }

    public function testCommandConfiguration(): void
    {
        // Arrange: 从容器获取命令实例
        $command = self::getContainer()->get(DifyHealthCommand::class);
        self::assertInstanceOf(DifyHealthCommand::class, $command);

        // Assert: 验证命令配置
        $this->assertEquals('dify:health', $command->getName());
        $this->assertEquals('检查 Dify 系统健康状态', $command->getDescription());
    }
}
