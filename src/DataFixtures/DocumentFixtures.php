<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\Dataset;
use Tourze\DifyClientBundle\Entity\Document;

class DocumentFixtures extends Fixture implements DependentFixtureInterface
{
    public const DOCUMENT_REFERENCE = 'document-1';

    public function load(ObjectManager $manager): void
    {
        /** @var Dataset $dataset */
        $dataset = $this->getReference(DatasetFixtures::DATASET_REFERENCE, Dataset::class);

        $document = new Document();
        $document->setDocumentId('doc-123');
        $document->setDataset($dataset);
        $document->setName('AI基础知识.pdf');
        $document->setContent('人工智能是计算机科学的一个分支，旨在创造能够模拟人类智能的系统...');
        $document->setDataSource('upload_file');
        $document->setIndexingTechnique('high_quality');
        $document->setOriginalFilename('ai-basics.pdf');
        $document->setFileSize(1024000);
        $document->setMimeType('application/pdf');
        $document->setProcessingStatus('completed');
        $document->setWordCount(5000);
        $document->setMetadata([
            'author' => 'AI Expert',
            'language' => 'zh-CN',
            'version' => '1.0',
        ]);

        $manager->persist($document);
        $manager->flush();

        $this->addReference(self::DOCUMENT_REFERENCE, $document);
    }

    /** @return array<class-string<FixtureInterface>> */
    public function getDependencies(): array
    {
        return [DatasetFixtures::class];
    }
}
