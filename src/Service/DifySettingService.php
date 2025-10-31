<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\DifyClientBundle\Entity\DifySetting;
use Tourze\DifyClientBundle\Repository\DifySettingRepository;

#[Autoconfigure(public: true)]
readonly class DifySettingService
{
    public function __construct(
        private DifySettingRepository $difySettingRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function setActiveSetting(DifySetting $setting): void
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->difySettingRepository->createQueryBuilder('s')
                ->update()
                ->set('s.isActive', 'false')
                ->where('s.isActive = true')
                ->getQuery()
                ->execute()
            ;

            $setting->setActive(true);
            $this->entityManager->persist($setting);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }
    }
}
