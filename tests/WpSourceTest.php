<?php
namespace Modulix\Readiness {
    final class WpSourceTestState {
        public static array $meta = [];
    }

    function get_post_meta(int $id, string $key, bool $single): mixed {
        return WpSourceTestState::$meta[$id][$key] ?? '';
    }
}

namespace {
    use Modulix\Readiness\WpSource;
    use Modulix\Readiness\WpSourceTestState;
    use PHPUnit\Framework\TestCase;

    final class WpSourceTest extends TestCase {
        protected function tearDown(): void {
            WpSourceTestState::$meta = [];
            unset($GLOBALS['wpdb']);
        }

        public function test_documents_include_the_saved_theme_location(): void {
            WpSourceTestState::$meta[17] = [
                '_elementor_data' => '[]',
                '_elementor_page_settings' => [],
                '_elementor_template_type' => 'code_snippet',
                '_elementor_location' => 'elementor_head',
            ];
            $GLOBALS['wpdb'] = new class {
                public string $posts = 'wp_posts';
                public string $postmeta = 'wp_postmeta';
                public function prepare(string $query, mixed ...$args): string { return $query; }
                public function get_results(string $query): array {
                    return [(object) ['ID' => 17, 'post_title' => 'Snippet', 'post_type' => 'elementor_snippet']];
                }
            };

            $documents = iterator_to_array(WpSource::documents(2));

            $this->assertCount(1, $documents);
            $this->assertSame('elementor_head', $documents[0]->themeLocation);
        }
    }
}
