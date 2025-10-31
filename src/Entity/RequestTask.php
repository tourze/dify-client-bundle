<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DifyClientBundle\Enum\RequestTaskStatus;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;

/**
 * Dify AI 请求任务实体
 *
 * 表示一次发送给 Dify AI 的请求任务
 * 支持消息聚合、批次处理、重试机制等高级功能
 * 记录任务的完整生命周期和状态变化
 */
#[ORM\Entity]
#[ORM\Table(name: 'dify_request_task', options: ['comment' => 'Dify AI 请求任务表'])]
class RequestTask implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use CreatedByAware;

    #[IndexColumn]
    #[ORM\Column(name: 'taskId', type: Types::STRING, length: 255, unique: true, options: ['comment' => '任务唯一标识符，用于追踪和重试'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $taskId;

    #[IndexColumn]
    #[ORM\Column(name: 'status', type: Types::STRING, enumType: RequestTaskStatus::class, options: ['default' => 'pending', 'comment' => '任务状态：pending-待处理，processing-处理中，completed-已完成，failed-失败，timeout-超时，retrying-重试中'])]
    #[Assert\Choice(callback: [RequestTaskStatus::class, 'cases'])]
    private RequestTaskStatus $status = RequestTaskStatus::PENDING;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '聚合后的消息内容，将批次中的多条消息合并后的完整文本'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 65535)]
    private string $aggregatedContent;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '批次中包含的消息数量'])]
    #[Assert\PositiveOrZero]
    #[Assert\Range(min: 0, max: 1000)]
    private int $messageCount = 0;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '任务的元数据信息，如会话ID、窗口开始时间等'])]
    #[Assert\All(constraints: [new Assert\Type('mixed')])]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '任务开始处理的时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $processTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '任务完成的时间（成功或失败）'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $completeTime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Dify返回的响应内容'])]
    #[Assert\Length(max: 65535)]
    private ?string $response = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '任务失败时的错误信息'])]
    #[Assert\Length(max: 65535)]
    private ?string $errorMessage = null;

    /** @var Collection<int, Message> */
    #[ORM\OneToMany(mappedBy: 'requestTask', targetEntity: Message::class)]
    private Collection $messages;

    /** @var Collection<int, FailedMessage> */
    #[ORM\OneToMany(mappedBy: 'requestTask', targetEntity: FailedMessage::class)]
    private Collection $failedMessages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->failedMessages = new ArrayCollection();
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function setTaskId(string $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function getStatus(): RequestTaskStatus
    {
        return $this->status;
    }

    public function setStatus(RequestTaskStatus $status): void
    {
        $this->status = $status;
    }

    public function getAggregatedContent(): string
    {
        return $this->aggregatedContent;
    }

    public function setAggregatedContent(string $aggregatedContent): void
    {
        $this->aggregatedContent = $aggregatedContent;
    }

    public function getMessageCount(): int
    {
        return $this->messageCount;
    }

    public function setMessageCount(int $messageCount): void
    {
        $this->messageCount = $messageCount;
    }

    /** @return ?array<string, mixed> */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /** @param ?array<string, mixed> $metadata */
    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processTime;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): void
    {
        $this->processTime = $processedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completeTime;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): void
    {
        $this->completeTime = $completedAt;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(?string $response): void
    {
        $this->response = $response;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    /** @return Collection<int, Message> */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setRequestTask($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getRequestTask() === $this) {
                $message->setRequestTask(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, FailedMessage> */
    public function getFailedMessages(): Collection
    {
        return $this->failedMessages;
    }

    public function addFailedMessage(FailedMessage $failedMessage): self
    {
        if (!$this->failedMessages->contains($failedMessage)) {
            $this->failedMessages->add($failedMessage);
            $failedMessage->setRequestTask($this);
        }

        return $this;
    }

    public function removeFailedMessage(FailedMessage $failedMessage): self
    {
        if ($this->failedMessages->removeElement($failedMessage)) {
            if ($failedMessage->getRequestTask() === $this) {
                $failedMessage->setRequestTask(null);
            }
        }

        return $this;
    }

    public function markAsProcessed(): void
    {
        $this->setStatus(RequestTaskStatus::PROCESSING);
        $this->setProcessedAt(new \DateTimeImmutable());
    }

    public function markAsCompleted(string $response): void
    {
        $this->setStatus(RequestTaskStatus::COMPLETED);
        $this->setCompletedAt(new \DateTimeImmutable());
        $this->setResponse($response);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->setStatus(RequestTaskStatus::FAILED);
        $this->setCompletedAt(new \DateTimeImmutable());
        $this->setErrorMessage($errorMessage);
    }

    public function isRetriable(): bool
    {
        return in_array($this->status, [
            RequestTaskStatus::FAILED,
            RequestTaskStatus::TIMEOUT,
        ], true);
    }

    public function __toString(): string
    {
        return sprintf('任务 %s (%s条消息)', $this->taskId, $this->messageCount);
    }
}
