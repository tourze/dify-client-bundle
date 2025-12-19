<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\MessageHandler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Tourze\DifyClientBundle\Message\ProcessDifyMessage;
use Tourze\DifyClientBundle\Service\DifyMessengerService;

#[AsMessageHandler]
final class ProcessDifyMessageHandler
{
    public function __construct(
        private readonly DifyMessengerService $difyMessengerService,
    ) {
    }

    public function __invoke(ProcessDifyMessage $message): void
    {
        $this->difyMessengerService->processMessage(
            $message->getRequestTask(),
            $message->getContent(),
            $message->getOriginalMessages()
        );
    }
}
