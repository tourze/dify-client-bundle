<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Tourze\DifyClientBundle\Command\Support\FailedMessageDisplayer;
use Tourze\DifyClientBundle\Command\Support\RetryParameterExtractor;
use Tourze\DifyClientBundle\Command\Support\RetryParameterValidator;
use Tourze\DifyClientBundle\Entity\FailedMessage;
use Tourze\DifyClientBundle\Entity\RequestTask;
use Tourze\DifyClientBundle\Message\RetryFailedMessage;
use Tourze\DifyClientBundle\Repository\FailedMessageRepository;
use Tourze\DifyClientBundle\Service\DifyRetryService;

#[AsCommand(name: self::NAME, description: '重试失败的 Dify 消息', help: <<<'TXT'
    此命令允许您重试失败的 Dify 消息。支持单个消息重试和整个批次重试。
    TXT)]
class DifyRetryFailedCommand extends Command
{
    public const NAME = 'dify:retry-failed';

    public function __construct(
        private readonly FailedMessageRepository $failedMessageRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly DifyRetryService $retryService,
        private readonly RetryParameterExtractor $parameterExtractor = new RetryParameterExtractor(),
        private readonly RetryParameterValidator $parameterValidator = new RetryParameterValidator(),
        private readonly FailedMessageDisplayer $displayer = new FailedMessageDisplayer(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::OPTIONAL, '要重试的失败消息ID或RequestTask ID')
            ->addOption('all', 'a', InputOption::VALUE_NONE, '重试所有未重试的失败消息')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, '限制重试数量', 10)
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, '试运行模式，不实际重试')
            ->addOption('batch', 'b', InputOption::VALUE_NONE, '重试整个批次（需要指定RequestTask ID）')
            ->addOption('request-task', 'r', InputOption::VALUE_OPTIONAL, '指定RequestTask ID进行批次重试')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->isInputValid($input, $io)) {
            return Command::FAILURE;
        }

        try {
            return $this->processRetryRequest($input, $io);
        } catch (\Exception $e) {
            $io->error(sprintf('重试失败：%s', $e->getMessage()));

            return Command::FAILURE;
        }
    }

    private function isInputValid(InputInterface $input, SymfonyStyle $io): bool
    {
        $validationErrors = $this->getValidationErrors($input);

        foreach ($validationErrors as $error) {
            $io->error($error);
        }

        return [] === $validationErrors;
    }

    /**
     * @return string[]
     */
    private function getValidationErrors(InputInterface $input): array
    {
        return $this->parameterValidator->validate($input);
    }

    private function processRetryRequest(InputInterface $input, SymfonyStyle $io): int
    {
        $params = $this->parameterExtractor->extract($input);
        $retryMethod = $this->determineRetryMethod($params);

        if (null === $retryMethod) {
            $io->warning('请指定重试目标：消息ID、--all、--batch ID、或--request-task ID');

            return Command::FAILURE;
        }

        return $this->executeRetryMethod($retryMethod, $params, $io);
    }

    /**
     * @param array{
     *     id: string,
     *     all: bool,
     *     batch: bool,
     *     limit: int,
     *     dryRun: bool,
     *     requestTask: string
     * } $params
     */
    private function determineRetryMethod(array $params): ?string
    {
        if ('' !== $params['requestTask']) {
            return 'requestTask';
        }

        if ($params['batch'] && '' !== $params['id']) {
            return 'batch';
        }

        if ('' !== $params['id']) {
            return 'single';
        }

        if ($params['all']) {
            return 'multiple';
        }

        return null;
    }

    /**
     * @param array{
     *     id: string,
     *     all: bool,
     *     batch: bool,
     *     limit: int,
     *     dryRun: bool,
     *     requestTask: string
     * } $params
     */
    private function executeRetryMethod(string $method, array $params, SymfonyStyle $io): int
    {
        return match ($method) {
            'requestTask' => $this->retryByRequestTask($params['requestTask'], $io, $params['dryRun']),
            'batch' => $this->retryBatch($params['id'], $io, $params['dryRun']),
            'single' => $this->retrySingleMessage($params['id'], $io, $params['dryRun']),
            'multiple' => $this->retryMultipleMessages($params['limit'], $io, $params['dryRun']),
            default => Command::FAILURE,
        };
    }

    private function retrySingleMessage(string $id, SymfonyStyle $io, bool $dryRun): int
    {
        $failedMessage = $this->findFailedMessage($id, $io);
        if (null === $failedMessage) {
            return Command::FAILURE;
        }

        if ($this->isAlreadyRetried($failedMessage, $io)) {
            return Command::SUCCESS;
        }

        $this->showSingleMessageHeader($id, $failedMessage, $io);

        if ($this->shouldSkipForDryRun($dryRun, $io)) {
            return Command::SUCCESS;
        }

        if (!$this->confirmSingleRetry($io)) {
            return Command::SUCCESS;
        }

        $this->processSingleMessageRetry($failedMessage);
        $io->success('重试消息已发送到队列');

        return Command::SUCCESS;
    }

    private function retryMultipleMessages(int $limit, SymfonyStyle $io, bool $dryRun): int
    {
        $failedMessages = $this->failedMessageRepository->findUnretriedMessages($limit);

        if ([] === $failedMessages) {
            $io->success('没有找到需要重试的失败消息');

            return Command::SUCCESS;
        }

        $this->showMultipleMessagesHeader($limit, $failedMessages, $io);

        if ($this->shouldShowDryRunPreview($dryRun, $failedMessages, $io)) {
            return Command::SUCCESS;
        }

        if (!$this->confirmMultipleRetry($failedMessages, $io)) {
            return Command::SUCCESS;
        }

        $retryCount = $this->processMultipleMessagesRetry($failedMessages, $io);
        $this->failedMessageRepository->flush();
        $io->success(sprintf('成功发送 %d 条重试消息到队列', $retryCount));

        return Command::SUCCESS;
    }

    private function dispatchRetryMessage(FailedMessage $failedMessage): void
    {
        $retryMessage = new RetryFailedMessage(
            $failedMessage->getId() ?? '',
            $failedMessage->getTaskId(),
            null !== $failedMessage->getContext() ? $failedMessage->getContext() : []
        );

        $this->messageBus->dispatch($retryMessage);
    }

    private function retryByRequestTask(string $requestTaskId, SymfonyStyle $io, bool $dryRun): int
    {
        $io->title(sprintf('重试 RequestTask 批次: %s', $requestTaskId));

        try {
            return $this->processRequestTaskRetry($requestTaskId, $io, $dryRun);
        } catch (\Exception $e) {
            $io->error(sprintf('重试 RequestTask 失败：%s', $e->getMessage()));

            return Command::FAILURE;
        }
    }

    private function getRequestTask(string $requestTaskId, SymfonyStyle $io): ?RequestTask
    {
        $taskInfo = $this->retryService->getRequestTaskMessages($requestTaskId);
        $requestTask = $taskInfo['request_task'] ?? null;

        if (!$requestTask instanceof RequestTask) {
            $io->error('获取的不是有效的RequestTask实例');

            return null;
        }

        return $requestTask;
    }

    private function shouldSkipForDryRun(bool $dryRun, SymfonyStyle $io): bool
    {
        if ($dryRun) {
            $io->note('试运行模式 - 不会实际重试');

            return true;
        }

        return false;
    }

    private function confirmRetry(SymfonyStyle $io): bool
    {
        return $io->confirm('确认重试整个批次？', false);
    }

    private function executeRetry(string $requestTaskId, SymfonyStyle $io): int
    {
        $result = $this->retryService->retryByRequestTaskId($requestTaskId);

        if (isset($result['success']) && true === $result['success']) {
            $this->displayRetrySuccess($result, $io);
        } else {
            $io->error(is_string($result['message'] ?? null) ? $result['message'] : '未知错误');
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $result
     */
    private function displayRetrySuccess(array $result, SymfonyStyle $io): void
    {
        $retriedCount = is_numeric($result['retried_count'] ?? null) ? (int) $result['retried_count'] : 0;
        $totalCount = is_numeric($result['total_count'] ?? null) ? (int) $result['total_count'] : 0;

        $io->success(sprintf('成功发送 %d/%d 条重试消息到队列', $retriedCount, $totalCount));
    }

    private function retryBatch(string $id, SymfonyStyle $io, bool $dryRun): int
    {
        return $this->retryByRequestTask($id, $io, $dryRun);
    }

    private function displayRequestTaskInfo(RequestTask $requestTask, SymfonyStyle $io): void
    {
        $this->displayer->displayRequestTaskInfo($requestTask, $io);
    }

    private function findFailedMessage(string $id, SymfonyStyle $io): ?FailedMessage
    {
        $failedMessage = $this->failedMessageRepository->find($id);

        if (null === $failedMessage) {
            $io->error(sprintf('找不到ID为 %s 的失败消息', $id));

            return null;
        }

        // 类型已通过Repository查询确定，无需重复检查

        return $failedMessage;
    }

    private function isAlreadyRetried(FailedMessage $failedMessage, SymfonyStyle $io): bool
    {
        if ($failedMessage->isRetried()) {
            $io->warning(sprintf('消息 %s 已经重试过', $failedMessage->getId()));

            return true;
        }

        return false;
    }

    private function showSingleMessageHeader(string $id, FailedMessage $failedMessage, SymfonyStyle $io): void
    {
        $io->title(sprintf('重试失败消息 #%s', $id));
        $this->displayer->displayMessageInfo($failedMessage, $io);
    }

    private function confirmSingleRetry(SymfonyStyle $io): bool
    {
        if (!$io->confirm('确认重试此消息？', false)) {
            $io->note('操作已取消');

            return false;
        }

        return true;
    }

    private function processSingleMessageRetry(FailedMessage $failedMessage): void
    {
        $this->dispatchRetryMessage($failedMessage);
        $failedMessage->setRetried(true);
        $failedMessage->addRetryAttempt(new \DateTimeImmutable(), 'manual_retry_triggered');
        $this->failedMessageRepository->flush();
    }

    /**
     * @param FailedMessage[] $failedMessages
     */
    private function showMultipleMessagesHeader(int $limit, array $failedMessages, SymfonyStyle $io): void
    {
        $io->title(sprintf('重试失败消息 (最多 %d 条)', $limit));
        $io->text(sprintf('找到 %d 条未重试的失败消息', count($failedMessages)));
    }

    /**
     * @param FailedMessage[] $failedMessages
     */
    private function shouldShowDryRunPreview(bool $dryRun, array $failedMessages, SymfonyStyle $io): bool
    {
        if ($dryRun) {
            $io->note('试运行模式 - 不会实际重试');

            foreach ($failedMessages as $message) {
                $this->displayer->displayMessageInfo($message, $io);
                $io->newLine();
            }

            return true;
        }

        return false;
    }

    /**
     * @param FailedMessage[] $failedMessages
     */
    private function confirmMultipleRetry(array $failedMessages, SymfonyStyle $io): bool
    {
        if (!$io->confirm(sprintf('确认重试这 %d 条消息？', count($failedMessages)), false)) {
            $io->note('操作已取消');

            return false;
        }

        return true;
    }

    /**
     * @param FailedMessage[] $failedMessages
     */
    private function processMultipleMessagesRetry(array $failedMessages, SymfonyStyle $io): int
    {
        $retryCount = 0;
        foreach ($failedMessages as $message) {
            try {
                $this->dispatchRetryMessage($message);
                $message->setRetried(true);
                $message->addRetryAttempt(new \DateTimeImmutable(), 'batch_retry_triggered');
                ++$retryCount;
            } catch (\Exception $e) {
                $io->warning(sprintf('消息 #%d 重试失败：%s', $message->getId(), $e->getMessage()));
            }
        }

        return $retryCount;
    }

    private function processRequestTaskRetry(string $requestTaskId, SymfonyStyle $io, bool $dryRun): int
    {
        $requestTask = $this->getRequestTask($requestTaskId, $io);
        if (!$requestTask instanceof RequestTask) {
            return Command::FAILURE;
        }

        $this->displayRequestTaskInfo($requestTask, $io);

        if ($this->shouldSkipForDryRun($dryRun, $io)) {
            return Command::SUCCESS;
        }

        if (!$this->confirmRetry($io)) {
            return Command::SUCCESS;
        }

        return $this->executeRetry($requestTaskId, $io);
    }
}
