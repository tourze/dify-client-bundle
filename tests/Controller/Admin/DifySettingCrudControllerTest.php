<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\DifyClientBundle\Controller\Admin\DifySettingCrudController;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(DifySettingCrudController::class)]
#[RunTestsInSeparateProcesses]
final class DifySettingCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<DifySetting>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return new DifySettingCrudController();
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '配置名称' => ['配置名称'];
        yield 'Base URL' => ['Base URL'];
        yield '批量阈值' => ['批量阈值'];
        yield '超时时间（秒）' => ['超时时间（秒）'];
        yield '重试次数' => ['重试次数'];
        yield '是否激活' => ['是否激活'];
        yield '创建时间' => ['创建时间'];
    }

    public static function provideNewPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'apiKey' => ['apiKey'];
        yield 'baseUrl' => ['baseUrl'];
        yield 'batchThreshold' => ['batchThreshold'];
        yield 'timeout' => ['timeout'];
        yield 'retryAttempts' => ['retryAttempts'];
        yield 'isActive' => ['isActive'];
        yield 'iframeEmbedCode' => ['iframeEmbedCode'];
    }

    public static function provideEditPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'apiKey' => ['apiKey'];
        yield 'baseUrl' => ['baseUrl'];
        yield 'batchThreshold' => ['batchThreshold'];
        yield 'timeout' => ['timeout'];
        yield 'retryAttempts' => ['retryAttempts'];
        yield 'isActive' => ['isActive'];
        yield 'iframeEmbedCode' => ['iframeEmbedCode'];
    }

    #[Test]
    public function testIndexPageRequiresAuthentication(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage("Access Denied. The user doesn't have ROLE_ADMIN.");

        $client->request('GET', '/admin/dify-client/dify-setting');
    }

    #[Test]
    public function testIndexPageAccessibleWithValidRole(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $this->loginAsAdmin($client);

        $client->request('GET', '/admin/dify-client/dify-setting');

        $this->assertResponseStatusCodeSame(200);
    }

    #[Test]
    public function testControllerStaticMethods(): void
    {
        $entityClass = DifySettingCrudController::getEntityFqcn();
        $this->assertSame('Tourze\DifyClientBundle\Entity\DifySetting', $entityClass);
    }
}
