<?php
use Modulix\Mcp\ArraySchemaRegistry;
use Modulix\Mcp\ElementTreeValidator;
use Modulix\Mcp\ElementorSchemaRegistry;
use Modulix\Mcp\HtmlSanitizer;
use Modulix\Mcp\WidgetSchemaMapper;
use PHPUnit\Framework\TestCase;

final class McpWidgetSchemaAndValidatorTest extends TestCase {
    public function test_validator_limits_have_the_reviewed_defaults_and_hard_caps(): void {
        $this->assertSame(40, ElementTreeValidator::DEFAULT_MAX_DEPTH);
        $this->assertSame(2000, ElementTreeValidator::DEFAULT_MAX_ELEMENTS);
        $this->assertGreaterThanOrEqual(
            ElementTreeValidator::DEFAULT_MAX_DEPTH,
            ElementTreeValidator::MAX_DEPTH_CAP
        );
        $this->assertGreaterThanOrEqual(
            ElementTreeValidator::DEFAULT_MAX_ELEMENTS,
            ElementTreeValidator::MAX_ELEMENTS_CAP
        );
    }

    public function test_widget_controls_are_mapped_to_the_public_shape(): void {
        $mapper = new WidgetSchemaMapper();
        $schema = $mapper->map('heading', 'widget', [
            'title' => ['type' => 'text', 'default' => 'Hello'],
            'size' => ['type' => 'select', 'options' => ['sm' => 'Small', 'lg' => 'Large'], 'default' => 'sm'],
            'section_style' => ['type' => 'section'],
        ]);

        $this->assertSame('heading', $schema['name']);
        $this->assertSame('widget', $schema['type']);
        $this->assertSame([
            ['name' => 'title', 'type' => 'text', 'options' => [], 'default' => 'Hello'],
            ['name' => 'size', 'type' => 'select', 'options' => ['sm' => 'Small', 'lg' => 'Large'], 'default' => 'sm'],
        ], $schema['controls']);
    }

    public function test_mapper_reads_widget_controls_from_a_fake_elementor_registry(): void {
        $widget = new class {
            public function get_controls(): array { return ['title' => ['type' => 'text', 'default' => '']]; }
        };
        $plugin = new class($widget) {
            public object $widgets_manager;
            public object $elements_manager;
            public function __construct(object $widget) {
                $this->widgets_manager = new class($widget) {
                    public function __construct(private object $widget) {}
                    public function get_widget_types(string $name): ?object { return $name === 'heading' ? $this->widget : null; }
                };
                $this->elements_manager = new class {
                    public function get_element_types(string $name): ?object { return null; }
                };
            }
        };

        $schema = (new ElementorSchemaRegistry(new WidgetSchemaMapper(), $plugin))->schema('heading');

        $this->assertSame('heading', $schema['name']);
        $this->assertSame('title', $schema['controls'][0]['name']);
    }

    public function test_validator_reports_each_required_error_with_a_json_path(): void {
        $registry = new ArraySchemaRegistry([
            'heading' => [
                'name' => 'heading',
                'type' => 'widget',
                'controls' => [
                    ['name' => 'title', 'type' => 'text', 'options' => [], 'default' => ''],
                    ['name' => 'size', 'type' => 'select', 'options' => ['sm' => 'Small', 'lg' => 'Large'], 'default' => 'sm'],
                    ['name' => 'count', 'type' => 'number', 'options' => [], 'default' => 0],
                ],
            ],
        ]);
        $validator = new ElementTreeValidator($registry, new PassthroughMcpHtmlSanitizer());
        $result = $validator->validate([
            ['id' => 'missing-widget', 'elType' => 'widget', 'widgetType' => 'missing', 'settings' => [], 'elements' => []],
            ['id' => 'unknown-setting', 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => ['unknown' => 'x'], 'elements' => []],
            ['id' => 'wrong-type', 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => ['count' => []], 'elements' => []],
            ['id' => 'invalid-option', 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => ['size' => 'xl'], 'elements' => []],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertSame([
            ['code' => 'unknown_widget', 'path' => 'elements[0].widgetType'],
            ['code' => 'unknown_setting', 'path' => 'elements[1].settings.unknown'],
            ['code' => 'wrong_type', 'path' => 'elements[2].settings.count'],
            ['code' => 'invalid_option', 'path' => 'elements[3].settings.size'],
        ], array_map(static fn(array $error): array => [
            'code' => $error['code'],
            'path' => $error['path'],
        ], $result['errors']));
    }

    public function test_html_capable_settings_are_sanitized_through_the_injected_policy(): void {
        $registry = new ArraySchemaRegistry([
            'text-editor' => [
                'name' => 'text-editor',
                'type' => 'widget',
                'controls' => [
                    ['name' => 'editor', 'type' => 'wysiwyg', 'options' => [], 'default' => ''],
                    ['name' => 'custom_css', 'type' => 'code', 'options' => [], 'default' => ''],
                    ['name' => 'html', 'type' => 'code', 'options' => [], 'default' => ''],
                ],
            ],
        ]);
        $sanitizer = new class implements HtmlSanitizer {
            public function sanitize(string $html): string { return str_replace('<script>bad()</script>', '', $html); }
        };
        $result = (new ElementTreeValidator($registry, $sanitizer))->validate([
            ['id' => 'text-editor', 'elType' => 'widget', 'widgetType' => 'text-editor', 'settings' => [
                'editor' => '<p>Safe</p><script>bad()</script>',
                'custom_css' => 'selector::before { content: "<script>bad()</script>"; }',
                'html' => '<div>Safe</div><script>bad()</script>',
            ], 'elements' => []],
        ]);

        $this->assertTrue($result['valid']);
        $this->assertSame('<p>Safe</p>', $result['elements'][0]['settings']['editor']);
        $this->assertSame(
            'selector::before { content: "<script>bad()</script>"; }',
            $result['elements'][0]['settings']['custom_css']
        );
        $this->assertSame('<div>Safe</div>', $result['elements'][0]['settings']['html']);
    }

    public function test_deep_and_wide_trees_fail_with_bounded_errors(): void {
        $registry = new ArraySchemaRegistry([
            'container' => ['name' => 'container', 'type' => 'container', 'controls' => []],
        ]);
        $validator = new ElementTreeValidator($registry, new PassthroughMcpHtmlSanitizer());
        $node = ['id' => 'leaf', 'elType' => 'container', 'settings' => [], 'elements' => []];
        for ($depth = 0; $depth < 5000; $depth++) {
            $node = ['id' => 'n' . $depth, 'elType' => 'container', 'settings' => [], 'elements' => [$node]];
        }

        $deep = $validator->validate([$node]);
        $wide = $validator->validate(array_fill(
            0,
            50000,
            ['id' => 'wide', 'elType' => 'container', 'settings' => [], 'elements' => []]
        ));

        $this->assertSame('tree_too_deep', $deep['errors'][0]['code']);
        $this->assertSame('too_many_elements', $wide['errors'][0]['code']);
    }

    public function test_every_element_requires_a_non_empty_globally_unique_string_id(): void {
        $validator = new ElementTreeValidator(new ArraySchemaRegistry([
            'container' => ['name' => 'container', 'type' => 'container', 'controls' => []],
        ]), new PassthroughMcpHtmlSanitizer());

        $result = $validator->validate([
            ['elType' => 'container', 'settings' => [], 'elements' => []],
            ['id' => '', 'elType' => 'container', 'settings' => [], 'elements' => []],
            ['id' => 'duplicate', 'elType' => 'container', 'settings' => [], 'elements' => [[
                'id' => 'duplicate',
                'elType' => 'container',
                'settings' => [],
                'elements' => [],
            ]],
            ],
        ]);

        $this->assertSame([
            ['code' => 'invalid_element_id', 'path' => 'elements[0].id'],
            ['code' => 'invalid_element_id', 'path' => 'elements[1].id'],
            ['code' => 'duplicate_element_id', 'path' => 'elements[2].elements[0].id'],
        ], array_map(static fn(array $error): array => [
            'code' => $error['code'],
            'path' => $error['path'],
        ], $result['errors']));
    }

    public function test_hostile_shapes_return_exact_path_errors_without_throwing(): void {
        $registry = new ArraySchemaRegistry([
            'container' => [
                'name' => 'container',
                'type' => 'container',
                'controls' => [
                    ['name' => 'payload', 'type' => 'array', 'options' => [], 'default' => []],
                ],
            ],
            'heading' => [
                'name' => 'heading',
                'type' => 'widget',
                'controls' => [
                    ['name' => 'title', 'type' => 'text', 'options' => [], 'default' => ''],
                    ['name' => 'items', 'type' => 'repeater', 'options' => [], 'default' => [], 'fields' => [
                        ['name' => 'title', 'type' => 'text', 'options' => [], 'default' => ''],
                    ]],
                ],
            ],
        ]);

        $result = (new ElementTreeValidator($registry, new PassthroughMcpHtmlSanitizer()))->validate([
            ['elType' => new stdClass(), 'settings' => [], 'elements' => []],
            ['elType' => 'widget', 'widgetType' => new stdClass(), 'settings' => [], 'elements' => []],
            [
                'elType' => 'widget',
                'widgetType' => 'heading',
                'unexpected' => 'value',
                'settings' => ['items' => [(object) ['title' => 'x']]],
                'elements' => [],
            ],
            [
                'elType' => 'container',
                'settings' => [7 => null, 'payload' => ['nested' => new stdClass()]],
                'styles' => ['bad' => new stdClass()],
                'elements' => new stdClass(),
            ],
            null,
            [7 => 'non-string key', 'elType' => 'container', 'settings' => [], 'elements' => []],
        ]);

        $paths = array_column($result['errors'], 'path');
        foreach ([
            'elements[0].elType',
            'elements[1].widgetType',
            'elements[2].unexpected',
            'elements[2].settings.items[0]',
            'elements[3].settings[7]',
            'elements[3].settings.payload.nested',
            'elements[3].styles.bad',
            'elements[3].elements',
            'elements[4]',
            'elements[5][7]',
        ] as $path) {
            $this->assertContains($path, $paths);
        }
        $this->assertFalse($result['valid']);
    }

    public function test_only_elementor_saved_element_keys_are_allowed(): void {
        $registry = new ArraySchemaRegistry([
            'container' => ['name' => 'container', 'type' => 'container', 'controls' => []],
        ]);
        $validator = new ElementTreeValidator($registry, new PassthroughMcpHtmlSanitizer());
        $saved = $validator->validate([[
            'id' => 'abc123',
            'elType' => 'container',
            'settings' => [],
            'elements' => [],
            'isInner' => false,
            'isLocked' => true,
            'version' => '0.0',
            'styles' => [],
            'editor_settings' => [],
            'interactions' => [],
        ]]);
        $unknown = $validator->validate([[
            'id' => 'abc123',
            'elType' => 'container',
            'settings' => [],
            'elements' => [],
            'requestPayload' => ['token' => 'secret'],
        ]]);

        $this->assertTrue($saved['valid']);
        $this->assertSame('unknown_element_key', $unknown['errors'][0]['code']);
        $this->assertSame('elements[0].requestPayload', $unknown['errors'][0]['path']);
    }

    public function test_every_template_fixture_validates_against_controls_built_from_its_tree(): void {
        foreach (glob(__DIR__ . '/fixtures/templates/*.json') ?: [] as $file) {
            $template = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            $schemas = [];
            $this->collectSchemas($template['content'], $schemas);
            $result = (new ElementTreeValidator(new ArraySchemaRegistry($schemas), new PassthroughMcpHtmlSanitizer()))
                ->validate($template['content']);
            $this->assertTrue($result['valid'], basename($file) . ': ' . json_encode($result['errors']));
        }
    }

    private function collectSchemas(array $elements, array &$schemas): void {
        foreach ($elements as $element) {
            $name = $element['widgetType'] ?? $element['elType'];
            $schemas[$name] ??= ['name' => $name, 'type' => $element['elType'], 'controls' => []];
            $known = array_column($schemas[$name]['controls'], null, 'name');
            foreach (($element['settings'] ?? []) as $setting => $value) {
                if (!isset($known[$setting])) {
                    $schemas[$name]['controls'][] = $this->fixtureControl($setting, $value);
                }
            }
            $this->collectSchemas($element['elements'] ?? [], $schemas);
        }
    }

    private function fixtureControl(string $name, mixed $value): array {
        if (is_array($value)) {
            return ['name' => $name, 'type' => 'array', 'options' => [], 'default' => []];
        }
        if (is_int($value) || is_float($value)) {
            return ['name' => $name, 'type' => 'number', 'options' => [], 'default' => 0];
        }
        return ['name' => $name, 'type' => 'text', 'options' => [], 'default' => ''];
    }
}

final class PassthroughMcpHtmlSanitizer implements HtmlSanitizer {
    public function sanitize(string $html): string { return $html; }
}
