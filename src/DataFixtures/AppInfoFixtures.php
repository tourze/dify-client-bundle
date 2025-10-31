<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\AppInfo;

class AppInfoFixtures extends Fixture
{
    public const APP_INFO_REFERENCE = 'app-info-1';

    public function load(ObjectManager $manager): void
    {
        $appInfo = new AppInfo();
        $appInfo->setAppId('dify-app-123');
        $appInfo->setName('AI 智能助手');
        $appInfo->setMode('chat');
        $appInfo->setDescription('一个强大的AI对话助手应用，支持多轮对话和智能回答。');
        $appInfo->setIcon([
            'type' => 'emoji',
            'value' => '🤖',
        ]);
        $appInfo->setIconBackground('#3B82F6');
        $appInfo->setEnableSite(true);
        $appInfo->setEnableApi(true);
        $appInfo->setMetadata([
            'version' => '1.0.0',
            'category' => 'assistant',
            'language' => 'zh-CN',
            'tags' => ['AI', '助手', '对话'],
        ]);

        $manager->persist($appInfo);
        $manager->flush();

        $this->addReference(self::APP_INFO_REFERENCE, $appInfo);
    }
}
