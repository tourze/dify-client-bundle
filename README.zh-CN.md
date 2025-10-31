# Dify Client Bundle

[English](README.md) | [中文](README.zh-CN.md)

用于集成 Dify AI 的 Symfony Bundle，支持消息聚合和事件驱动响应。

## 功能特性

- **消息聚合**：自动在时间窗口内聚合消息（默认30秒）以减少 API 调用
- **事件驱动响应**：使用 Symfony EventDispatcher 异步处理 AI 响应
- **数据库配置管理**：在数据库中存储和管理 Dify 配置
- **EasyAdmin 集成**：内置管理界面，用于管理配置和查看消息
- **控制台命令**：用于配置管理和健康检查的 CLI 工具
- **队列处理**：内置 Symfony Messenger 支持，确保可靠的异步处理
- **流式响应支持**：为交互式应用提供实时流式响应

## 安装

```bash
composer require tourze/dify-client-bundle
```

## 配置

在 `config/bundles.php` 中添加 bundle：

```php
return [
    // ...
    Tourze\DifyClientBundle\DifyClientBundle::class => ['all' => true],
];
```

## 使用方法

### 基本用法

```php
use Tourze\DifyClientBundle\Service\DifyMessengerService;

class MyController extends AbstractController
{
    public function __construct(
        private readonly DifyMessengerService $difyMessengerService
    ) {
    }

    public function sendMessage(Request $request): Response
    {
        $message = $request->request->get('message');
        
        // 发送消息（如需要会被聚合）
        $this->difyMessengerService->push($message);
        
        // 强制立即处理待处理消息
        $this->difyMessengerService->flushBatch();
        
        return new Response('消息发送成功');
    }
}
```

### 事件监听器

创建事件监听器来处理 AI 响应：

```php
use Tourze\DifyClientBundle\Event\DifyReplyEvent;
use Tourze\DifyClientBundle\Event\DifyErrorEvent;

class DifyResponseListener
{
    public function onDifyReply(DifyReplyEvent $event): void
    {
        $conversation = $event->getConversation();
        $reply = $event->getReply();
        
        // 处理 AI 响应
        // 发送到 WebSocket、存储到数据库等
    }

    public function onDifyError(DifyErrorEvent $event): void
    {
        $error = $event->getErrorMessage();
        
        // 处理错误
        // 记录日志、通知管理员等
    }
}
```

在 `services.yaml` 中注册监听器：

```yaml
services:
    App\EventListener\DifyResponseListener:
        tags:
            - { name: kernel.event_listener, event: Tourze\DifyClientBundle\Event\DifyReplyEvent, method: onDifyReply }
            - { name: kernel.event_listener, event: Tourze\DifyClientBundle\Event\DifyErrorEvent, method: onDifyError }
```

### 系统健康检查

检查系统健康状态：

```bash
# 检查系统健康状态
php bin/console dify:health
```

### 重试失败消息

重试失败的 Dify 消息：

```bash
# 通过 ID 重试特定的失败消息
php bin/console dify:retry-failed <message-id>

# 重试所有未重试的失败消息（限制数量）
php bin/console dify:retry-failed --all --limit=10

# 通过 RequestTask ID 重试整个批次
php bin/console dify:retry-failed --request-task=<task-id>

# 试运行模式（预览而不实际重试）
php bin/console dify:retry-failed <message-id> --dry-run
```

## 消息聚合

Bundle 实现智能消息聚合：

1. **基于时间的聚合**：30 秒内的消息会被聚合
2. **基于数量的聚合**：达到批次阈值时发送消息
3. **自动处理**：聚合的消息作为单个请求发送到 Dify

## 数据库架构

Bundle 创建以下表：

- `dify_setting`：配置管理
- `dify_conversation`：会话跟踪
- `dify_message`：单个消息
- `dify_failed_message`：失败消息跟踪

## 测试

```bash
# 运行测试
php vendor/bin/phpunit tests/

# 运行静态分析
php vendor/bin/phpstan analyse src/
```

## 许可

此 Bundle 基于 MIT 许可证。详情请参阅 LICENSE 文件。
