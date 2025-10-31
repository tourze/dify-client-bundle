<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\DatasetTag;

class DatasetTagFixtures extends Fixture
{
    public const DATASET_TAG_REFERENCE = 'dataset-tag-1';

    public function load(ObjectManager $manager): void
    {
        $tag = new DatasetTag();
        $tag->setTagId('tag-123');
        $tag->setName('AI相关');
        $tag->setDescription('人工智能相关的内容标签');
        $tag->setColor('#3B82F6');
        $tag->setCreatedBy('admin-user');
        $tag->setUsageCount(15);
        $tag->setMetadata([
            'category' => 'technology',
            'priority' => 'high',
            'sort_order' => 1,
            'enabled' => true,
        ]);

        $manager->persist($tag);
        $manager->flush();

        $this->addReference(self::DATASET_TAG_REFERENCE, $tag);
    }
}
