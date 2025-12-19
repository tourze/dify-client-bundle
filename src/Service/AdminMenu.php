<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

/**
 * Dify AI模块的管理菜单
 */
#[Autoconfigure(public: true)]
final readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(private LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        $difyMenu = $item->getChild('Dify AI管理');
        if (null === $difyMenu) {
            $difyMenu = $item->addChild('Dify AI管理');
        }

        // 配置管理
        $difyMenu->addChild('AI配置管理')
            ->setUri($this->linkGenerator->getCurdListPage(DifySetting::class))
            ->setAttribute('icon', 'fas fa-cogs')
        ;
    }
}
