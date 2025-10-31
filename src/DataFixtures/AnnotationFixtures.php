<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\Annotation;

class AnnotationFixtures extends Fixture
{
    public const ANNOTATION_REFERENCE = 'annotation-1';

    public function load(ObjectManager $manager): void
    {
        $annotation = new Annotation();
        $annotation->setAnnotationId('dify-ann-123');
        $annotation->setQuestion('什么是人工智能？');
        $annotation->setAnswer('人工智能是计算机科学的一个分支，旨在创建能够执行通常需要人类智能的任务的系统。');
        $annotation->setUserId('test-user-123');
        $annotation->setHitCount(5);
        $annotation->setSimilarityThreshold(0.85);
        $annotation->setEnabled(true);
        $annotation->setMetadata([
            'source' => 'manual',
            'domain' => 'ai',
            'confidence' => 0.95,
        ]);

        $manager->persist($annotation);
        $manager->flush();

        $this->addReference(self::ANNOTATION_REFERENCE, $annotation);
    }
}
