<?php

namespace Tourze\DifyClientBundle\Tests\MessageHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyClientBundle\Entity\Message;
use Tourze\DifyClientBundle\Entity\RequestTask;
use Tourze\DifyClientBundle\Message\ProcessDifyMessage;
use Tourze\DifyClientBundle\MessageHandler\ProcessDifyMessageHandler;
use Tourze\DifyClientBundle\Service\DifyMessengerService;

/**
 * @internal
 */
#[CoversClass(ProcessDifyMessageHandler::class)]
final class ProcessDifyMessageHandlerTest extends TestCase
{
    public function testHandlerShouldProcessMessageThroughService(): void
    {
        $difyMessengerService = $this->createMock(DifyMessengerService::class);
        $handler = new ProcessDifyMessageHandler($difyMessengerService);

        $requestTask = new RequestTask();
        $requestTask->setTaskId('task-123');
        $requestTask->setAggregatedContent('User messages');

        $originalMessages = [
            new Message(),
            new Message(),
        ];

        $message = new ProcessDifyMessage($requestTask, 'AI response', $originalMessages);

        $difyMessengerService->expects($this->once())
            ->method('processMessage')
            ->with(
                self::identicalTo($requestTask),
                $this->equalTo('AI response'),
                self::identicalTo($originalMessages)
            )
        ;

        $handler->__invoke($message);
    }

    public function testHandlerShouldProcessMessageWithoutOriginalMessages(): void
    {
        $difyMessengerService = $this->createMock(DifyMessengerService::class);
        $handler = new ProcessDifyMessageHandler($difyMessengerService);

        $requestTask = new RequestTask();
        $requestTask->setTaskId('task-456');

        $message = new ProcessDifyMessage($requestTask, 'Simple response');

        $difyMessengerService->expects($this->once())
            ->method('processMessage')
            ->with(
                self::identicalTo($requestTask),
                $this->equalTo('Simple response'),
                $this->equalTo([])
            )
        ;

        $handler->__invoke($message);
    }

    public function testHandlerShouldProcessMessageWithEmptyContent(): void
    {
        $difyMessengerService = $this->createMock(DifyMessengerService::class);
        $handler = new ProcessDifyMessageHandler($difyMessengerService);

        $requestTask = new RequestTask();
        $requestTask->setTaskId('task-789');

        $message = new ProcessDifyMessage($requestTask, '');

        $difyMessengerService->expects($this->once())
            ->method('processMessage')
            ->with(
                self::identicalTo($requestTask),
                $this->equalTo(''),
                $this->equalTo([])
            )
        ;

        $handler->__invoke($message);
    }
}
