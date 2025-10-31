<?php

declare(strict_types=1);

namespace Tourze\DifyClientBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\DifyClientBundle\Entity\DifySetting;

/** @extends AbstractCrudController<DifySetting> */
#[AdminCrud(
    routePath: '/dify-client/dify-setting',
    routeName: 'dify_client_dify_setting'
)]
final class DifySettingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DifySetting::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Dify 配置')
            ->setEntityLabelInPlural('Dify 配置管理')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['name', 'baseUrl'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')->hideOnForm(),
            TextField::new('name', '配置名称')
                ->setHelp('配置的唯一标识符，用于区分不同的环境或应用'),
            TextField::new('apiKey', 'API Key')
                ->setHelp('Dify 应用的 API Key')
                ->hideOnIndex()
                ->setFormTypeOption('attr', ['autocomplete' => 'off']),
            TextField::new('baseUrl', 'Base URL')
                ->setHelp('Dify API 的基础地址，例如：https://api.dify.ai/v1'),
            IntegerField::new('batchThreshold', '批量阈值')
                ->setHelp('消息聚合的阈值，达到此数量时自动发送')
                ->setColumns(6),
            IntegerField::new('timeout', '超时时间（秒）')
                ->setHelp('API 请求的超时时间')
                ->setColumns(6),
            IntegerField::new('retryAttempts', '重试次数')
                ->setHelp('消息发送失败时的最大重试次数')
                ->setColumns(6),
            BooleanField::new('isActive', '是否激活')
                ->setHelp('同一时间只能有一个配置处于激活状态')
                ->setColumns(6),
            TextareaField::new('iframeEmbedCode', 'iframe 嵌入代码')
                ->setHelp('Dify 聊天窗口的 iframe 嵌入代码')
                ->hideOnIndex()
                ->setNumOfRows(5),
            DateTimeField::new('createTime', '创建时间')
                ->hideOnForm(),
            DateTimeField::new('updateTime', '更新时间')
                ->onlyOnDetail(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', '配置名称'))
            ->add(TextFilter::new('baseUrl', 'Base URL'))
            ->add(BooleanFilter::new('isActive', '是否激活'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
