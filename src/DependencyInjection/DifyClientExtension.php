<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

final class DifyClientExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('twig', [
            'paths' => [
                dirname(__DIR__, 2) . '/templates' => 'DifyClient',
            ],
        ]);
    }
}
