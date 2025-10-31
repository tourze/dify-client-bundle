# 获取应用参数 (Workflow)

## OpenAPI

````yaml zh-hans/openapi_workflow.json get /parameters
paths:
  path: /parameters
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
              user_input_form:
                allOf:
                  - type: array
                    items:
                      $ref: '#/components/schemas/UserInputFormItemCn'
                    description: 用户输入表单配置。
              file_upload:
                allOf:
                  - type: object
                    properties:
                      image:
                        type: object
                        properties:
                          enabled:
                            type: boolean
                          number_limits:
                            type: integer
                          detail:
                            type: string
                          transfer_methods:
                            type: array
                            items:
                              type: string
                              enum:
                                - remote_url
                                - local_file
                        description: 图片设置。当前仅支持图片类型：png, jpg, jpeg, webp, gif。
                    description: 文件上传配置。
              system_parameters:
                allOf:
                  - type: object
                    properties:
                      file_size_limit:
                        type: integer
                        description: 文档上传大小限制 (MB)。
                      image_file_size_limit:
                        type: integer
                        description: 图片文件上传大小限制（MB）。
                      audio_file_size_limit:
                        type: integer
                        description: 音频文件上传大小限制 (MB)。
                      video_file_size_limit:
                        type: integer
                        description: 视频文件上传大小限制 (MB)。
                    description: 系统参数。
            description: Workflow 应用参数信息。
            refIdentifier: '#/components/schemas/WorkflowAppParametersResponseCn'
        examples:
          example:
            value:
              user_input_form:
                - text-input:
                    label: <string>
                    variable: <string>
                    required: true
                    default: <string>
              file_upload:
                image:
                  enabled: true
                  number_limits: 123
                  detail: <string>
                  transfer_methods:
                    - remote_url
              system_parameters:
                file_size_limit: 123
                image_file_size_limit: 123
                audio_file_size_limit: 123
                video_file_size_limit: 123
        description: 应用参数信息。
  deprecated: false
  type: path
components:
  schemas:
    UserInputFormItemCn:
      type: object
      description: 用户输入表单中的控件项。
      oneOf:
        - $ref: '#/components/schemas/TextInputControlWrapperCn'
        - $ref: '#/components/schemas/ParagraphControlWrapperCn'
        - $ref: '#/components/schemas/SelectControlWrapperCn'
    TextInputControlWrapperCn:
      type: object
      properties:
        text-input:
          $ref: '#/components/schemas/TextInputControlCn'
      required:
        - text-input
    ParagraphControlWrapperCn:
      type: object
      properties:
        paragraph:
          $ref: '#/components/schemas/ParagraphControlCn'
      required:
        - paragraph
    SelectControlWrapperCn:
      type: object
      properties:
        select:
          $ref: '#/components/schemas/SelectControlCn'
      required:
        - select
    TextInputControlCn:
      type: object
      description: 文本输入控件。
      required:
        - label
        - variable
        - required
      properties:
        label:
          type: string
          description: 控件展示标签名。
        variable:
          type: string
          description: 控件 ID。
        required:
          type: boolean
          description: 是否必填。
        default:
          type: string
          nullable: true
          description: 默认值。
    ParagraphControlCn:
      type: object
      description: 段落文本输入控件。
      required:
        - label
        - variable
        - required
      properties:
        label:
          type: string
          description: 控件展示标签名。
        variable:
          type: string
          description: 控件 ID。
        required:
          type: boolean
          description: 是否必填。
        default:
          type: string
          nullable: true
          description: 默认值。
    SelectControlCn:
      type: object
      description: 下拉控件。
      required:
        - label
        - variable
        - required
        - options
      properties:
        label:
          type: string
          description: 控件展示标签名。
        variable:
          type: string
          description: 控件 ID。
        required:
          type: boolean
          description: 是否必填。
        default:
          type: string
          nullable: true
          description: 默认值。
        options:
          type: array
          items:
            type: string
          description: 选项值。

````