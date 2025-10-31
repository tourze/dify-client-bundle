<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Psr\Clock\ClockInterface;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Enum\MessageRole;
use Tourze\DifyClientBundle\Enum\MessageStatus;

/**
 * 消息工厂 - 专门处理消息实体的创建
 */
readonly class MessageFactory
{
    public function __construct(private ClockInterface $clock)
    {
    }

    /**
     * 创建用户消息
     */
    public function createUserMessage(Conversation $conversation, string $content, string $userId): Message
    {
        $message = new Message();
        $message->setConversation($conversation);
        $message->setRole(MessageRole::USER);
        $message->setContent($content);
        $message->setStatus(MessageStatus::PENDING);
        $message->setCreateTime($this->clock->now());
        $message->setMetadata(['user_id' => $userId]);

        return $message;
    }

    /**
     * 创建助手消息
     */
    /** @param ?array<string, mixed> $response */
    public function createAssistantMessage(Conversation $conversation, string $content, ?array $response = null): Message
    {
        $message = new Message();
        $message->setConversation($conversation);
        $message->setRole(MessageRole::ASSISTANT);
        $message->setContent($content);
        $message->setStatus(MessageStatus::RECEIVED);
        $message->setReceivedAt($this->clock->now());

        // 保存 Dify message ID 到 metadata
        if (null !== $response && isset($response['message_id'])) {
            $metadata = $message->getMetadata() ?? [];
            $metadata['dify_message_id'] = $response['message_id'];
            $message->setMetadata($metadata);
        }

        return $message;
    }
}
