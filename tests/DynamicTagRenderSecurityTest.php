<?php
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DynamicTagRenderSecurityTest extends TestCase {
    public static function setUpBeforeClass(): void {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }

        eval(<<<'PHP'
namespace Modulix\Modules\DynamicTags\Tags\Base {
    abstract class Pro_Tag {
        private array $testSettings = [];
        public function set_test_settings(array $settings): void { $this->testSettings = $settings; }
        public function get_settings(): array { return $this->testSettings; }
        public function add_control(...$args): void {}
    }
    abstract class Pro_Data_Tag extends Pro_Tag {}
}
namespace Elementor {
    if (!class_exists(Plugin::class, false)) {
        final class Plugin {
            public static ?object $instance = null;
            public function __construct(public object $images_manager, public object $frontend) {}
        }
    }
    if (!class_exists(Controls_Manager::class, false)) {
        final class Controls_Manager {
            public const CHOOSE = 'choose';
            public const MEDIA = 'media';
            public const TEXT = 'text';
            public const SELECT = 'select';
        }
    }
    if (!class_exists(Embed::class, false)) {
        final class Embed {}
    }
}
namespace {
    final class DynamicTagRenderState {
        public static array $posts = [];
        public static array $users = [];
        public static array $terms = [];
        public static array $taxonomies = [];
        public static array $capabilities = [];
        public static array $images = [];
        public static array $publishedPostCounts = [];
    }
    function get_post(int $id): ?object { return DynamicTagRenderState::$posts[$id] ?? null; }
    function get_userdata(int $id): object|false { return DynamicTagRenderState::$users[$id] ?? false; }
    function get_term(int $id): object|false { return DynamicTagRenderState::$terms[$id] ?? false; }
    function get_taxonomy(string $name): object|false { return DynamicTagRenderState::$taxonomies[$name] ?? false; }
    function is_taxonomy_viewable(object $taxonomy): bool { return $taxonomy->publicly_queryable; }
    function current_user_can(string $capability, mixed ...$args): bool {
        $key = $capability . ($args ? ':' . implode(':', array_map('strval', $args)) : '');
        return DynamicTagRenderState::$capabilities[$key] ?? false;
    }
    function wp_get_attachment_image_src(int $id, string $size): array|false {
        return isset(DynamicTagRenderState::$images[$id]) ? [DynamicTagRenderState::$images[$id]] : false;
    }
    function get_post_types(array $args, string $output): array { return ['post']; }
    function count_user_posts(int $id, string|array $postTypes, bool $publicOnly): int {
        return DynamicTagRenderState::$publishedPostCounts[$id] ?? 0;
    }
    function get_author_posts_url(int $id): string { return 'https://' . 'author' . '.invalid/' . $id; }
    function get_permalink(object $post): string { return 'https://' . 'content' . '.invalid/' . $post->ID; }
    function get_term_link(object $term): string { return 'https://' . 'term' . '.invalid/' . $term->term_id; }
    function get_attachment_link(int $id): string { return 'https://' . 'media' . '.invalid/' . $id; }
    function esc_url_raw(string $url): string { return filter_var($url, FILTER_VALIDATE_URL) ? $url : ''; }
    function is_wp_error(mixed $value): bool { return false; }
}
PHP);

        require_once __DIR__ . '/../modules/dynamic-tags/tags/lightbox.php';
        require_once __DIR__ . '/../modules/dynamic-tags/tags/internal-url.php';
    }

    protected function setUp(): void {
        DynamicTagRenderState::$posts = [];
        DynamicTagRenderState::$users = [];
        DynamicTagRenderState::$terms = [];
        DynamicTagRenderState::$taxonomies = [];
        DynamicTagRenderState::$capabilities = [];
        DynamicTagRenderState::$images = [];
        DynamicTagRenderState::$publishedPostCounts = [];
    }

    public function test_lightbox_render_uses_one_validated_attachment_for_url_and_metadata(): void {
        $trustedUrl = 'https://' . 'asset' . '.invalid/public.jpg';
        $savedUrl = 'https://' . 'saved' . '.invalid/mismatch.jpg';
        DynamicTagRenderState::$posts[11] = (object) [
            'ID' => 11,
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_password' => '',
            'post_parent' => 0,
        ];
        DynamicTagRenderState::$images[11] = $trustedUrl;

        $imagesManager = new class {
            public array $calls = [];
            public function get_lightbox_image_attributes(int $id): array {
                $this->calls[] = $id;
                return ['title' => 'Public image'];
            }
        };
        \Elementor\Plugin::$instance = new \Elementor\Plugin(
            $imagesManager,
            new class {
                public function create_action_hash(string $action, array $value): string {
                    return json_encode([$action, $value], JSON_THROW_ON_ERROR);
                }
            }
        );

        $tag = new \Modulix\Modules\DynamicTags\Tags\Lightbox();
        $tag->set_test_settings([
            'type' => 'image',
            'image' => ['id' => 11, 'url' => $savedUrl],
        ]);

        ob_start();
        $tag->render();
        $output = (string) ob_get_clean();
        $payload = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame($trustedUrl, $payload[1]['url']);
        $this->assertNotSame($savedUrl, $payload[1]['url']);
        $this->assertSame([11], $imagesManager->calls);
    }

    public function test_lightbox_render_rejects_non_attachment_and_protected_attachment_metadata(): void {
        DynamicTagRenderState::$posts = [
            12 => (object) ['ID' => 12, 'post_type' => 'post', 'post_status' => 'private', 'post_password' => '', 'post_parent' => 0],
            13 => (object) ['ID' => 13, 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_password' => '', 'post_parent' => 14],
            14 => (object) ['ID' => 14, 'post_type' => 'page', 'post_status' => 'private', 'post_password' => '', 'post_parent' => 0],
            15 => (object) ['ID' => 15, 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_password' => 'set', 'post_parent' => 0],
        ];
        $imagesManager = new class {
            public array $calls = [];
            public function get_lightbox_image_attributes(int $id): array { $this->calls[] = $id; return []; }
        };
        \Elementor\Plugin::$instance = new \Elementor\Plugin(
            $imagesManager,
            new class {
                public function create_action_hash(string $action, array $value): string { return 'unexpected'; }
            }
        );

        foreach ([12, 13, 15] as $id) {
            $tag = new \Modulix\Modules\DynamicTags\Tags\Lightbox();
            $tag->set_test_settings([
                'type' => 'image',
                'image' => ['id' => $id, 'url' => 'https://' . 'safe' . '.invalid/image.jpg'],
            ]);
            ob_start();
            $tag->render();
            $this->assertSame('', (string) ob_get_clean(), 'Rejected IDs must not render a lightbox action.');
        }

        $this->assertSame([], $imagesManager->calls);
    }

    public function test_internal_author_url_requires_a_public_post_or_listing_access(): void {
        DynamicTagRenderState::$users[17] = (object) ['ID' => 17];
        $tag = new \Modulix\Modules\DynamicTags\Tags\Internal_URL();
        $tag->set_test_settings(['type' => 'author', 'author_id' => 17]);

        $this->assertSame('', $tag->get_value());

        DynamicTagRenderState::$publishedPostCounts[17] = 1;
        $this->assertSame('https://' . 'author' . '.invalid/17', $tag->get_value());
    }

    public function test_internal_taxonomy_url_renders_a_public_term(): void {
        DynamicTagRenderState::$terms[201] = (object) [
            'term_id' => 201,
            'taxonomy' => 'category',
        ];
        DynamicTagRenderState::$taxonomies['category'] = (object) ['publicly_queryable' => true];
        $tag = new \Modulix\Modules\DynamicTags\Tags\Internal_URL();
        $tag->set_test_settings(['type' => 'taxonomy', 'taxonomy_id' => 201]);

        $this->assertSame('https://' . 'term' . '.invalid/201', $tag->get_value());
    }

    public function test_internal_taxonomy_url_rejects_a_saved_non_viewable_term(): void {
        DynamicTagRenderState::$terms[202] = (object) [
            'term_id' => 202,
            'taxonomy' => 'private_topics',
        ];
        DynamicTagRenderState::$taxonomies['private_topics'] = (object) ['publicly_queryable' => false];
        $tag = new \Modulix\Modules\DynamicTags\Tags\Internal_URL();
        $tag->set_test_settings(['type' => 'taxonomy', 'taxonomy_id' => 202]);

        $this->assertSame('', $tag->get_value());
    }
}
