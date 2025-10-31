<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\DifySetting;

class DifySettingFixtures extends Fixture
{
    public const DIFY_SETTING_REFERENCE = 'dify-setting-1';

    public function load(ObjectManager $manager): void
    {
        $setting = new DifySetting();
        $setting->setName('Test Dify Configuration');
        $setting->setBaseUrl('https://api.dify.ai');
        $setting->setApiKey('test-api-key-123');
        $setting->setTimeout(30);
        $setting->setMaxRetries(3);
        $setting->setBatchSize(10);
        $setting->setBatchTimeout(300);
        $setting->setActive(true);
        $setting->setMetadata([
            'environment' => 'test',
            'version' => '1.0.0',
        ]);

        $manager->persist($setting);
        $manager->flush();

        $this->addReference(self::DIFY_SETTING_REFERENCE, $setting);
    }
}
