# 获取应用Meta信息

> 用于获取工具 icon。

## OpenAPI

````yaml zh-hans/openapi_chatflow.json get /meta
paths:
  path: /meta
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
      query: {}
      header: {}
      cookie: {}
    body: {}
  response:
    '200':
      application/json:
        schemaArray:
          - type: object
            properties:
              tool_icons:
                allOf:
                  - type: object
                    additionalProperties:
                      oneOf:
                        - type: string
                          format: url
                          description: 图标 URL。
                        - $ref: '#/components/schemas/ToolIconDetailCn'
                    description: 工具图标，键为工具名称。
            description: 应用 Meta 信息。
            refIdentifier: '#/components/schemas/AppMetaResponseCn'
        examples:
          example:
            value:
              tool_icons: {}
        description: 成功获取应用 Meta 信息。
  deprecated: false
  type: path
components:
  schemas:
    ToolIconDetailCn:
      type: object
      description: 工具图标详情。
      properties:
        background:
          type: string
          description: hex 格式的背景色。
        content:
          type: string
          description: emoji。

````