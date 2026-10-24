<?php
namespace Modulix\Mcp;

final class AbilityCatalog {
    public function __construct(
        private ReadSource $source,
        private AccessGate $access,
        private RequestGate $limits,
        private SchemaRegistry $schemas
    ) {}

    public function definitions(): array {
        return [
            'modulix/site-overview' => $this->definition(
                'Site overview',
                'Returns Elementor and Modulix versions, global kit colors and fonts, and active theme templates.',
                ['type' => 'object', 'additionalProperties' => false],
                $this->siteOverviewSchema(),
                fn($input = []): bool => $this->access->canReadSite(),
                fn($input = []): mixed => $this->executeRead((array) $input, fn(): array => $this->source->siteOverview())
            ),
            'modulix/list-documents' => $this->definition(
                'List Elementor documents',
                'Lists pages, posts, and templates built with Elementor that the current user may read and edit.',
                [
                    'type' => 'object',
                    'properties' => [
                        'page' => ['type' => 'integer', 'minimum' => 1, 'default' => 1],
                        'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50],
                    ],
                    'additionalProperties' => false,
                ],
                ['type' => 'array', 'items' => $this->documentSummarySchema()],
                fn($input = []): bool => $this->access->canReadSite(),
                fn($input = []): mixed => $this->executeRead((array) $input, function () use ($input): array {
                    $input = (array) $input;
                    $page = max(1, (int) ($input['page'] ?? 1));
                    $perPage = min(100, max(1, (int) ($input['per_page'] ?? 50)));
                    return $this->source->listDocuments($page, $perPage);
                })
            ),
            'modulix/get-document' => $this->definition(
                'Get Elementor document',
                'Returns the Elementor element tree and page settings for one document.',
                [
                    'type' => 'object',
                    'properties' => ['post_id' => ['type' => 'integer', 'minimum' => 1]],
                    'required' => ['post_id'],
                    'additionalProperties' => false,
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'title' => ['type' => 'string'],
                        'type' => ['type' => 'string'],
                        'status' => ['type' => 'string'],
                        'modified' => ['type' => 'string'],
                        'elements' => ['type' => 'array', 'items' => ['type' => 'object']],
                        'page_settings' => ['type' => 'object', 'additionalProperties' => true],
                    ],
                    'required' => ['id', 'elements', 'page_settings'],
                ],
                fn($input = []): bool => $this->access->canReadSite()
                    && $this->access->canReadDocument((int) (((array) $input)['post_id'] ?? 0)),
                fn($input = []): mixed => $this->executeRead((array) $input, function () use ($input): mixed {
                    $input = (array) $input;
                    $postId = (int) ($input['post_id'] ?? 0);
                    if (!$this->access->canReadSite() || !$this->access->canReadDocument($postId)) {
                        return $this->error('forbidden', 'The current user cannot read and edit this document.');
                    }
                    return $this->source->getDocument($postId)
                        ?? $this->error('not_elementor_document', 'The document does not exist or was not built with Elementor.');
                })
            ),
            'modulix/widget-schema' => $this->definition(
                'Get Elementor widget schema',
                'Returns the controls for one Elementor widget or element type.',
                [
                    'type' => 'object',
                    'properties' => ['element_type' => ['type' => 'string', 'minLength' => 1]],
                    'required' => ['element_type'],
                    'additionalProperties' => false,
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'type' => ['type' => 'string'],
                        'controls' => ['type' => 'array', 'items' => ['type' => 'object']],
                    ],
                    'required' => ['name', 'type', 'controls'],
                ],
                fn($input = []): bool => $this->access->canReadSite(),
                fn($input = []): mixed => $this->executeRead((array) $input, function () use ($input): mixed {
                    $input = (array) $input;
                    $name = (string) ($input['element_type'] ?? '');
                    return $this->schemas->schema($name)
                        ?? $this->error('unknown_element_type', 'The Elementor widget or element type is not registered.');
                })
            ),
        ];
    }

    private function definition(
        string $label,
        string $description,
        array $inputSchema,
        array $outputSchema,
        callable $permission,
        callable $execute
    ): array {
        return [
            'label' => $label,
            'description' => $description,
            'category' => 'modulix',
            'input_schema' => $inputSchema,
            'output_schema' => $outputSchema,
            'permission_callback' => $permission,
            'execute_callback' => $execute,
            'meta' => [
                'show_in_rest' => false,
                'mcp' => ['public' => true],
                'annotations' => ['readonly' => true, 'idempotent' => true, 'destructive' => false],
            ],
        ];
    }

    private function executeRead(array $input, callable $callback): mixed {
        if (!$this->access->canReadSite()) {
            return $this->error('forbidden', 'The current user cannot read Elementor documents.');
        }
        $limitError = $this->limits->checkRead($this->access->userId(), $input);
        if ($limitError !== null) {
            return $this->error($limitError, 'The MCP read limit was exceeded.');
        }
        return $callback();
    }

    private function error(string $code, string $message): mixed {
        if (class_exists('WP_Error')) {
            return new \WP_Error($code, $message);
        }
        return ['error' => ['code' => $code, 'message' => $message]];
    }

    private function documentSummarySchema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'type' => ['type' => 'string'],
                'status' => ['type' => 'string'],
                'modified' => ['type' => 'string'],
            ],
            'required' => ['id', 'title', 'type', 'status', 'modified'],
        ];
    }

    private function siteOverviewSchema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'elementor_version' => ['type' => 'string'],
                'modulix_version' => ['type' => 'string'],
                'kit' => ['type' => 'object', 'additionalProperties' => true],
                'theme_templates' => ['type' => 'array', 'items' => ['type' => 'object']],
            ],
            'required' => ['elementor_version', 'modulix_version', 'kit', 'theme_templates'],
        ];
    }
}
