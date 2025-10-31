<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Entity\Annotation;
use Tourze\DifyClientBundle\Repository\AnnotationRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AnnotationRepository::class)]
#[RunTestsInSeparateProcesses]
final class AnnotationRepositoryTest extends AbstractRepositoryTestCase
{
    private AnnotationRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(AnnotationRepository::class);
    }

    protected function getRepository(): AnnotationRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): Annotation
    {
        $annotation = new Annotation();
        $annotation->setAnnotationId('test-annotation-' . uniqid());
        $annotation->setQuestion('Test question?');
        $annotation->setAnswer('Test answer.');
        $annotation->setEnabled(true);
        $annotation->setHitCount(0);

        return $annotation;
    }

    public function testFindByAnnotationIdShouldReturnCorrectAnnotation(): void
    {
        // Arrange: 创建并持久化标注
        $annotationId = 'test-find-by-id-' . uniqid();
        $annotation = new Annotation();
        $annotation->setAnnotationId($annotationId);
        $annotation->setQuestion('How to use Dify?');
        $annotation->setAnswer('Dify is an AI platform.');
        $annotation->setEnabled(true);
        $this->persistAndFlush($annotation);

        // Act: 根据标注ID查找
        $foundAnnotation = $this->repository->findByAnnotationId($annotationId);

        // Assert: 验证找到正确的标注
        $this->assertNotNull($foundAnnotation);
        $this->assertSame($annotationId, $foundAnnotation->getAnnotationId());
        $this->assertSame('How to use Dify?', $foundAnnotation->getQuestion());
        $this->assertSame('Dify is an AI platform.', $foundAnnotation->getAnswer());
    }

    public function testFindByAnnotationIdWithNonExistentIdShouldReturnNull(): void
    {
        // Act: 查找不存在的标注ID
        $foundAnnotation = $this->repository->findByAnnotationId('non-existent-id');

        // Assert: 应该返回null
        $this->assertNull($foundAnnotation);
    }

    public function testFindEnabledAnnotationsShouldReturnOnlyEnabledAnnotations(): void
    {
        // Arrange: 清理现有数据并创建不同状态的标注
        $em = self::getEntityManager();
        $em->getConnection()->executeStatement('DELETE FROM dify_annotation');
        $em->clear();

        $enabledAnnotation1 = new Annotation();
        $enabledAnnotation1->setAnnotationId('enabled-1');
        $enabledAnnotation1->setQuestion('Enabled question 1?');
        $enabledAnnotation1->setAnswer('Enabled answer 1.');
        $enabledAnnotation1->setEnabled(true);
        $enabledAnnotation1->setHitCount(10);

        $enabledAnnotation2 = new Annotation();
        $enabledAnnotation2->setAnnotationId('enabled-2');
        $enabledAnnotation2->setQuestion('Enabled question 2?');
        $enabledAnnotation2->setAnswer('Enabled answer 2.');
        $enabledAnnotation2->setEnabled(true);
        $enabledAnnotation2->setHitCount(20);

        $disabledAnnotation = new Annotation();
        $disabledAnnotation->setAnnotationId('disabled-1');
        $disabledAnnotation->setQuestion('Disabled question?');
        $disabledAnnotation->setAnswer('Disabled answer.');
        $disabledAnnotation->setEnabled(false);
        $disabledAnnotation->setHitCount(5);

        $this->persistAndFlush($enabledAnnotation1);
        $this->persistAndFlush($enabledAnnotation2);
        $this->persistAndFlush($disabledAnnotation);

        // Act: 查找启用的标注
        $enabledAnnotations = $this->repository->findEnabledAnnotations();

        // Assert: 只返回启用的标注，并按点击次数降序排列
        $this->assertCount(2, $enabledAnnotations);

        $annotationIds = array_map(fn ($a) => $a->getAnnotationId(), $enabledAnnotations);
        $this->assertContains('enabled-1', $annotationIds);
        $this->assertContains('enabled-2', $annotationIds);
        $this->assertNotContains('disabled-1', $annotationIds);

        // 验证按点击次数降序排列
        $this->assertSame('enabled-2', $enabledAnnotations[0]->getAnnotationId()); // 点击次数更高的在前
        $this->assertSame('enabled-1', $enabledAnnotations[1]->getAnnotationId());
    }

    public function testFindEnabledAnnotationsWithNoEnabledAnnotationsShouldReturnEmptyArray(): void
    {
        // Arrange: 清理数据并创建禁用的标注
        $em = self::getEntityManager();
        $em->getConnection()->executeStatement('DELETE FROM dify_annotation');
        $em->clear();

        $disabledAnnotation = new Annotation();
        $disabledAnnotation->setAnnotationId('disabled-only');
        $disabledAnnotation->setQuestion('Disabled question?');
        $disabledAnnotation->setAnswer('Disabled answer.');
        $disabledAnnotation->setEnabled(false);
        $this->persistAndFlush($disabledAnnotation);

        // Act: 查找启用的标注
        $enabledAnnotations = $this->repository->findEnabledAnnotations();

        // Assert: 返回空数组
        $this->assertEmpty($enabledAnnotations);
    }

    public function testSearchWithKeywordShouldReturnMatchingAnnotations(): void
    {
        // Arrange: 清理数据并创建测试标注
        $em = self::getEntityManager();
        $em->getConnection()->executeStatement('DELETE FROM dify_annotation');
        $em->clear();

        $annotation1 = new Annotation();
        $annotation1->setAnnotationId('search-1');
        $annotation1->setQuestion('How to configure AI model?');
        $annotation1->setAnswer('You can configure AI models in settings.');
        $annotation1->setEnabled(true);

        $annotation2 = new Annotation();
        $annotation2->setAnnotationId('search-2');
        $annotation2->setQuestion('What is machine learning?');
        $annotation2->setAnswer('Machine learning is a subset of AI.');
        $annotation2->setEnabled(true);

        $annotation3 = new Annotation();
        $annotation3->setAnnotationId('search-3');
        $annotation3->setQuestion('How to deploy application?');
        $annotation3->setAnswer('Use Docker for deployment.');
        $annotation3->setEnabled(true);

        $this->persistAndFlush($annotation1);
        $this->persistAndFlush($annotation2);
        $this->persistAndFlush($annotation3);

        // Act: 搜索包含"AI"关键词的标注
        $searchResults = $this->repository->search('AI');

        // Assert: 返回匹配的标注
        $this->assertCount(2, $searchResults);
        $annotationIds = array_map(fn ($a) => $a->getAnnotationId(), $searchResults);
        $this->assertContains('search-1', $annotationIds);
        $this->assertContains('search-2', $annotationIds);
        $this->assertNotContains('search-3', $annotationIds);
    }

    public function testSearchWithoutKeywordShouldReturnAllAnnotations(): void
    {
        // Arrange: 清理数据并创建测试标注
        $em = self::getEntityManager();
        $em->getConnection()->executeStatement('DELETE FROM dify_annotation');
        $em->clear();

        $annotation1 = new Annotation();
        $annotation1->setAnnotationId('all-1');
        $annotation1->setQuestion('Question 1?');
        $annotation1->setAnswer('Answer 1.');
        $annotation1->setEnabled(true);

        $annotation2 = new Annotation();
        $annotation2->setAnnotationId('all-2');
        $annotation2->setQuestion('Question 2?');
        $annotation2->setAnswer('Answer 2.');
        $annotation2->setEnabled(true);

        $this->persistAndFlush($annotation1);
        $this->persistAndFlush($annotation2);

        // Act: 无关键词搜索
        $searchResults = $this->repository->search(null);

        // Assert: 返回所有标注
        $this->assertCount(2, $searchResults);
    }

    public function testSearchWithPaginationShouldRespectLimitAndOffset(): void
    {
        // Arrange: 清理数据并创建多个标注
        $em = self::getEntityManager();
        $em->getConnection()->executeStatement('DELETE FROM dify_annotation');
        $em->clear();

        for ($i = 1; $i <= 5; ++$i) {
            $annotation = new Annotation();
            $annotation->setAnnotationId("pagination-{$i}");
            $annotation->setQuestion("Question {$i}?");
            $annotation->setAnswer("Answer {$i}.");
            $annotation->setEnabled(true);
            $this->persistAndFlush($annotation);
        }

        // Act: 分页搜索（第二页，每页2条）
        $searchResults = $this->repository->search(null, 2, 2);

        // Assert: 返回正确的分页结果
        $this->assertCount(2, $searchResults);
    }

    public function testRecordHitShouldIncrementHitCountAndUpdateLastHitAt(): void
    {
        // Arrange: 创建标注
        $annotation = new Annotation();
        $annotation->setAnnotationId('hit-test');
        $annotation->setQuestion('Hit test question?');
        $annotation->setAnswer('Hit test answer.');
        $annotation->setEnabled(true);
        $annotation->setHitCount(5);
        $this->persistAndFlush($annotation);

        $originalHitCount = $annotation->getHitCount();
        $originalLastHitAt = $annotation->getLastHitAt();

        // 等待一小段时间确保时间戳不同
        usleep(1000);

        // Act: 记录点击
        $this->repository->recordHit($annotation);

        // Assert: 验证点击次数增加且最后点击时间更新
        $this->assertSame($originalHitCount + 1, $annotation->getHitCount());
        $this->assertNotNull($annotation->getLastHitAt());
        $this->assertNotEquals($originalLastHitAt, $annotation->getLastHitAt());
        $this->assertGreaterThan($originalLastHitAt ?? new \DateTimeImmutable('1970-01-01'), $annotation->getLastHitAt());
    }

    public function testSaveShouldPersistNewEntity(): void
    {
        // Arrange: 创建新标注（未持久化）
        $annotation = new Annotation();
        $annotation->setAnnotationId('test-save');
        $annotation->setQuestion('Save test question?');
        $annotation->setAnswer('Save test answer.');
        $annotation->setEnabled(true);

        // Act: 保存标注
        $this->repository->save($annotation);

        // Assert: 验证标注已持久化
        $this->assertNotNull($annotation->getId());
        $this->assertEntityPersisted($annotation);
    }

    public function testSaveShouldUpdateExistingEntity(): void
    {
        // Arrange: 创建并持久化标注
        $annotation = new Annotation();
        $annotation->setAnnotationId('test-update-save');
        $annotation->setQuestion('Original question?');
        $annotation->setAnswer('Original answer.');
        $annotation->setEnabled(true);
        $this->persistAndFlush($annotation);

        // Act: 修改并保存
        $annotation->setQuestion('Updated question?');
        $this->repository->save($annotation);

        // Assert: 验证更新已持久化
        $em = self::getEntityManager();
        $em->refresh($annotation);
        $this->assertSame('Updated question?', $annotation->getQuestion());
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // Arrange: 创建并持久化标注
        $annotation = new Annotation();
        $annotation->setAnnotationId('test-remove');
        $annotation->setQuestion('Remove test question?');
        $annotation->setAnswer('Remove test answer.');
        $annotation->setEnabled(true);
        $this->persistAndFlush($annotation);

        $annotationId = $annotation->getId();

        // Act: 删除标注
        $this->repository->remove($annotation);

        // Assert: 验证标注已删除
        $this->assertEntityNotExists(Annotation::class, $annotationId);
    }

    public function testRemoveWithoutFlushShouldNotDeleteImmediately(): void
    {
        // Arrange: 创建并持久化标注
        $annotation = new Annotation();
        $annotation->setAnnotationId('test-remove-no-flush');
        $annotation->setQuestion('Remove no flush test?');
        $annotation->setAnswer('Remove no flush answer.');
        $annotation->setEnabled(true);
        $this->persistAndFlush($annotation);

        $annotationId = $annotation->getId();

        // Act: 删除标注但不刷新
        $this->repository->remove($annotation, false);

        // Assert: 验证标注仍然存在（在数据库中）
        $em = self::getEntityManager();
        $qb = $this->repository->createQueryBuilder('a');
        $qb->select('COUNT(a.id)')
            ->where('a.id = :id')
            ->setParameter('id', $annotationId)
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
        $this->assertSame(Annotation::class, $this->repository->getClassName());
    }

    public function testFlush(): void
    {
        // TODO: 实现测试逻辑
        self::markTestIncomplete('此测试尚未实现');
    }
}
