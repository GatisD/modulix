<?php
use Modulix\ActivationGuard;
use Modulix\App;
use PHPUnit\Framework\TestCase;

final class AppTest extends TestCase {
    public function test_full_mode_registers_site_editor_assets_and_config(): void {
        $assets = App::assetDefinitions(
            ActivationGuard::FULL,
            'https://example.test/plugin/assets/',
            '4.0.4'
        );

        $this->assertSame('https://example.test/plugin/assets/js/modulix-app.js', $assets['script']['src']);
        $this->assertSame('https://example.test/plugin/assets/css/modulix-app.css', $assets['style']['src']);
        $this->assertContains('elementor-app-packages', $assets['script']['dependencies']);
        $this->assertNotContains('elementor-app', $assets['script']['dependencies']);
        $this->assertContains('elementor-v2-ui', $assets['script']['dependencies']);
        $this->assertContains('elementor-v2-icons', $assets['script']['dependencies']);
        $this->assertSame('modulix', App::TRANSLATION_DOMAIN);

        $this->assertSame([
            'menu_url' => 'admin.php?page=elementor-app#/site-editor',
            'hasPro' => true,
        ], App::coreAppSettings('admin.php?page=elementor-app#/site-editor'));

        $this->assertSame(
            ['baseUrl' => 'https://example.test/plugin/'],
            App::baseConfig(ActivationGuard::FULL, 'https://example.test/plugin/')
        );
        $this->assertSame([
            'urls' => ['legacy_view' => 'https://example.test/wp-admin/edit.php?post_type=elementor_library&tabs_group=theme'],
        ], App::siteEditorConfig(
            ActivationGuard::FULL,
            'https://example.test/wp-admin/edit.php?post_type=elementor_library&tabs_group=theme'
        ));
    }

    public function test_check_mode_registers_no_site_editor_assets_or_config(): void {
        $this->assertSame([], App::assetDefinitions(
            ActivationGuard::CHECK,
            'https://example.test/plugin/assets/',
            '4.0.4'
        ));
        $this->assertSame([], App::baseConfig(ActivationGuard::CHECK, 'https://example.test/plugin/'));
        $this->assertSame([], App::siteEditorConfig(
            ActivationGuard::CHECK,
            'https://example.test/wp-admin/edit.php?post_type=elementor_library&tabs_group=theme'
        ));
    }

    public function test_app_is_registered_immediately_before_editor(): void {
        $ids = \Modulix\Plugin::registry()->ids();
        $this->assertSame('app', $ids[array_search('editor', $ids, true) - 1]);
    }

    public function test_template_type_config_removes_elementor_documentation_urls_only(): void {
        $serverUrl = 'https://' . 'go.' . 'elementor.' . 'com/theme-builder';
        $videoUrl = 'https://' . 'video.' . 'invalid/embed/example';
        $types = [
            'header',
            'footer',
            'single-page',
            'single-post',
            'archive',
            'search-results',
            'error-404',
            'product',
            'product-archive',
            'loop-item',
        ];
        $config = array_map(static fn (string $type): array => [
            'type' => $type,
            'tooltip_data' => [
                'title' => 'What is ' . $type . '?',
                'content' => $type . ' help text.',
                'docs' => $serverUrl . '/' . $type,
                'video_url' => $videoUrl . '/' . $type,
            ],
            'nested' => [
                'documentation_url' => $serverUrl . '/nested/' . $type,
                'label' => 'Keep this text.',
            ],
        ], $types);

        $sanitized = App::sanitizeTemplateTypeConfig($config);

        foreach ($sanitized as $index => $typeConfig) {
            $type = $types[$index];
            $this->assertSame($type, $typeConfig['type']);
            $this->assertSame('What is ' . $type . '?', $typeConfig['tooltip_data']['title']);
            $this->assertSame($type . ' help text.', $typeConfig['tooltip_data']['content']);
            $this->assertSame($videoUrl . '/' . $type, $typeConfig['tooltip_data']['video_url']);
            $this->assertArrayNotHasKey('docs', $typeConfig['tooltip_data']);
            $this->assertArrayNotHasKey('documentation_url', $typeConfig['nested']);
            $this->assertSame('Keep this text.', $typeConfig['nested']['label']);
        }
        $this->assertStringNotContainsString($serverUrl, json_encode($sanitized, JSON_THROW_ON_ERROR));
    }
}
