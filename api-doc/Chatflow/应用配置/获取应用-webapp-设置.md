# 获取应用 WebApp 设置

> 用于获取应用的 WebApp 设置。

## OpenAPI

````yaml zh-hans/openapi_chatflow.json get /site
paths:
  path: /site
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
              title:
                allOf:
                  - type: string
                    description: WebApp 名称。
              chat_color_theme:
                allOf:
                  - type: string
                    description: 聊天颜色主题, hex 格式。
              chat_color_theme_inverted:
                allOf:
                  - type: boolean
                    description: 聊天颜色主题是否反转。
              icon_type:
                allOf:
                  - type: string
                    enum:
                      - emoji
                      - image
                    description: 图标类型。
              icon:
                allOf:
                  - type: string
                    description: 图标内容 (emoji 或图片 URL)。
              icon_background:
                allOf:
                  - type: string
                    description: hex 格式的背景色。
              icon_url:
                allOf:
                  - type: string
                    format: url
                    nullable: true
                    description: 图标 URL。
              description:
                allOf:
                  - type: string
                    description: 描述。
              copyright:
                allOf:
                  - type: string
                    description: 版权信息。
              privacy_policy:
                allOf:
                  - type: string
                    description: 隐私政策链接。
              custom_disclaimer:
                allOf:
                  - type: string
                    description: 自定义免责声明。
              default_language:
                allOf:
                  - type: string
                    description: 默认语言。
              show_workflow_steps:
                allOf:
                  - type: boolean
                    description: 是否显示工作流详情。
              use_icon_as_answer_icon:
                allOf:
                  - type: boolean
                    description: 是否使用 WebApp 图标替换聊天中的机器人图标。
            description: 应用 WebApp 设置。
            refIdentifier: '#/components/schemas/WebAppSettingsResponseCn'
        examples:
          example:
            value:
              title: <string>
              chat_color_theme: <string>
              chat_color_theme_inverted: true
              icon_type: emoji
              icon: <string>
              icon_background: <string>
              icon_url: <string>
              description: <string>
              copyright: <string>
              privacy_policy: <string>
              custom_disclaimer: <string>
              default_language: <string>
              show_workflow_steps: true
              use_icon_as_answer_icon: true
        description: WebApp 设置信息。
  deprecated: false
  type: path
components:
  schemas: {}

````