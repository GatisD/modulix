<?php
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class QueryControlTaxonomySecurityTest extends TestCase {
    public static function setUpBeforeClass(): void {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }

        eval(<<<'PHP'
namespace Modulix\Base {
    abstract class Module_Base {}
}
namespace Modulix\Core\Security {
    final class Access_Control {
        public static function verify_user_editing_capability(): void {}
        public static function verify_post_edit_access(int $postId): void {}
        public static function user_can_access_private_posts(): bool { return false; }
        public static function user_can_edit(int $postId): bool { return false; }
    }
}
namespace {
    final class QueryControlTaxonomyState {
        public static array $taxonomies = [];
        public static array $capabilities = [];
        public static array $terms = [];
    }
    final class WP_Error {
        public function __construct(private string $code, private string $message) {}
        public function get_error_code(): string { return $this->code; }
        public function get_error_message(): string { return $this->message; }
    }
    final class WP_Term {
        public function __construct(
            public int $term_id,
            public int $term_taxonomy_id,
            public string $taxonomy,
            public string $name,
            public int $parent = 0
        ) {}
    }
    function sanitize_key(string $key): string { return strtolower((string) preg_replace('/[^a-z0-9_\-]/i', '', $key)); }
}
namespace Modulix\Modules\QueryControl {
    function get_taxonomies(array $args = [], string $output = 'names'): array {
        return $output === 'objects'
            ? \QueryControlTaxonomyState::$taxonomies
            : array_keys(\QueryControlTaxonomyState::$taxonomies);
    }
    function get_taxonomy(string $name): ?object { return \QueryControlTaxonomyState::$taxonomies[$name] ?? null; }
    function is_taxonomy_viewable(object $taxonomy): bool {
        return !empty($taxonomy->public) || !empty($taxonomy->publicly_queryable);
    }
    function current_user_can(string $capability, mixed ...$args): bool {
        return \QueryControlTaxonomyState::$capabilities[$capability] ?? false;
    }
    function get_terms(array $args): array|\WP_Error {
        $taxonomies = (array) ($args['taxonomy'] ?? []);
        return array_values(array_filter(
            \QueryControlTaxonomyState::$terms,
            static fn(\WP_Term $term): bool => in_array($term->taxonomy, $taxonomies, true)
        ));
    }
    function is_wp_error(mixed $value): bool { return $value instanceof \WP_Error; }
    function apply_filters(string $name, mixed $value, mixed ...$args): mixed { return $value; }
}
PHP);

        require_once __DIR__ . '/../modules/query-control/module.php';
    }

    protected function setUp(): void {
        QueryControlTaxonomyState::$taxonomies = [
            'category' => (object) [
                'name' => 'category',
                'public' => true,
                'publicly_queryable' => true,
                'cap' => (object) ['assign_terms' => 'edit_posts', 'manage_terms' => 'manage_categories'],
            ],
            'private_topics' => (object) [
                'name' => 'private_topics',
                'public' => false,
                'publicly_queryable' => false,
                'cap' => (object) ['assign_terms' => 'assign_private_topics', 'manage_terms' => 'manage_private_topics'],
            ],
        ];
        QueryControlTaxonomyState::$capabilities = [];
        QueryControlTaxonomyState::$terms = [
            new WP_Term(1, 101, 'category', 'Public topic'),
            new WP_Term(2, 202, 'private_topics', 'Private topic'),
        ];
    }

    private function module(): \Modulix\Modules\QueryControl\Module {
        $reflection = new ReflectionClass(\Modulix\Modules\QueryControl\Module::class);
        return $reflection->newInstanceWithoutConstructor();
    }

    public function test_contributor_and_author_cannot_autocomplete_private_taxonomy(): void {
        foreach (['contributor', 'author'] as $role) {
            try {
                $this->module()->ajax_posts_filter_autocomplete([
                    'q' => 'topic',
                    'autocomplete' => [
                        'object' => 'tax',
                        'display' => 'minimal',
                        'query' => ['taxonomy' => ['private_topics']],
                    ],
                ]);
                $this->fail($role . ' unexpectedly received private taxonomy terms.');
            } catch (Exception $error) {
                $this->assertSame('QueryControlAutocomplete:No accessible taxonomies', $error->getMessage());
            }
        }
    }

    public function test_contributor_and_author_cannot_resolve_private_taxonomy_titles(): void {
        foreach (['contributor', 'author'] as $role) {
            $result = $this->module()->ajax_posts_control_value_titles([
                'id' => [202],
                'get_titles' => [
                    'object' => 'tax',
                    'display' => 'minimal',
                    'query' => ['taxonomy' => ['private_topics']],
                ],
            ]);

            $this->assertSame([], $result, $role . ' unexpectedly received a private taxonomy title.');
        }
    }

    public function test_public_taxonomy_remains_available(): void {
        $autocomplete = $this->module()->ajax_posts_filter_autocomplete([
            'q' => 'topic',
            'autocomplete' => [
                'object' => 'tax',
                'display' => 'minimal',
                'query' => ['taxonomy' => ['category']],
            ],
        ]);
        $titles = $this->module()->ajax_posts_control_value_titles([
            'id' => [101],
            'get_titles' => [
                'object' => 'tax',
                'display' => 'minimal',
                'query' => ['taxonomy' => ['category']],
            ],
        ]);

        $this->assertSame([['id' => 101, 'text' => 'Public topic']], $autocomplete['results']);
        $this->assertSame([101 => 'Public topic'], $titles);
    }
}
