<?php

namespace Tourze\DifyClientBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\DependencyInjection\DifyClientExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(DifyClientExtension::class)]
final class DifyClientExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    public function testExtensionAlias(): void
    {
        $extension = new DifyClientExtension();
        $this->assertSame('dify_client', $extension->getAlias());
    }

    public function testPrepend(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
