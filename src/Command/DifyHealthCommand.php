<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;

#[AsCommand(
    name: self::NAME,
    description: '检查 Dify 系统健康状态'
)]
class DifyHealthCommand extends Command
{
    public const NAME = 'dify:health';

    public function __construct(
        private readonly DifySettingRepository $settingRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Dify 系统健康检查');

        $allHealthy = true;

        $activeSetting = $this->settingRepository->findActiveSetting();

        if (null === $activeSetting) {
            $io->error('❌ 没有找到激活的 Dify 配置');
            $allHealthy = false;
        } else {
            $io->success(sprintf('✅ 找到激活的配置：%s', $activeSetting->getName()));

            $apiHealth = $this->checkApiHealth($activeSetting, $io);
            if (!$apiHealth) {
                $allHealthy = false;
            }
        }

        $dbHealth = $this->checkDatabaseHealth($io);
        if (!$dbHealth) {
            $allHealthy = false;
        }

        $queueHealth = $this->checkQueueHealth($io);
        if (!$queueHealth) {
            $allHealthy = false;
        }

        if ($allHealthy) {
            $io->success('🎉 所有检查通过，系统运行正常！');

            return Command::SUCCESS;
        }
        $io->error('⚠️  发现问题，请检查上述错误');

        return Command::FAILURE;
    }

    private function checkApiHealth(DifySetting $setting, SymfonyStyle $io): bool
    {
        $io->section('API 连接检查');

        try {
            $url = rtrim($setting->getBaseUrl(), '/') . '/parameters';

            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $setting->getApiKey(),
                ],
                'timeout' => 10,
            ]);

            if (200 === $response->getStatusCode()) {
                $io->success('✅ Dify API 连接正常');

                return true;
            }
            $io->error(sprintf('❌ Dify API 返回错误状态码：%d', $response->getStatusCode()));

            return false;
        } catch (\Exception $e) {
            $io->error(sprintf('❌ Dify API 连接失败：%s', $e->getMessage()));

            return false;
        }
    }

    private function checkDatabaseHealth(SymfonyStyle $io): bool
    {
        $io->section('数据库连接检查');

        try {
            $settings = $this->settingRepository->findAll();
            $io->success(sprintf('✅ 数据库连接正常，找到 %d 个配置', count($settings)));

            return true;
        } catch (\Exception $e) {
            $io->error(sprintf('❌ 数据库连接失败：%s', $e->getMessage()));

            return false;
        }
    }

    private function checkQueueHealth(SymfonyStyle $io): bool
    {
        $io->section('消息队列检查');

        try {
            $pendingMessagesResult = $this->entityManager->getConnection()
                ->executeQuery('SELECT COUNT(*) as count FROM dify_message WHERE status = "pending"')
                ->fetchOne()
            ;
            $pendingMessages = is_numeric($pendingMessagesResult) ? (int) $pendingMessagesResult : 0;

            $failedMessagesResult = $this->entityManager->getConnection()
                ->executeQuery('SELECT COUNT(*) as count FROM dify_failed_message WHERE retried = false')
                ->fetchOne()
            ;
            $failedMessages = is_numeric($failedMessagesResult) ? (int) $failedMessagesResult : 0;

            $io->text(sprintf('待处理消息：%d', $pendingMessages));
            $io->text(sprintf('失败消息：%d', $failedMessages));

            if ($pendingMessages > 100) {
                $io->warning('⚠️  待处理消息较多，可能存在队列积压');

                return false;
            }

            if ($failedMessages > 10) {
                $io->warning('⚠️  失败消息较多，请检查错误日志');

                return false;
            }

            $io->success('✅ 消息队列状态正常');

            return true;
        } catch (\Exception $e) {
            $io->error(sprintf('❌ 无法检查队列状态：%s', $e->getMessage()));

            return false;
        }
    }
}
