# 执行 workflow

> 执行 workflow，没有已发布的 workflow，不可执行。

## OpenAPI

````yaml zh-hans/openapi_workflow.json post /workflows/run
paths:
  path: /workflows/run
  method: post
  servers:
    - url: '{api_base_url}'
      description: API 的基础 URL。请将 {api_base_url} 替换为您的应用提供的实际 API 基础 URL。
      variables:
        api_base_url:
          type: string
          description: 实际的 API 基础 URL
          default: https://api.dify.ai/v1
  request:
    security:
      - title: ApiKeyAuth
        parameters:
          query: {}
          header:
            Authorization:
              type: http
              scheme: bearer
              description: >-
                API-Key 鉴权。所有 API 请求都应在 Authorization HTTP Header 中包含您的
                API-Key，格式为：Bearer {API_KEY}。强烈建议开发者把 API-Key 放在后端存储，而非客户端，以免泄露。
          cookie: {}
    parameters:
      path: {}
      query: {}
      header: {}
      cookie: {}
    body:
      application/json:
        schemaArray:
          - type: object
            properties:
              inputs:
                allOf:
                  - type: object
                    description: >-
                      允许传入 App 定义的各变量值。如果变量是文件列表类型，该变量对应的值应是
                      InputFileObjectWorkflowCn 对象的列表。
                    additionalProperties:
                      oneOf:
                        - type: string
                        - type: number
                        - type: boolean
                        - type: object
                        - type: array
                          items:
                            $ref: '#/components/schemas/InputFileObjectWorkflowCn'
                    example:
                      user_query: 请帮我翻译这句话。
                      target_language: 法语
              response_mode:
                allOf:
                  - type: string
                    enum:
                      - streaming
                      - blocking
                    description: >-
                      返回响应模式。streaming (推荐) 基于 SSE；blocking 等待执行完毕后返回
                      (Cloudflare 100秒超时限制)。
              user:
                allOf:
                  - type: string
                    description: 用户标识，应用内唯一。
            required: true
            refIdentifier: '#/components/schemas/WorkflowExecutionRequestCn'
            requiredProperties:
              - inputs
              - response_mode
              - user
        examples:
          basic_execution_cn:
            summary: 基础工作流执行示例
            value:
              inputs:
                query: 请总结这段文字：...
              response_mode: streaming
              user: workflow_user_001
          with_file_array_variable_cn:
            summary: 包含文件列表变量的输入示例
            value:
              inputs:
                my_documents:
                  - type: document
                    transfer_method: local_file
                    upload_file_id: 已上传的文件ID_abc
                  - type: image
                    transfer_method: remote_url
                    url: https://example.com/image.jpg
              response_mode: blocking
              user: workflow_user_002
  response:
    '200':
      application/json:
        schemaArray:
          - type: object
            properties:
              workflow_run_id:
                allOf:
                  - type: string
                    format: uuid
                    description: workflow 执行 ID。
              task_id:
                allOf:
                  - type: string
                    format: uuid
                    description: 任务 ID。
              data:
                allOf:
                  - $ref: '#/components/schemas/WorkflowFinishedDataCn'
            description: 阻塞模式下的 workflow 执行结果。
            refIdentifier: '#/components/schemas/WorkflowCompletionResponseCn'
        examples:
          example:
            value:
              workflow_run_id: 3c90c3cc-0d44-4b50-8888-8dd25736052a
              task_id: 3c90c3cc-0d44-4b50-8888-8dd25736052a
              data:
                id: 3c90c3cc-0d44-4b50-8888-8dd25736052a
                workflow_id: 3c90c3cc-0d44-4b50-8888-8dd25736052a
                status: running
                outputs: {}
                error: <string>
                elapsed_time: 123
                total_tokens: 123
                total_steps: 0
                created_at: 123
                finished_at: 123
        description: >-
          工作流执行成功。响应结构取决于 `response_mode`。

          - `blocking`: `application/json` 格式，包含 `WorkflowCompletionResponseCn`
          对象。

          - `streaming`: `text/event-stream` 格式，包含 `ChunkWorkflowEventCn` 事件流。
      text/event-stream:
        schemaArray:
          - type: string
            description: >-
              SSE 事件流。每个事件以 'data: ' 开头，以 '\n\n' 结尾。具体结构请参见
              `ChunkWorkflowEventCn`。
        examples:
          example:
            value: <string>
        description: >-
          工作流执行成功。响应结构取决于 `response_mode`。

          - `blocking`: `application/json` 格式，包含 `WorkflowCompletionResponseCn`
          对象。

          - `streaming`: `text/event-stream` 格式，包含 `ChunkWorkflowEventCn` 事件流。
    '400':
      application/json:
        schemaArray:
          - type: object
            properties:
              status:
                allOf:
                  - &ref_0
                    type: integer
                    nullable: true
                    description: HTTP 状态码。
              code:
                allOf:
                  - &ref_1
                    type: string
                    nullable: true
                    description: 错误码。
              message:
                allOf:
                  - &ref_2
                    type: string
                    description: 错误消息。
            description: 错误响应。
            refIdentifier: '#/components/schemas/ErrorResponseCn'
        examples:
          example:
            value:
              status: 123
              code: <string>
              message: <string>
        description: >-
          请求参数错误或工作流执行失败。可能错误码：invalid_param, app_unavailable,
          provider_not_initialize, provider_quota_exceeded,
          model_currently_not_support, workflow_request_error。
    '500':
      application/json:
        schemaArray:
          - type: object
            properties:
              status:
                allOf:
                  - *ref_0
              code:
                allOf:
                  - *ref_1
              message:
                allOf:
                  - *ref_2
            description: 错误响应。
            refIdentifier: '#/components/schemas/ErrorResponseCn'
        examples:
          example:
            value:
              status: 123
              code: <string>
              message: <string>
        description: 服务内部异常。
  deprecated: false
  type: path
components:
  schemas:
    InputFileObjectWorkflowCn:
      type: object
      required:
        - type
        - transfer_method
      properties:
        type:
          type: string
          enum:
            - document
            - image
            - audio
            - video
            - custom
          description: >-
            文件类型。document: TXT,MD,PDF等; image: JPG,PNG等; audio: MP3,WAV等; video:
            MP4,MOV等; custom: 其他。
        transfer_method:
          type: string
          enum:
            - remote_url
            - local_file
          description: 传递方式，remote_url 用于图片 URL / local_file 用于文件上传
        url:
          type: string
          format: url
          description: 图片地址（当传递方式为 remote_url 时）
        upload_file_id:
          type: string
          description: 上传文件 ID，必须通过事先上传文件接口获得（当传递方式为 local_file 时）
      anyOf:
        - properties:
            transfer_method:
              enum:
                - remote_url
            url:
              type: string
              format: url
          required:
            - url
          not:
            required:
              - upload_file_id
        - properties:
            transfer_method:
              enum:
                - local_file
            upload_file_id:
              type: string
          required:
            - upload_file_id
          not:
            required:
              - url
    WorkflowFinishedDataCn:
      type: object
      description: Workflow 执行结束事件的详细内容。
      required:
        - id
        - workflow_id
        - status
        - created_at
        - finished_at
      properties:
        id:
          type: string
          format: uuid
          description: workflow 执行 ID。
        workflow_id:
          type: string
          format: uuid
          description: 关联 Workflow ID。
        status:
          type: string
          enum:
            - running
            - succeeded
            - failed
            - stopped
          description: 执行状态。
        outputs:
          type: object
          additionalProperties: true
          nullable: true
          description: （可选）输出内容 (JSON)。
        error:
          type: string
          nullable: true
          description: （可选）错误原因。
        elapsed_time:
          type: number
          format: float
          nullable: true
          description: （可选）耗时(秒)。
        total_tokens:
          type: integer
          nullable: true
          description: （可选）总使用 tokens。
        total_steps:
          type: integer
          default: 0
          description: 总步数，默认 0。
        created_at:
          type: integer
          format: int64
          description: 开始时间。
        finished_at:
          type: integer
          format: int64
          description: 结束时间。

````