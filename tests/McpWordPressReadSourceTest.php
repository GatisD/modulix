<?php
namespace Modulix\Mcp {
    final class McpWordPressReadSourceState {
        public static array $posts = [];
        public static array $meta = [];
        public static array $filters = [];

        public static function reset(): void {
            self::$posts = [];
            self::$meta = [];
            self::$filters = [];
        }
    }

    function get_posts(array $args): array {
        return [];
    }

    function get_post(int $id): ?object {
        return McpWordPressReadSourceState::$posts[$id] ?? null;
    }

    function get_post_meta(int $id, string $key, bool $single): mixed {
        return McpWordPressReadSourceState::$meta[$id][$key] ?? '';
    }

    function get_the_title(object $post): string {
        return $post->post_title ?? '';
    }

    function apply_filters(string $name, mixed $value): mixed {
        return isset(McpWordPressReadSourceState::$filters[$name])
            ? (McpWordPressReadSourceState::$filters[$name])($value)
            : $value;
    }
}

namespace Elementor {
    if (!class_exists(Plugin::class, false)) {
        final class Plugin {
            public static ?object $instance = null;
        }
    }
}

namespace {
    use Modulix\Mcp\AccessGate;
    use Modulix\Mcp\ArraySchemaRegistry;
    use Modulix\Mcp\ElementTreeValidator;
    use Modulix\Mcp\HtmlSanitizer;
    use Modulix\Mcp\McpWordPressReadSourceState;
    use Modulix\Mcp\WordPressReadSource;
    use PHPUnit\Framework\TestCase;

    final class McpWordPressReadSourceTest extends TestCase {
        protected function setUp(): void {
            McpWordPressReadSourceState::reset();
            \Elementor\Plugin::$instance = null;
        }

        protected function tearDown(): void {
            McpWordPressReadSourceState::reset();
            \Elementor\Plugin::$instance = null;
        }

        public function test_site_overview_omits_kit_when_kit_document_access_is_denied(): void {
            \Elementor\Plugin::$instance = (object) [
                'kits_manager' => new class {
                    public function get_active_kit(): object {
                        return new class {
                            public function get_id(): int { return 77; }
                            public function get_settings_for_display(string $name): array { return [['id' => $name]]; }
                            public function get_settings(string $name): array { return [['id' => $name]]; }
                        };
                    }
                },
            ];

            $overview = (new WordPressReadSource(new McpReadSourceAccessGate([])))->siteOverview();

            $this->assertEquals((object) [], $overview['kit']);
        }

        public function test_site_overview_includes_kit_after_kit_document_access_is_allowed(): void {
            \Elementor\Plugin::$instance = (object) [
                'kits_manager' => new class {
                    public function get_active_kit(): object {
                        return new class {
                            public function get_id(): int { return 77; }
                            public function get_settings_for_display(string $name): array { return [['id' => $name]]; }
                            public function get_settings(string $name): array { return [['id' => $name]]; }
                        };
                    }
                },
            ];

            $overview = (new WordPressReadSource(new McpReadSourceAccessGate([77])))->siteOverview();

            $this->assertSame(77, $overview['kit']['id']);
            $this->assertSame('system_colors', $overview['kit']['colors']['system'][0]['id']);
        }

        public function test_get_document_rejects_post_types_outside_the_explicit_allowlist(): void {
            foreach (['page', 'attachment', 'revision', 'product'] as $index => $postType) {
                $id = $index + 10;
                McpWordPressReadSourceState::$posts[$id] = (object) [
                    'ID' => $id,
                    'post_title' => ucfirst($postType),
                    'post_type' => $postType,
                    'post_status' => 'draft',
                    'post_modified_gmt' => '2026-10-24 12:00:00',
                ];
                McpWordPressReadSourceState::$meta[$id] = [
                    '_elementor_edit_mode' => 'builder',
                    '_elementor_data' => '[]',
                    '_elementor_page_settings' => [],
                ];
            }
            $source = new WordPressReadSource(new McpReadSourceAccessGate([10, 11, 12, 13]));

            $this->assertSame('page', $source->getDocument(10)['type']);
            $this->assertNull($source->getDocument(11));
            $this->assertNull($source->getDocument(12));
            $this->assertNull($source->getDocument(13));
        }

        public function test_document_post_type_allowlist_is_filterable(): void {
            McpWordPressReadSourceState::$posts[20] = (object) [
                'ID' => 20,
                'post_title' => 'Product',
                'post_type' => 'product',
                'post_status' => 'draft',
                'post_modified_gmt' => '2026-10-24 12:00:00',
            ];
            McpWordPressReadSourceState::$meta[20] = [
                '_elementor_edit_mode' => 'builder',
                '_elementor_data' => '[]',
                '_elementor_page_settings' => [],
            ];
            McpWordPressReadSourceState::$filters['modulix_mcp_document_post_types'] = static function (array $types): array {
                $types[] = 'product';
                return $types;
            };

            $document = (new WordPressReadSource(new McpReadSourceAccessGate([20])))->getDocument(20);

            $this->assertSame('product', $document['type']);
        }

        public function test_validator_filters_can_lower_limits_but_cannot_exceed_hard_caps(): void {
            $registry = new ArraySchemaRegistry([
                'container' => ['name' => 'container', 'type' => 'container', 'controls' => []],
            ]);
            $node = ['id' => 'leaf', 'elType' => 'container', 'settings' => [], 'elements' => []];
            for ($depth = 0; $depth < ElementTreeValidator::MAX_DEPTH_CAP; $depth++) {
                $node = ['id' => 'n' . $depth, 'elType' => 'container', 'settings' => [], 'elements' => [$node]];
            }
            McpWordPressReadSourceState::$filters['modulix_mcp_validator_max_depth'] = static fn(): int => PHP_INT_MAX;
            McpWordPressReadSourceState::$filters['modulix_mcp_validator_max_elements'] = static fn(): int => PHP_INT_MAX;
            $capped = new ElementTreeValidator($registry, new McpReadSourceHtmlSanitizer());

            $this->assertSame('tree_too_deep', $capped->validate([$node])['errors'][0]['code']);
            $this->assertSame('too_many_elements', $capped->validate(array_fill(
                0,
                ElementTreeValidator::MAX_ELEMENTS_CAP + 1,
                ['id' => 'wide', 'elType' => 'container', 'settings' => [], 'elements' => []]
            ))['errors'][0]['code']);

            McpWordPressReadSourceState::$filters['modulix_mcp_validator_max_depth'] = static fn(): int => 2;
            $lowered = new ElementTreeValidator($registry, new McpReadSourceHtmlSanitizer());
            $threeLevels = [[
                'id' => 'one',
                'elType' => 'container',
                'settings' => [],
                'elements' => [[
                    'id' => 'two',
                    'elType' => 'container',
                    'settings' => [],
                    'elements' => [[
                        'id' => 'three',
                        'elType' => 'container',
                        'settings' => [],
                        'elements' => [],
                    ]],
                ]],
            ]];
            $this->assertSame('tree_too_deep', $lowered->validate($threeLevels)['errors'][0]['code']);
        }
    }

    final class McpReadSourceAccessGate implements AccessGate {
        public function __construct(private array $documents) {}
        public function canReadSite(): bool { return true; }
        public function canReadDocument(int $postId): bool { return in_array($postId, $this->documents, true); }
        public function userId(): int { return 7; }
    }

    final class McpReadSourceHtmlSanitizer implements HtmlSanitizer {
        public function sanitize(string $html): string { return $html; }
    }
}
