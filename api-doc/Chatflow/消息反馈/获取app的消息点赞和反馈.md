# 获取APP的消息点赞和反馈

> 获取应用的终端用户反馈、点赞。

## OpenAPI

````yaml zh-hans/openapi_chatflow.json get /app/feedbacks
paths:
  path: /app/feedbacks
  method: get
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
      query:
        page:
          schema:
            - type: integer
              description: 页码，（选填）默认值：1。
              default: 1
        limit:
          schema:
            - type: integer
              description: 每页数量，（选填）默认值：20。
              default: 20
      header: {}
      cookie: {}
    body: {}
  response:
    '200':
      application/json:
        schemaArray:
          - type: object
            properties:
              data:
                allOf:
                  - type: array
                    items:
                      $ref: '#/components/schemas/FeedbackItemCn'
                    description: 返回该APP的点赞、反馈列表。
            refIdentifier: '#/components/schemas/AppFeedbacksResponseCn'
        examples:
          example:
            value:
              data:
                - id: 3c90c3cc-0d44-4b50-8888-8dd25736052a
                  app_id: 3c90c3cc-0d44-4b50-8888-8dd25736052a
                  conversation_id: 3c90c3cc-0d44-4b50-8888-8dd25736052a
                  message_id: 3c90c3cc-0d44-4b50-8888-8dd25736052a
                  rating: like
                  content: <string>
                  from_source: <string>
                  from_end_user_id: 3c90c3cc-0d44-4b50-8888-8dd25736052a
                  from_account_id: 3c90c3cc-0d44-4b50-8888-8dd25736052a
                  created_at: '2023-11-07T05:31:56Z'
                  updated_at: '2023-11-07T05:31:56Z'
        description: 成功获取应用的反馈列表。
  deprecated: false
  type: path
components:
  schemas:
    FeedbackItemCn:
      type: object
      properties:
        id:
          type: string
          format: uuid
        app_id:
          type: string
          format: uuid
        conversation_id:
          type: string
          format: uuid
        message_id:
          type: string
          format: uuid
        rating:
          type: string
          enum:
            - like
            - dislike
            - null
          nullable: true
        content:
          type: string
        from_source:
          type: string
        from_end_user_id:
          type: string
          format: uuid
        from_account_id:
          type: string
          format: uuid
          nullable: true
        created_at:
          type: string
          format: date-time
        updated_at:
          type: string
          format: date-time

````