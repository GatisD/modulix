<?php
namespace Modulix\Readiness {
    final class WpSourceTestState {
        public static array $meta = [];
        public static array $metaCalls = [];
    }

    function get_post_meta(int $id, string $key, bool $single): mixed {
        WpSourceTestState::$metaCalls[] = [$id, $key, $single];
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
            WpSourceTestState::$metaCalls = [];
            unset($GLOBALS['wpdb']);
        }

        public function test_documents_decode_string_data_and_include_the_saved_theme_location(): void {
            $elements = [['elType' => 'widget', 'widgetType' => 'heading']];
            WpSourceTestState::$meta[17] = [
                '_elementor_data' => json_encode($elements, JSON_THROW_ON_ERROR),
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
            $this->assertSame($elements, $documents[0]->elements);
            $this->assertSame('elementor_head', $documents[0]->themeLocation);
        }

        public function test_documents_keep_array_element_data_and_saved_conditions(): void {
            $elements = [['elType' => 'widget', 'widgetType' => 'form']];
            WpSourceTestState::$meta[18] = [
                '_elementor_data' => $elements,
                '_elementor_page_settings' => [],
                '_elementor_template_type' => 'floating-buttons',
                '_elementor_location' => '',
                '_elementor_conditions' => ['include/general'],
            ];
            $GLOBALS['wpdb'] = new class {
                public string $posts = 'wp_posts';
                public string $postmeta = 'wp_postmeta';
                public function prepare(string $query, mixed ...$args): string { return $query; }
                public function get_results(string $query): array {
                    return [(object) ['ID' => 18, 'post_title' => 'Contact', 'post_type' => 'elementor_library']];
                }
            };

            $documents = iterator_to_array(WpSource::documents(2));

            $this->assertCount(1, $documents);
            $this->assertSame($elements, $documents[0]->elements);
            $this->assertSame(['include/general'], $documents[0]->conditions);
        }

        public function test_documents_read_conditions_only_for_floating_buttons(): void {
            WpSourceTestState::$meta = [
                20 => [
                    '_elementor_data' => [],
                    '_elementor_page_settings' => [],
                    '_elementor_template_type' => '',
                    '_elementor_location' => '',
                ],
                21 => [
                    '_elementor_data' => [],
                    '_elementor_page_settings' => [],
                    '_elementor_template_type' => 'header',
                    '_elementor_location' => 'header',
                ],
                22 => [
                    '_elementor_data' => [],
                    '_elementor_page_settings' => [],
                    '_elementor_location' => 'elementor_head',
                ],
                23 => [
                    '_elementor_data' => [],
                    '_elementor_page_settings' => [],
                    '_elementor_template_type' => 'floating-buttons',
                    '_elementor_location' => '',
                    '_elementor_conditions' => ['include/general'],
                ],
            ];
            $GLOBALS['wpdb'] = new class {
                public string $posts = 'wp_posts';
                public string $postmeta = 'wp_postmeta';
                public function prepare(string $query, mixed ...$args): string { return $query; }
                public function get_results(string $query): array {
                    return [
                        (object) ['ID' => 20, 'post_title' => 'Page', 'post_type' => 'page'],
                        (object) ['ID' => 21, 'post_title' => 'Header', 'post_type' => 'elementor_library'],
                        (object) ['ID' => 22, 'post_title' => 'Snippet', 'post_type' => 'elementor_snippet'],
                        (object) ['ID' => 23, 'post_title' => 'Contact', 'post_type' => 'elementor_library'],
                    ];
                }
            };

            $documents = iterator_to_array(WpSource::documents());
            $conditionCalls = array_values(array_filter(
                WpSourceTestState::$metaCalls,
                static fn(array $call): bool => '_elementor_conditions' === $call[1]
            ));

            $this->assertCount(4, $documents);
            $this->assertSame([[23, '_elementor_conditions', true]], $conditionCalls);
            $this->assertSame([], $documents[0]->conditions);
            $this->assertSame([], $documents[1]->conditions);
            $this->assertSame([], $documents[2]->conditions);
            $this->assertSame(['include/general'], $documents[3]->conditions);
        }
    }
}
