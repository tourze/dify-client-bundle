<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;

final class ChatController extends AbstractController
{
    public function __construct(
        private readonly DifySettingRepository $difySettingRepository,
    ) {
    }

    #[Route(path: '/admin/dify/chat', name: 'admin_dify_chat_view', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        // 从查询参数获取settingId
        $settingId = $request->query->get('settingId');

        if (null === $settingId || '' === $settingId) {
            throw $this->createNotFoundException('缺少必需的参数 settingId');
        }

        // 必须找到对应的配置
        $setting = $this->difySettingRepository->find($settingId);

        if (null === $setting) {
            throw $this->createNotFoundException(sprintf('Dify 配置 ID %s 未找到', $settingId));
        }

        return $this->render('@DifyClient/admin/chat.html.twig', [
            'setting' => $setting,
        ]);
    }
}
