<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Tourze\DifyClientBundle\Entity\Conversation;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Enum\MessageRole;
use Tourze\DifyClientBundle\Enum\MessageStatus;
use Tourze\DifyClientBundle\Service\MessageFactory;

/** * @internal
 */
#[CoversClass(MessageFactory::class)]
final class MessageFactoryTest extends TestCase
{
    private MessageFactory $factory;

    private ClockInterface $clock;

    protected function setUp(): void
    {
        parent::setUp();

        parent::setUp();
        $this->clock = $this->createMock(ClockInterface::class);
        $this->clock
            ->method('now')
            ->willReturn(new \DateTimeImmutable('2023-01-01 12:00:00'))
        ;

        $this->factory = new MessageFactory($this->clock);
    }

    public function testCreateAssistantMessage(): void
    {
        $conversation = new Conversation();
        $content = 'Test message content';

        $message = $this->factory->createAssistantMessage($conversation, $content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame($conversation, $message->getConversation());
        $this->assertSame($content, $message->getContent());
        $this->assertSame(MessageRole::ASSISTANT, $message->getRole());
        $this->assertSame(MessageStatus::RECEIVED, $message->getStatus());
        $this->assertNotNull($message->getReceivedAt());
    }

    public function testCreateAssistantMessageWithResponse(): void
    {
        $conversation = new Conversation();
        $content = 'Test message content';
        $response = ['message_id' => 'msg-123', 'metadata' => ['key' => 'value']];

        $message = $this->factory->createAssistantMessage($conversation, $content, $response);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame($conversation, $message->getConversation());
        $this->assertSame($content, $message->getContent());
        $this->assertSame(MessageRole::ASSISTANT, $message->getRole());
        $this->assertSame(MessageStatus::RECEIVED, $message->getStatus());
        $this->assertNotNull($message->getReceivedAt());

        $metadata = $message->getMetadata();
        $this->assertNotNull($metadata);
        $this->assertArrayHasKey('dify_message_id', $metadata);
        $this->assertSame('msg-123', $metadata['dify_message_id']);
    }

    public function testCreateUserMessage(): void
    {
        $conversation = new Conversation();
        $content = 'User message content';
        $userId = 'user-123';

        $message = $this->factory->createUserMessage($conversation, $content, $userId);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame($conversation, $message->getConversation());
        $this->assertSame($content, $message->getContent());
        $this->assertSame(MessageRole::USER, $message->getRole());
        $this->assertSame(MessageStatus::PENDING, $message->getStatus());
        $this->assertNotNull($message->getCreateTime());

        $metadata = $message->getMetadata();
        $this->assertNotNull($metadata);
        $this->assertArrayHasKey('user_id', $metadata);
        $this->assertSame($userId, $metadata['user_id']);
    }

    public function testCreateAssistantMessageWithoutResponse(): void
    {
        $conversation = new Conversation();
        $content = 'Test content without response';

        $message = $this->factory->createAssistantMessage($conversation, $content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame($conversation, $message->getConversation());
        $this->assertSame($content, $message->getContent());
        $this->assertSame(MessageRole::ASSISTANT, $message->getRole());
        $this->assertSame(MessageStatus::RECEIVED, $message->getStatus());
        $this->assertNotNull($message->getReceivedAt());
    }

    public function testCreateAssistantMessageWithEmptyResponse(): void
    {
        $conversation = new Conversation();
        $content = 'Test content with empty response';
        $response = [];

        $message = $this->factory->createAssistantMessage($conversation, $content, $response);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame($conversation, $message->getConversation());
        $this->assertSame($content, $message->getContent());
        $this->assertSame(MessageRole::ASSISTANT, $message->getRole());
        $this->assertSame(MessageStatus::RECEIVED, $message->getStatus());
    }
}
