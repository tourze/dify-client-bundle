<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\DifyClientBundle\Entity\EmbeddingModel;

class EmbeddingModelFixtures extends Fixture
{
    public const EMBEDDING_MODEL_REFERENCE = 'embedding-model-1';

    public function load(ObjectManager $manager): void
    {
        $model = new EmbeddingModel();
        $model->setModelName('text-embedding-ada-002');
        $model->setDisplayName('OpenAI Ada 002');
        $model->setProvider('openai');
        $model->setDimensions(1536);
        $model->setMaxTokens(8191);
        $model->setAvailable(true);
        $model->setDescription('OpenAI的文本嵌入模型，适用于语义搜索');
        $model->setPricePerThousandTokens(0.0001);
        $model->setCurrency('USD');
        $model->setMetadata([
            'api_version' => 'v1',
            'quality' => 'high',
        ]);

        $manager->persist($model);
        $manager->flush();

        $this->addReference(self::EMBEDDING_MODEL_REFERENCE, $model);
    }
}
