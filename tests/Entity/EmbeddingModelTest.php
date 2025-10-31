<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DifyClientBundle\Entity\EmbeddingModel;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(EmbeddingModel::class)]
final class EmbeddingModelTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): EmbeddingModel
    {
        return new EmbeddingModel();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'modelName' => ['modelName', 'text-embedding-ada-002'];
        yield 'provider' => ['provider', 'openai'];
        yield 'displayName' => ['displayName', 'OpenAI Ada v2'];
        yield 'description' => ['description', '最新的OpenAI嵌入模型，具有出色的性能'];
        yield 'dimensions' => ['dimensions', 1536];
        yield 'maxTokens' => ['maxTokens', 8191];
        yield 'modelType' => ['modelType', 'text'];
        yield 'available' => ['available', false];
        yield 'default' => ['default', true];
        yield 'pricePerThousandTokens' => ['pricePerThousandTokens', 0.0001];
        yield 'currency' => ['currency', 'USD'];
    }

    public function testCreateEmbeddingModelWithDefaultValuesShouldSucceed(): void
    {
        $model = $this->createEntity();

        $this->assertNull($model->getId());
        $this->assertNull($model->getDescription());
        $this->assertNull($model->getDimensions());
        $this->assertNull($model->getMaxTokens());
        $this->assertNull($model->getModelType());
        $this->assertNull($model->getSupportedLanguages());
        $this->assertTrue($model->isAvailable());
        $this->assertFalse($model->isDefault());
        $this->assertNull($model->getPricePerThousandTokens());
        $this->assertNull($model->getCurrency());
        $this->assertNull($model->getMetadata());
        $this->assertNull($model->getLastSyncAt());
    }

    public function testSetModelNameShouldUpdateValue(): void
    {
        $model = $this->createEntity();
        $modelName = 'text-embedding-3-large';

        $model->setModelName($modelName);

        $this->assertEquals($modelName, $model->getModelName());
    }

    public function testSetProviderShouldUpdateValue(): void
    {
        $model = $this->createEntity();
        $provider = 'azure';

        $model->setProvider($provider);

        $this->assertEquals($provider, $model->getProvider());
    }

    public function testSetDisplayNameShouldUpdateValue(): void
    {
        $model = $this->createEntity();
        $displayName = 'OpenAI Text Embedding 3 Large';

        $model->setDisplayName($displayName);

        $this->assertEquals($displayName, $model->getDisplayName());
    }

    public function testSetDescriptionShouldUpdateValue(): void
    {
        $model = $this->createEntity();
        $description = '最新的高维度嵌入模型，适用于语义搜索和相似度匹配';

        $model->setDescription($description);

        $this->assertEquals($description, $model->getDescription());
    }

    public function testSetDescriptionWithNullShouldAcceptNull(): void
    {
        $model = $this->createEntity();
        $model->setDescription('原始描述');

        $model->setDescription(null);

        $this->assertNull($model->getDescription());
    }

    public function testSetDimensionsShouldUpdateValue(): void
    {
        $model = $this->createEntity();
        $dimensions = 3072;

        $model->setDimensions($dimensions);

        $this->assertEquals($dimensions, $model->getDimensions());
    }

    public function testSetDimensionsWithNullShouldAcceptNull(): void
    {
        $model = $this->createEntity();
        $model->setDimensions(1536);

        $model->setDimensions(null);

        $this->assertNull($model->getDimensions());
    }

    public function testSetMaxTokensShouldUpdateValue(): void
    {
        $model = $this->createEntity();
        $maxTokens = 8191;

        $model->setMaxTokens($maxTokens);

        $this->assertEquals($maxTokens, $model->getMaxTokens());
    }

    public function testSetMaxTokensWithNullShouldAcceptNull(): void
    {
        $model = $this->createEntity();
        $model->setMaxTokens(4096);

        $model->setMaxTokens(null);

        $this->assertNull($model->getMaxTokens());
    }

    public function testSetModelTypeShouldUpdateValue(): void
    {
        $model = $this->createEntity();
        $modelType = 'multimodal';

        $model->setModelType($modelType);

        $this->assertEquals($modelType, $model->getModelType());
    }

    public function testSetModelTypeWithNullShouldAcceptNull(): void
    {
        $model = $this->createEntity();
        $model->setModelType('text');

        $model->setModelType(null);

        $this->assertNull($model->getModelType());
    }

    public function testSetSupportedLanguagesShouldUpdateValue(): void
    {
        $model = $this->createEntity();
        $supportedLanguages = ['zh-CN', 'en-US', 'ja-JP', 'ko-KR'];

        $model->setSupportedLanguages($supportedLanguages);

        $this->assertEquals($supportedLanguages, $model->getSupportedLanguages());
    }

    public function testSetSupportedLanguagesWithNullShouldAcceptNull(): void
    {
        $model = $this->createEntity();
        $model->setSupportedLanguages(['en-US', 'zh-CN']);

        $model->setSupportedLanguages(null);

        $this->assertNull($model->getSupportedLanguages());
    }

    public function testSetAvailableShouldUpdateValue(): void
    {
        $model = $this->createEntity();

        $model->setAvailable(false);

        $this->assertFalse($model->isAvailable());

        $model->setAvailable(true);
        $this->assertTrue($model->isAvailable());
    }

    public function testSetDefaultShouldUpdateValue(): void
    {
        $model = $this->createEntity();

        $model->setIsDefault(true);

        $this->assertTrue($model->isDefault());

        $model->setIsDefault(false);
        $this->assertFalse($model->isDefault());
    }

    public function testSetPricePerThousandTokensShouldUpdateValue(): void
    {
        $model = $this->createEntity();
        $price = 0.0004;

        $model->setPricePerThousandTokens($price);

        $this->assertEquals($price, $model->getPricePerThousandTokens());
    }

    public function testSetPricePerThousandTokensWithNullShouldAcceptNull(): void
    {
        $model = $this->createEntity();
        $model->setPricePerThousandTokens(0.0001);

        $model->setPricePerThousandTokens(null);

        $this->assertNull($model->getPricePerThousandTokens());
    }

    public function testSetCurrencyShouldUpdateValue(): void
    {
        $model = $this->createEntity();
        $currency = 'EUR';

        $model->setCurrency($currency);

        $this->assertEquals($currency, $model->getCurrency());
    }

    public function testSetCurrencyWithNullShouldAcceptNull(): void
    {
        $model = $this->createEntity();
        $model->setCurrency('USD');

        $model->setCurrency(null);

        $this->assertNull($model->getCurrency());
    }

    public function testSetMetadataShouldUpdateValue(): void
    {
        $model = $this->createEntity();
        $metadata = [
            'api_version' => '2023-05-15',
            'region' => 'us-east-1',
            'performance_tier' => 'standard',
            'batch_size_limit' => 100,
        ];

        $model->setMetadata($metadata);

        $this->assertEquals($metadata, $model->getMetadata());
    }

    public function testSetMetadataWithNullShouldAcceptNull(): void
    {
        $model = $this->createEntity();
        $model->setMetadata(['key' => 'value']);

        $model->setMetadata(null);

        $this->assertNull($model->getMetadata());
    }

    public function testSetLastSyncAtShouldUpdateValue(): void
    {
        $model = $this->createEntity();
        $lastSyncAt = new \DateTimeImmutable('2024-01-15 10:30:00');

        $model->setLastSyncAt($lastSyncAt);

        $this->assertEquals($lastSyncAt, $model->getLastSyncAt());
    }

    public function testSetLastSyncAtWithNullShouldAcceptNull(): void
    {
        $model = $this->createEntity();
        $lastSyncAt = new \DateTimeImmutable('2024-01-15 10:30:00');
        $model->setLastSyncAt($lastSyncAt);

        $model->setLastSyncAt(null);

        $this->assertNull($model->getLastSyncAt());
    }

    public function testToStringShouldReturnDisplayNameAndProvider(): void
    {
        $model = $this->createEntity();
        $model->setDisplayName('OpenAI Ada v2');
        $model->setProvider('openai');

        $result = (string) $model;

        $this->assertEquals('OpenAI Ada v2 (openai)', $result);
    }

    public function testEmbeddingModelShouldAcceptLongDescription(): void
    {
        $model = $this->createEntity();
        $longDescription = str_repeat('这是一个功能强大的嵌入模型，提供高质量的语义向量表示。', 100);

        $model->setDescription($longDescription);

        $this->assertEquals($longDescription, $model->getDescription());
    }

    public function testEmbeddingModelShouldAcceptExtensiveSupportedLanguages(): void
    {
        $model = $this->createEntity();
        $extensiveLanguages = [
            'zh-CN', 'zh-TW', 'en-US', 'en-GB', 'ja-JP', 'ko-KR',
            'fr-FR', 'de-DE', 'es-ES', 'it-IT', 'pt-BR', 'ru-RU',
            'ar-SA', 'hi-IN', 'th-TH', 'vi-VN', 'nl-NL', 'sv-SE',
        ];

        $model->setSupportedLanguages($extensiveLanguages);

        $this->assertEquals($extensiveLanguages, $model->getSupportedLanguages());
    }

    public function testEmbeddingModelShouldAcceptComplexMetadata(): void
    {
        $model = $this->createEntity();
        $complexMetadata = [
            'api_config' => [
                'version' => '2023-12-01-preview',
                'endpoint' => 'https://api.openai.com/v1/embeddings',
                'rate_limit' => 3000,
                'timeout' => 30,
            ],
            'model_specs' => [
                'architecture' => 'transformer',
                'training_data_cutoff' => '2023-04-01',
                'context_length' => 8191,
                'output_dimensions' => 1536,
            ],
            'performance_metrics' => [
                'accuracy_score' => 0.89,
                'latency_p95_ms' => 150,
                'throughput_rps' => 1000,
            ],
            'pricing' => [
                'model' => 'text-embedding-ada-002',
                'usage_type' => 'input_tokens',
                'price_per_1k_tokens' => 0.0001,
                'currency' => 'USD',
            ],
            'capabilities' => [
                'multilingual' => true,
                'code_understanding' => true,
                'semantic_search' => true,
                'clustering' => true,
            ],
        ];

        $model->setMetadata($complexMetadata);

        $this->assertEquals($complexMetadata, $model->getMetadata());
    }
}
