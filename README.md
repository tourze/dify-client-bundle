# Dify Client Bundle

[English](README.md) | [中文](README.zh-CN.md)

Symfony Bundle for integrating Dify AI with message aggregation and event-driven responses.

## Features

- **Complete API Coverage**: Full implementation of all 79 Dify API endpoints across 4 modules
  - **Chatflow API**: 21 endpoints for conversation management, messaging, and app configuration
  - **Completion API**: 13 endpoints for text generation and completion tasks
  - **Workflow API**: 5 endpoints for workflow execution and monitoring
  - **Dataset API**: 40 endpoints for knowledge base management, documents, and embeddings
- **RESTful API Controllers**: Ready-to-use HTTP controllers with proper routing and error handling
- **Message Aggregation**: Automatically aggregates messages within a time window (default 30s) to reduce API calls
- **Event-Driven Responses**: Uses Symfony EventDispatcher to handle AI responses asynchronously
- **Database Configuration Management**: Store and manage Dify configurations in database
- **EasyAdmin Integration**: Built-in admin interface for managing configurations and viewing messages
- **Console Commands**: CLI tools for configuration management and health checks
- **Queue Processing**: Built-in Symfony Messenger support for reliable async processing
- **Stream Support**: Real-time streaming responses for interactive applications
- **Comprehensive Test Coverage**: Full unit and integration test coverage for all components

## Installation

```bash
composer require tourze/dify-client-bundle
```

## Configuration

Add the bundle to your `config/bundles.php`:

```php
return [
    // ...
    Tourze\DifyClientBundle\DifyClientBundle::class => ['all' => true],
];
```

## Usage

### Basic Usage

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
        
        // Send message (will be aggregated if needed)
        $this->difyMessengerService->push($message);
        
        // Force immediate processing of pending messages
        $this->difyMessengerService->flushBatch();
        
        return new Response('Message sent successfully');
    }
}
```

### Event Listeners

Create event listeners to handle AI responses:

```php
use Tourze\DifyClientBundle\Event\DifyReplyEvent;
use Tourze\DifyClientBundle\Event\DifyErrorEvent;

class DifyResponseListener
{
    public function onDifyReply(DifyReplyEvent $event): void
    {
        $conversation = $event->getConversation();
        $reply = $event->getReply();
        
        // Handle the AI response
        // Send to WebSocket, store in database, etc.
    }

    public function onDifyError(DifyErrorEvent $event): void
    {
        $error = $event->getErrorMessage();
        
        // Handle errors
        // Log the error, notify admin, etc.
    }
}
```

Register the listener in `services.yaml`:

```yaml
services:
    App\EventListener\DifyResponseListener:
        tags:
            - { name: kernel.event_listener, event: Tourze\DifyClientBundle\Event\DifyReplyEvent, method: onDifyReply }
            - { name: kernel.event_listener, event: Tourze\DifyClientBundle\Event\DifyErrorEvent, method: onDifyError }
```

### RESTful API Usage

The bundle provides complete RESTful API endpoints that can be accessed via HTTP. All API endpoints follow the `/dify/api/v1` prefix:

#### Chatflow API Examples

```bash
# Send a chat message
curl -X POST /dify/api/v1/chat-messages \
  -H "Content-Type: application/json" \
  -d '{"query": "Hello, how are you?", "user": "user123"}'

# Get conversation list  
curl -X GET "/dify/api/v1/conversations?user=user123&limit=20"

# Get conversation messages
curl -X GET "/dify/api/v1/conversations/{conversation_id}/messages?user=user123"

# Upload a file
curl -X POST /dify/api/v1/files/upload \
  -F "file=@document.pdf" \
  -F "user=user123"
```

#### Completion API Examples

```bash
# Generate completion text
curl -X POST /dify/api/v1/completion/messages \
  -H "Content-Type: application/json" \
  -d '{"inputs": {"prompt": "Write a poem"}, "user": "user123"}'

# Create annotation
curl -X POST /dify/api/v1/completion/annotations \
  -H "Content-Type: application/json" \
  -d '{"question": "What is AI?", "answer": "AI stands for Artificial Intelligence"}'
```

#### Workflow API Examples

```bash
# Run workflow
curl -X POST /dify/api/v1/workflows/run \
  -H "Content-Type: application/json" \
  -d '{"inputs": {"input1": "value1"}, "user": "user123"}'

# Get workflow logs
curl -X GET "/dify/api/v1/workflows/tasks/{task_id}/logs?user=user123"
```

#### Dataset API Examples

```bash
# Get datasets
curl -X GET "/dify/api/v1/datasets?page=1&limit=20"

# Create dataset
curl -X POST /dify/api/v1/datasets \
  -H "Content-Type: application/json" \
  -d '{"name": "My Dataset", "description": "Test dataset"}'

# Create document from text
curl -X POST /dify/api/v1/datasets/{dataset_id}/document/create_by_text \
  -H "Content-Type: application/json" \
  -d '{"name": "Test Doc", "text": "This is test content"}'

# Search in dataset
curl -X POST /dify/api/v1/datasets/{dataset_id}/retrieve \
  -H "Content-Type: application/json" \
  -d '{"query": "search term", "user": "user123"}'
```

### Service Layer Usage

Use the service classes directly in your application:

```php
use Tourze\DifyClientBundle\Service\ChatflowService;
use Tourze\DifyClientBundle\Service\DatasetService;

class MyService
{
    public function __construct(
        private readonly ChatflowService $chatflowService,
        private readonly DatasetService $datasetService
    ) {
    }

    public function processUserMessage(string $message, string $userId): array
    {
        // Send message via Chatflow service
        return $this->chatflowService->sendChatMessage(
            query: $message,
            user: $userId,
            responseMode: 'blocking'
        );
    }

    public function searchKnowledge(string $query, string $datasetId): array
    {
        // Search in dataset
        return $this->datasetService->retrieveFromDataset(
            datasetId: $datasetId,
            query: $query,
            user: 'system'
        );
    }
}
```

### System Health Check

Check the system health status:

```bash
# Check system health
php bin/console dify:health
```

### Retry Failed Messages

Retry failed Dify messages:

```bash
# Retry a specific failed message by ID
php bin/console dify:retry-failed <message-id>

# Retry all unretried failed messages (with limit)
php bin/console dify:retry-failed --all --limit=10

# Retry an entire batch by RequestTask ID
php bin/console dify:retry-failed --request-task=<task-id>

# Dry run mode (preview without actual retry)
php bin/console dify:retry-failed <message-id> --dry-run
```

## Message Aggregation

The bundle implements intelligent message aggregation:

1. **Time-based aggregation**: Messages within 30 seconds are aggregated
2. **Count-based aggregation**: Messages are sent when batch threshold is reached
3. **Automatic processing**: Aggregated messages are sent to Dify as a single request

## Database Schema

The bundle creates the following tables:

- `dify_setting`: Configuration management
- `dify_conversation`: Conversation tracking
- `dify_message`: Individual messages
- `dify_failed_message`: Failed message tracking

## Testing

```bash
# Run tests
php vendor/bin/phpunit tests/

# Run static analysis
php vendor/bin/phpstan analyse src/
```

## License

This bundle is licensed under the MIT License. See the LICENSE file for details.