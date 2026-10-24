<?php
use Modulix\ActivationGuard;
use Modulix\Editor;
use PHPUnit\Framework\TestCase;

final class EditorTest extends TestCase {
    public function test_pro_bundle_and_config_load_before_core_editor_events_but_legacy_fallback_loads_after(): void {
        $this->assertSame([
            'elementor/editor/before_enqueue_scripts' => [Editor::PRO_HANDLE],
            'elementor/editor/after_enqueue_scripts' => [Editor::HANDLE],
        ], Editor::scriptHooks());
    }

    public function test_full_mode_enqueues_pro_bundle_with_upstream_dependencies_then_legacy_fallback(): void {
        $scripts = Editor::scriptDefinitions(ActivationGuard::FULL, 'https://x.test/modulix/assets/', '4.0.4');

        $this->assertSame([
            'backbone-marionette',
            'elementor-common',
            'elementor-editor-modules',
            'elementor-editor-document',
        ], $scripts[Editor::PRO_HANDLE]['dependencies']);
        $this->assertSame('https://x.test/modulix/assets/js/modulix-editor-pro.js', $scripts[Editor::PRO_HANDLE]['src']);
        $this->assertContains(Editor::PRO_HANDLE, $scripts[Editor::HANDLE]['dependencies']);
    }

    public function test_check_mode_enqueues_no_editor_scripts(): void {
        $this->assertSame([], Editor::scriptDefinitions(
            ActivationGuard::CHECK,
            'https://x.test/modulix/assets/',
            '4.0.4'
        ));
    }

    public function test_editor_config_has_base_and_filtered_keys_without_promotions(): void {
        $config = Editor::config('4.0.4', 'https://x.test/modulix/modules/', static function (string $hook, array $settings): array {
            self::assertSame('elementor_pro/editor/localize_settings', $hook);
            return array_replace_recursive($settings, [
                'forms' => ['recaptcha' => ['enabled' => false]],
                'widget_templates' => [12 => ['widgetType' => 'heading']],
                'should_show_promotion' => true,
                'loopFilter' => ['mainQueryPostType' => 'page', 'nonce' => 'rest-nonce'],
                'shareButtonsNetworks' => ['facebook' => ['title' => 'Facebook']],
                'woocommerce' => ['woocommercePages' => ['cart' => 42]],
                'eSearch' => ['nonce' => 'search-rest-nonce'],
                'elementPromotionURL' => 'https://upgrade.test/',
                'dynamicPromotionURL' => 'https://upgrade.test/',
                'promotionWidgets' => ['posts'],
            ]);
        });

        $this->assertSame('4.0.4', $config['version']);
        $this->assertTrue($config['isActive']);
        $this->assertSame('https://x.test/modulix/modules/', $config['urls']['modules']);
        $this->assertSame('', $config['urls']['connect']);
        $this->assertArrayHasKey('forms', $config);
        $this->assertArrayHasKey('widget_templates', $config);
        $this->assertArrayHasKey('loopFilter', $config);
        $this->assertArrayHasKey('shareButtonsNetworks', $config);
        $this->assertArrayHasKey('woocommerce', $config);
        $this->assertArrayHasKey('eSearch', $config);
        $this->assertFalse($config['should_show_promotion']);
        $this->assertSame([], $config['promotionWidgets']);
        $this->assertSame('', $config['elementPromotionURL']);
        $this->assertSame('', $config['dynamicPromotionURL']);
    }

    public function test_legacy_query_control_is_guarded_when_bundle_module_exists(): void {
        $source = (string) file_get_contents(__DIR__ . '/../assets/js/modulix-editor.js');

        $this->assertStringContainsString('elementorPro.modules.queryControl', $source);
    }
}
