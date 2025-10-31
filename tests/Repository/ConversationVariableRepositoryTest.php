<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\ConversationVariable;
use Tourze\DifyClientBundle\Enum\ConversationStatus;
use Tourze\DifyClientBundle\Repository\ConversationVariableRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(ConversationVariableRepository::class)]
#[RunTestsInSeparateProcesses]
final class ConversationVariableRepositoryTest extends AbstractRepositoryTestCase
{
    private ConversationVariableRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(ConversationVariableRepository::class);
    }

    protected function getRepository(): ConversationVariableRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): ConversationVariable
    {
        // 创建测试会话
        $conversation = new Conversation();
        $conversation->setConversationId('test-conv-' . uniqid());
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $this->persistAndFlush($conversation);

        $conversationVariable = new ConversationVariable();
        $conversationVariable->setConversation($conversation);
        $conversationVariable->setName('test_variable_' . uniqid());
        $conversationVariable->setValue('test value');
        $conversationVariable->setType('string');
        $conversationVariable->setRequired(false);

        return $conversationVariable;
    }

    private function createTestConversation(string $suffix = ''): Conversation
    {
        $conversation = new Conversation();
        $conversation->setConversationId('test-conversation-' . $suffix . uniqid());
        $conversation->setStatus(ConversationStatus::ACTIVE);
        $this->persistAndFlush($conversation);

        return $conversation;
    }

    public function testFindByConversationShouldReturnVariablesForSpecificConversation(): void
    {
        // Arrange: 清理现有数据并创建两个会话和它们的变量
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM dify_conversation_variable');
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM dify_conversation');
        self::getEntityManager()->clear();

        $conversation1 = $this->createTestConversation('1');
        $conversation2 = $this->createTestConversation('2');

        // 为第一个会话创建变量
        $variable1 = new ConversationVariable();
        $variable1->setConversation($conversation1);
        $variable1->setName('user_name');
        $variable1->setValue('John Doe');
        $variable1->setType('string');
        $variable1->setDescription('User full name');

        $variable2 = new ConversationVariable();
        $variable2->setConversation($conversation1);
        $variable2->setName('user_age');
        $variable2->setValue('25');
        $variable2->setType('number');
        $variable2->setRequired(true);

        // 为第二个会话创建变量
        $variable3 = new ConversationVariable();
        $variable3->setConversation($conversation2);
        $variable3->setName('session_timeout');
        $variable3->setValue('3600');
        $variable3->setType('number');

        $this->persistAndFlush($variable1);
        $this->persistAndFlush($variable2);
        $this->persistAndFlush($variable3);

        // Act: 查找第一个会话的变量
        $conversation1Variables = $this->repository->findByConversation($conversation1);

        // Assert: 只返回第一个会话的变量
        $this->assertCount(2, $conversation1Variables);

        $variableNames = array_map(fn ($var) => $var->getName(), $conversation1Variables);
        $this->assertContains('user_name', $variableNames);
        $this->assertContains('user_age', $variableNames);
        $this->assertNotContains('session_timeout', $variableNames);

        // Act: 查找第二个会话的变量
        $conversation2Variables = $this->repository->findByConversation($conversation2);

        // Assert: 只返回第二个会话的变量
        $this->assertCount(1, $conversation2Variables);
        $this->assertSame('session_timeout', $conversation2Variables[0]->getName());
    }

    public function testFindByConversationWithNoVariablesShouldReturnEmptyArray(): void
    {
        // Arrange: 创建没有变量的会话
        $conversation = $this->createTestConversation('empty');

        // Act: 查找会话变量
        $variables = $this->repository->findByConversation($conversation);

        // Assert: 返回空数组
        $this->assertEmpty($variables);
    }

    public function testFindByConversationAndNameShouldReturnCorrectVariable(): void
    {
        // Arrange: 创建会话和变量
        $conversation = $this->createTestConversation('name-test');

        $variable1 = new ConversationVariable();
        $variable1->setConversation($conversation);
        $variable1->setName('api_key');
        $variable1->setValue('secret123');
        $variable1->setType('string');
        $variable1->setDescription('API authentication key');

        $variable2 = new ConversationVariable();
        $variable2->setConversation($conversation);
        $variable2->setName('debug_mode');
        $variable2->setValue('true');
        $variable2->setType('boolean');

        $this->persistAndFlush($variable1);
        $this->persistAndFlush($variable2);

        // Act: 根据会话和变量名查找
        $foundVariable = $this->repository->findByConversationAndName($conversation, 'api_key');

        // Assert: 验证找到正确的变量
        $this->assertNotNull($foundVariable);
        $this->assertSame('api_key', $foundVariable->getName());
        $this->assertSame('secret123', $foundVariable->getValue());
        $this->assertSame('string', $foundVariable->getType());
        $this->assertSame('API authentication key', $foundVariable->getDescription());
        $this->assertSame($conversation->getId(), $foundVariable->getConversation()->getId());
    }

    public function testFindByConversationAndNameWithNonExistentNameShouldReturnNull(): void
    {
        // Arrange: 创建会话和变量
        $conversation = $this->createTestConversation('non-existent');

        $variable = new ConversationVariable();
        $variable->setConversation($conversation);
        $variable->setName('existing_var');
        $variable->setValue('value');
        $variable->setType('string');
        $this->persistAndFlush($variable);

        // Act: 查找不存在的变量名
        $foundVariable = $this->repository->findByConversationAndName($conversation, 'non_existent_var');

        // Assert: 应该返回null
        $this->assertNull($foundVariable);
    }

    public function testFindRequiredByConversationShouldReturnOnlyRequiredVariables(): void
    {
        // Arrange: 创建会话和必需/非必需变量
        $conversation = $this->createTestConversation('required-test');

        $requiredVar1 = new ConversationVariable();
        $requiredVar1->setConversation($conversation);
        $requiredVar1->setName('required_field_1');
        $requiredVar1->setValue('value1');
        $requiredVar1->setType('string');
        $requiredVar1->setRequired(true);

        $requiredVar2 = new ConversationVariable();
        $requiredVar2->setConversation($conversation);
        $requiredVar2->setName('required_field_2');
        $requiredVar2->setValue('value2');
        $requiredVar2->setType('string');
        $requiredVar2->setRequired(true);

        $optionalVar = new ConversationVariable();
        $optionalVar->setConversation($conversation);
        $optionalVar->setName('optional_field');
        $optionalVar->setValue('optional_value');
        $optionalVar->setType('string');
        $optionalVar->setRequired(false);

        $this->persistAndFlush($requiredVar1);
        $this->persistAndFlush($requiredVar2);
        $this->persistAndFlush($optionalVar);

        // Act: 查找必需变量
        $requiredVariables = $this->repository->findRequiredByConversation($conversation);

        // Assert: 只返回必需变量
        $this->assertCount(2, $requiredVariables);

        $variableNames = array_map(fn ($var) => $var->getName(), $requiredVariables);
        $this->assertContains('required_field_1', $variableNames);
        $this->assertContains('required_field_2', $variableNames);
        $this->assertNotContains('optional_field', $variableNames);

        // 验证所有返回的变量都是必需的
        foreach ($requiredVariables as $variable) {
            $this->assertTrue($variable->isRequired());
        }
    }

    public function testFindRequiredByConversationWithNoRequiredVariablesShouldReturnEmptyArray(): void
    {
        // Arrange: 创建只有非必需变量的会话
        $conversation = $this->createTestConversation('no-required');

        $optionalVar = new ConversationVariable();
        $optionalVar->setConversation($conversation);
        $optionalVar->setName('optional_only');
        $optionalVar->setValue('value');
        $optionalVar->setType('string');
        $optionalVar->setRequired(false);
        $this->persistAndFlush($optionalVar);

        // Act: 查找必需变量
        $requiredVariables = $this->repository->findRequiredByConversation($conversation);

        // Assert: 返回空数组
        $this->assertEmpty($requiredVariables);
    }

    public function testFindByTypeShouldReturnVariablesOfSpecificType(): void
    {
        // Arrange: 清理现有数据并创建不同类型的变量
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM dify_conversation_variable');
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM dify_conversation');
        self::getEntityManager()->clear();

        $conversation1 = $this->createTestConversation('type-test-1');
        $conversation2 = $this->createTestConversation('type-test-2');

        // 创建不同类型的变量
        $stringVar1 = new ConversationVariable();
        $stringVar1->setConversation($conversation1);
        $stringVar1->setName('string_var_1');
        $stringVar1->setValue('string value 1');
        $stringVar1->setType('string');

        $stringVar2 = new ConversationVariable();
        $stringVar2->setConversation($conversation2);
        $stringVar2->setName('string_var_2');
        $stringVar2->setValue('string value 2');
        $stringVar2->setType('string');

        $numberVar = new ConversationVariable();
        $numberVar->setConversation($conversation1);
        $numberVar->setName('number_var');
        $numberVar->setValue('42');
        $numberVar->setType('number');

        $booleanVar = new ConversationVariable();
        $booleanVar->setConversation($conversation1);
        $booleanVar->setName('boolean_var');
        $booleanVar->setValue('true');
        $booleanVar->setType('boolean');

        $this->persistAndFlush($stringVar1);
        $this->persistAndFlush($stringVar2);
        $this->persistAndFlush($numberVar);
        $this->persistAndFlush($booleanVar);

        // Act: 查找字符串类型的变量
        $stringVariables = $this->repository->findByType('string');

        // Assert: 只返回字符串类型的变量
        $this->assertCount(2, $stringVariables);

        $variableNames = array_map(fn ($var) => $var->getName(), $stringVariables);
        $this->assertContains('string_var_1', $variableNames);
        $this->assertContains('string_var_2', $variableNames);
        $this->assertNotContains('number_var', $variableNames);
        $this->assertNotContains('boolean_var', $variableNames);

        // 验证所有返回的变量都是字符串类型
        foreach ($stringVariables as $variable) {
            $this->assertSame('string', $variable->getType());
        }
    }

    public function testFindByTypeWithNonExistentTypeShouldReturnEmptyArray(): void
    {
        // Arrange: 创建一些变量
        $conversation = $this->createTestConversation('non-existent-type');

        $variable = new ConversationVariable();
        $variable->setConversation($conversation);
        $variable->setName('test_var');
        $variable->setValue('value');
        $variable->setType('string');
        $this->persistAndFlush($variable);

        // Act: 查找不存在的类型
        $variables = $this->repository->findByType('unknown_type');

        // Assert: 返回空数组
        $this->assertEmpty($variables);
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新会话变量（未持久化）
        $conversation = $this->createTestConversation('save-test');

        $conversationVariable = new ConversationVariable();
        $conversationVariable->setConversation($conversation);
        $conversationVariable->setName('save_test_var');
        $conversationVariable->setValue('save test value');
        $conversationVariable->setType('string');
        $conversationVariable->setDescription('Test variable for save operation');
        $conversationVariable->setRequired(true);
        $conversationVariable->setConfig(['min_length' => 5, 'max_length' => 100]);

        // Act: 保存会话变量
        $this->repository->save($conversationVariable);

        // Assert: 验证会话变量已持久化
        $this->assertNotNull($conversationVariable->getId());
        $this->assertEntityPersisted($conversationVariable);
    }

    public function testSaveShouldUpdateExistingEntity(): void
    {
        // Arrange: 创建并持久化会话变量
        $conversation = $this->createTestConversation('update-test');

        $conversationVariable = new ConversationVariable();
        $conversationVariable->setConversation($conversation);
        $conversationVariable->setName('update_test_var');
        $conversationVariable->setValue('original value');
        $conversationVariable->setType('string');
        $conversationVariable->setRequired(false);
        $this->persistAndFlush($conversationVariable);

        // Act: 修改并保存
        $conversationVariable->setValue('updated value');
        $conversationVariable->setType('number');
        $conversationVariable->setRequired(true);
        $conversationVariable->setDescription('Updated description');
        $this->repository->save($conversationVariable);

        // Assert: 验证更新已持久化
        self::getEntityManager()->refresh($conversationVariable);
        $this->assertSame('updated value', $conversationVariable->getValue());
        $this->assertSame('number', $conversationVariable->getType());
        $this->assertTrue($conversationVariable->isRequired());
        $this->assertSame('Updated description', $conversationVariable->getDescription());
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化会话变量
        $conversation = $this->createTestConversation('remove-test');

        $conversationVariable = new ConversationVariable();
        $conversationVariable->setConversation($conversation);
        $conversationVariable->setName('remove_test_var');
        $conversationVariable->setValue('remove test value');
        $conversationVariable->setType('string');
        $this->persistAndFlush($conversationVariable);

        $variableId = $conversationVariable->getId();

        // Act: 删除会话变量
        $this->repository->remove($conversationVariable);

        // Assert: 验证会话变量已删除
        $this->assertEntityNotExists(ConversationVariable::class, $variableId);
    }

    public function testRemoveWithoutFlushShouldNotDeleteImmediately(): void
    {
        // Arrange: 创建并持久化会话变量
        $conversation = $this->createTestConversation('remove-no-flush');

        $conversationVariable = new ConversationVariable();
        $conversationVariable->setConversation($conversation);
        $conversationVariable->setName('remove_no_flush_var');
        $conversationVariable->setValue('value');
        $conversationVariable->setType('string');
        $this->persistAndFlush($conversationVariable);

        $variableId = $conversationVariable->getId();

        // Act: 删除会话变量但不刷新
        $this->repository->remove($conversationVariable, false);

        // Assert: 验证会话变量仍然存在（在数据库中）
        $em = self::getEntityManager();
        $qb = $this->repository->createQueryBuilder('cv');
        $qb->select('COUNT(cv.id)')
            ->where('cv.id = :id')
            ->setParameter('id', $variableId)
        ;

        $count = (int) $qb->getQuery()->getSingleScalarResult();
        $this->assertSame(1, $count, '删除未flush时，实体应该仍在数据库中');

        // 手动刷新后应该被删除
        $em->flush();

        $count = (int) $qb->getQuery()->getSingleScalarResult();
        $this->assertSame(0, $count, 'flush后，实体应该被删除');
    }

    public function testGetEntityManagerShouldReturnEntityManagerInterface(): void
    {
        // Act: 获取实体管理器
        $em = self::getEntityManager();

        // Assert: 验证返回类型
        $this->assertInstanceOf(EntityManagerInterface::class, $em);
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        // Assert: 验证继承关系
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryHasCorrectEntityClass(): void
    {
        // Assert: 验证实体类
        $this->assertSame(ConversationVariable::class, $this->repository->getClassName());
    }

    public function testEntityHelperMethodsShouldWorkCorrectly(): void
    {
        // Arrange: 创建会话变量并测试辅助方法
        $conversation = $this->createTestConversation('helper-test');

        $stringVar = new ConversationVariable();
        $stringVar->setConversation($conversation);
        $stringVar->setName('string_test');
        $stringVar->setValue('Hello World');
        $stringVar->setType('string');

        $numberVar = new ConversationVariable();
        $numberVar->setConversation($conversation);
        $numberVar->setName('number_test');
        $numberVar->setValue('42.5');
        $numberVar->setType('number');

        $booleanVar = new ConversationVariable();
        $booleanVar->setConversation($conversation);
        $booleanVar->setName('boolean_test');
        $booleanVar->setValue('true');
        $booleanVar->setType('boolean');

        $arrayVar = new ConversationVariable();
        $arrayVar->setConversation($conversation);
        $arrayVar->setName('array_test');
        $arrayVar->setValue('["item1", "item2", "item3"]');
        $arrayVar->setType('array');

        // Act & Assert: 测试获取解析后的值
        $this->assertSame('Hello World', $stringVar->getParsedValue());
        $this->assertSame(42.5, $numberVar->getParsedValue());
        $this->assertTrue($booleanVar->getParsedValue());
        $this->assertSame(['item1', 'item2', 'item3'], $arrayVar->getParsedValue());
    }

    public function testSetValueWithTypeShouldAutomaticallySetCorrectType(): void
    {
        // Arrange: 创建会话变量
        $conversation = $this->createTestConversation('value-type-test');

        $variable = new ConversationVariable();
        $variable->setConversation($conversation);
        $variable->setName('auto_type_test');

        // Act & Assert: 测试不同类型值的自动类型设置
        $variable->setValueWithType('string value');
        $this->assertSame('string value', $variable->getValue());
        $this->assertSame('string', $variable->getType());

        $variable->setValueWithType(42);
        $this->assertSame('42', $variable->getValue());
        $this->assertSame('number', $variable->getType());

        $variable->setValueWithType(true);
        $this->assertSame('true', $variable->getValue());
        $this->assertSame('boolean', $variable->getType());

        $variable->setValueWithType(false);
        $this->assertSame('false', $variable->getValue());
        $this->assertSame('boolean', $variable->getType());

        $variable->setValueWithType(['item1', 'item2']);
        $this->assertSame('["item1","item2"]', $variable->getValue());
        $this->assertSame('array', $variable->getType());

        $variable->setValueWithType(null);
        $this->assertNull($variable->getValue());
        $this->assertSame('string', $variable->getType());
    }

    public function testEntityStringRepresentationShouldBeCorrect(): void
    {
        // Arrange: 创建会话变量
        $conversation = $this->createTestConversation('string-rep-test');

        $variable = new ConversationVariable();
        $variable->setConversation($conversation);
        $variable->setName('test_variable');
        $variable->setValue('test value');
        $variable->setType('string');

        // Act & Assert: 测试字符串表示
        $this->assertSame('test_variable: test value (string)', (string) $variable);

        // 测试null值的情况
        $variable->setValue(null);
        $this->assertSame('test_variable: null (string)', (string) $variable);
    }

    public function testFlush(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
