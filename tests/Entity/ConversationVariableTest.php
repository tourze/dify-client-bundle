<?php

namespace Tourze\DifyClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\ConversationVariable;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(ConversationVariable::class)]
final class ConversationVariableTest extends AbstractEntityTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的设置逻辑
    }

    protected function createEntity(): ConversationVariable
    {
        return new ConversationVariable();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', 'test_variable'];
        yield 'value' => ['value', 'test_value'];
        yield 'type' => ['type', 'number'];
        yield 'description' => ['description', '这是一个测试变量'];
        yield 'required' => ['required', true];
    }

    public function testCreateConversationVariableWithDefaultValuesShouldSucceed(): void
    {
        $variable = $this->createEntity();

        $this->assertNull($variable->getId());
        $this->assertNull($variable->getValue());
        $this->assertEquals('string', $variable->getType());
        $this->assertNull($variable->getDescription());
        $this->assertFalse($variable->isRequired());
        $this->assertNull($variable->getConfig());
    }

    public function testSetConversationShouldUpdateValue(): void
    {
        $variable = $this->createEntity();
        $conversation = $this->createMock(Conversation::class);

        $variable->setConversation($conversation);

        $this->assertSame($conversation, $variable->getConversation());
    }

    public function testSetNameShouldUpdateValue(): void
    {
        $variable = $this->createEntity();
        $name = 'user_input';

        $variable->setName($name);

        $this->assertEquals($name, $variable->getName());
    }

    public function testSetValueShouldUpdateValue(): void
    {
        $variable = $this->createEntity();
        $value = 'test_value';

        $variable->setValue($value);

        $this->assertEquals($value, $variable->getValue());
    }

    public function testSetValueWithNullShouldAcceptNull(): void
    {
        $variable = $this->createEntity();
        $variable->setValue('original_value');

        $variable->setValue(null);

        $this->assertNull($variable->getValue());
    }

    public function testSetTypeShouldUpdateValue(): void
    {
        $variable = $this->createEntity();
        $type = 'boolean';

        $variable->setType($type);

        $this->assertEquals($type, $variable->getType());
    }

    #[TestWith(['string'], 'string type')]
    #[TestWith(['number'], 'number type')]
    #[TestWith(['boolean'], 'boolean type')]
    #[TestWith(['object'], 'object type')]
    #[TestWith(['array'], 'array type')]
    public function testSetTypeWithValidValuesShouldSucceed(string $type): void
    {
        $variable = $this->createEntity();

        $variable->setType($type);

        $this->assertEquals($type, $variable->getType());
    }

    public function testSetDescriptionShouldUpdateValue(): void
    {
        $variable = $this->createEntity();
        $description = '这是一个用户输入变量';

        $variable->setDescription($description);

        $this->assertEquals($description, $variable->getDescription());
    }

    public function testSetDescriptionWithNullShouldAcceptNull(): void
    {
        $variable = $this->createEntity();
        $variable->setDescription('原始描述');

        $variable->setDescription(null);

        $this->assertNull($variable->getDescription());
    }

    public function testSetRequiredShouldUpdateValue(): void
    {
        $variable = $this->createEntity();

        $variable->setRequired(true);

        $this->assertTrue($variable->isRequired());

        $variable->setRequired(false);
        $this->assertFalse($variable->isRequired());
    }

    public function testSetConfigShouldUpdateValue(): void
    {
        $variable = $this->createEntity();
        $config = [
            'default' => 'default_value',
            'validation' => ['min' => 1, 'max' => 100],
        ];

        $variable->setConfig($config);

        $this->assertEquals($config, $variable->getConfig());
    }

    public function testSetConfigWithNullShouldAcceptNull(): void
    {
        $variable = $this->createEntity();
        $variable->setConfig(['key' => 'value']);

        $variable->setConfig(null);

        $this->assertNull($variable->getConfig());
    }

    public function testGetParsedValueWithStringShouldReturnString(): void
    {
        $variable = $this->createEntity();
        $variable->setType('string');
        $variable->setValue('test_value');

        $result = $variable->getParsedValue();

        $this->assertEquals('test_value', $result);
    }

    public function testGetParsedValueWithNumberShouldReturnFloat(): void
    {
        $variable = $this->createEntity();
        $variable->setType('number');
        $variable->setValue('123.45');

        $result = $variable->getParsedValue();

        $this->assertEquals(123.45, $result);
    }

    public function testGetParsedValueWithBooleanShouldReturnBool(): void
    {
        $variable = $this->createEntity();
        $variable->setType('boolean');
        $variable->setValue('true');

        $result = $variable->getParsedValue();

        $this->assertTrue($result);

        $variable->setValue('false');
        $result = $variable->getParsedValue();
        $this->assertFalse($result);
    }

    #[TestWith(['true', true], 'true string')]
    #[TestWith(['TRUE', true], 'TRUE string')]
    #[TestWith(['1', true], '1 string')]
    #[TestWith(['yes', true], 'yes string')]
    #[TestWith(['YES', true], 'YES string')]
    #[TestWith(['false', false], 'false string')]
    #[TestWith(['0', false], '0 string')]
    #[TestWith(['no', false], 'no string')]
    #[TestWith(['', false], 'empty string')]
    public function testGetParsedValueWithBooleanValuesShouldReturnCorrectBool(string $value, bool $expected): void
    {
        $variable = $this->createEntity();
        $variable->setType('boolean');
        $variable->setValue($value);

        $result = $variable->getParsedValue();

        $this->assertEquals($expected, $result);
    }

    public function testGetParsedValueWithObjectShouldReturnArray(): void
    {
        $variable = $this->createEntity();
        $variable->setType('object');
        $variable->setValue('{"key": "value", "number": 42}');

        $result = $variable->getParsedValue();

        $this->assertEquals(['key' => 'value', 'number' => 42], $result);
    }

    public function testGetParsedValueWithArrayShouldReturnArray(): void
    {
        $variable = $this->createEntity();
        $variable->setType('array');
        $variable->setValue('["item1", "item2", 42]');

        $result = $variable->getParsedValue();

        $this->assertEquals(['item1', 'item2', 42], $result);
    }

    public function testGetParsedValueWithNullShouldReturnNull(): void
    {
        $variable = $this->createEntity();
        $variable->setType('string');
        $variable->setValue(null);

        $result = $variable->getParsedValue();

        $this->assertNull($result);
    }

    public function testSetValueWithTypeStringShouldSetStringTypeAndValue(): void
    {
        $variable = $this->createEntity();

        $variable->setValueWithType('test string');

        $this->assertEquals('test string', $variable->getValue());
        $this->assertEquals('string', $variable->getType());
    }

    public function testSetValueWithTypeNumberShouldSetNumberTypeAndValue(): void
    {
        $variable = $this->createEntity();

        $variable->setValueWithType(123.45);

        $this->assertEquals('123.45', $variable->getValue());
        $this->assertEquals('number', $variable->getType());
    }

    public function testSetValueWithTypeBooleanShouldSetBooleanTypeAndValue(): void
    {
        $variable = $this->createEntity();

        $variable->setValueWithType(true);
        $this->assertEquals('true', $variable->getValue());
        $this->assertEquals('boolean', $variable->getType());

        $variable->setValueWithType(false);
        $this->assertEquals('false', $variable->getValue());
        $this->assertEquals('boolean', $variable->getType());
    }

    public function testSetValueWithTypeArrayShouldSetArrayTypeAndJsonValue(): void
    {
        $variable = $this->createEntity();
        $array = ['item1', 'item2', 42];

        $variable->setValueWithType($array);

        $this->assertEquals('["item1","item2",42]', $variable->getValue());
        $this->assertEquals('array', $variable->getType());
    }

    public function testSetValueWithTypeObjectShouldSetObjectTypeAndJsonValue(): void
    {
        $variable = $this->createEntity();
        $object = (object) ['key' => 'value', 'number' => 42];

        $variable->setValueWithType($object);

        $this->assertEquals('{"key":"value","number":42}', $variable->getValue());
        $this->assertEquals('object', $variable->getType());
    }

    public function testSetValueWithTypeNullShouldSetNullValueAndStringType(): void
    {
        $variable = $this->createEntity();

        $variable->setValueWithType(null);

        $this->assertNull($variable->getValue());
        $this->assertEquals('string', $variable->getType());
    }

    public function testToStringShouldReturnNameTypeAndValue(): void
    {
        $variable = $this->createEntity();
        $variable->setName('test_var');
        $variable->setValue('test_value');
        $variable->setType('string');

        $result = (string) $variable;

        $this->assertEquals('test_var: test_value (string)', $result);
    }

    public function testToStringWithNullValueShouldShowNull(): void
    {
        $variable = $this->createEntity();
        $variable->setName('test_var');
        $variable->setValue(null);
        $variable->setType('string');

        $result = (string) $variable;

        $this->assertEquals('test_var: null (string)', $result);
    }

    public function testVariableShouldAcceptComplexConfig(): void
    {
        $variable = $this->createEntity();
        $complexConfig = [
            'default' => '{"theme": "light"}',
            'validation' => [
                'required' => true,
                'type' => 'object',
                'properties' => [
                    'theme' => ['enum' => ['light', 'dark']],
                ],
            ],
            'ui' => [
                'widget' => 'select',
                'options' => ['light', 'dark'],
            ],
        ];

        $variable->setConfig($complexConfig);

        $this->assertEquals($complexConfig, $variable->getConfig());
    }
}
