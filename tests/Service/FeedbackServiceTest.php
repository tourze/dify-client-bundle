<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DifyClientBundle\Service\FeedbackService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * FeedbackService 测试类
 *
 * 测试反馈服务的核心功能
 * @internal
 */
#[CoversClass(FeedbackService::class)]
#[RunTestsInSeparateProcesses]
final class FeedbackServiceTest extends AbstractIntegrationTestCase
{
    private FeedbackService $feedbackService;

    protected function onSetUp(): void
    {
        $this->feedbackService = self::getService(FeedbackService::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(FeedbackService::class, $this->feedbackService);
    }

    public function testSubmitFeedbackMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->feedbackService);
        $this->assertTrue($reflection->hasMethod('submitFeedback'));
        $method = $reflection->getMethod('submitFeedback');
        $this->assertTrue($method->isPublic());
    }

    public function testLikeMessageMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->feedbackService);
        $this->assertTrue($reflection->hasMethod('likeMessage'));
        $method = $reflection->getMethod('likeMessage');
        $this->assertTrue($method->isPublic());
    }

    public function testDislikeMessageMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->feedbackService);
        $this->assertTrue($reflection->hasMethod('dislikeMessage'));
        $method = $reflection->getMethod('dislikeMessage');
        $this->assertTrue($method->isPublic());
    }

    public function testUpdateFeedbackMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->feedbackService);
        $this->assertTrue($reflection->hasMethod('updateFeedback'));
        $method = $reflection->getMethod('updateFeedback');
        $this->assertTrue($method->isPublic());
    }

    public function testDeleteFeedbackMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->feedbackService);
        $this->assertTrue($reflection->hasMethod('deleteFeedback'));
        $method = $reflection->getMethod('deleteFeedback');
        $this->assertTrue($method->isPublic());
    }

    public function testFindUserFeedbackMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->feedbackService);
        $this->assertTrue($reflection->hasMethod('findUserFeedback'));
        $method = $reflection->getMethod('findUserFeedback');
        $this->assertTrue($method->isPublic());
    }

    public function testFetchAppFeedbacksMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->feedbackService);
        $this->assertTrue($reflection->hasMethod('fetchAppFeedbacks'));
        $method = $reflection->getMethod('fetchAppFeedbacks');
        $this->assertTrue($method->isPublic());
    }

    public function testCleanupOldFeedbacksMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->feedbackService);
        $this->assertTrue($reflection->hasMethod('cleanupOldFeedbacks'));
        $method = $reflection->getMethod('cleanupOldFeedbacks');
        $this->assertTrue($method->isPublic());
    }

    public function testGetMessageFeedbacksMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->feedbackService);
        $this->assertTrue($reflection->hasMethod('getMessageFeedbacks'));
        $method = $reflection->getMethod('getMessageFeedbacks');
        $this->assertTrue($method->isPublic());
    }

    public function testGetUserFeedbacksMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->feedbackService);
        $this->assertTrue($reflection->hasMethod('getUserFeedbacks'));
        $method = $reflection->getMethod('getUserFeedbacks');
        $this->assertTrue($method->isPublic());
    }

    public function testGetAllFeedbacksMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->feedbackService);
        $this->assertTrue($reflection->hasMethod('getAllFeedbacks'));
        $method = $reflection->getMethod('getAllFeedbacks');
        $this->assertTrue($method->isPublic());
    }

    public function testGetFeedbacksMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->feedbackService);
        $this->assertTrue($reflection->hasMethod('getFeedbacks'));
        $method = $reflection->getMethod('getFeedbacks');
        $this->assertTrue($method->isPublic());
    }

    public function testGetFeedbackStatsMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->feedbackService);
        $this->assertTrue($reflection->hasMethod('getFeedbackStats'));
        $method = $reflection->getMethod('getFeedbackStats');
        $this->assertTrue($method->isPublic());
    }

    public function testGetNegativeFeedbackAnalysisMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->feedbackService);
        $this->assertTrue($reflection->hasMethod('getNegativeFeedbackAnalysis'));
        $method = $reflection->getMethod('getNegativeFeedbackAnalysis');
        $this->assertTrue($method->isPublic());
    }

    public function testCreateFeedbackMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->feedbackService);
        $this->assertTrue($reflection->hasMethod('createFeedback'));
        $method = $reflection->getMethod('createFeedback');
        $this->assertTrue($method->isPublic());
    }

    public function testServiceIsFinal(): void
    {
        $reflection = new \ReflectionClass(FeedbackService::class);
        $this->assertTrue($reflection->isFinal());
    }

    public function testServiceIsReadonly(): void
    {
        $reflection = new \ReflectionClass(FeedbackService::class);
        $this->assertTrue($reflection->isReadOnly());
    }
}
