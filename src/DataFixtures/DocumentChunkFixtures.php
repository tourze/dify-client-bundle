<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\Document;
use Tourze\DifyClientBundle\Entity\DocumentChunk;

class DocumentChunkFixtures extends Fixture implements DependentFixtureInterface
{
    public const DOCUMENT_CHUNK_REFERENCE = 'document-chunk-1';

    public function load(ObjectManager $manager): void
    {
        /** @var Document $document */
        $document = $this->getReference(DocumentFixtures::DOCUMENT_REFERENCE, Document::class);

        $chunk = new DocumentChunk();
        $chunk->setSegmentId('chunk-123');
        $chunk->setDocument($document);
        $chunk->setContent('人工智能的定义和基本概念...');
        $chunk->setPosition(0);
        $chunk->setWordCount(200);
        $chunk->setTokenCount(150);
        $chunk->setCharacterCount(400);
        $chunk->setEnabled(true);
        $chunk->setMetadata([
            'page' => 1,
            'section' => 'introduction',
        ]);

        $manager->persist($chunk);
        $manager->flush();

        $this->addReference(self::DOCUMENT_CHUNK_REFERENCE, $chunk);
    }

    /** @return array<class-string<FixtureInterface>> */
    public function getDependencies(): array
    {
        return [DocumentFixtures::class];
    }
}
