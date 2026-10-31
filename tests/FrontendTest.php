<?php
use Modulix\Frontend;
use PHPUnit\Framework\TestCase;

final class FrontendTest extends TestCase {
    public function test_responsive_templates_keep_pros_custom_pro_prefix(): void {
        $out = Frontend::add_responsive_templates(['custom-frontend.min.css' => '/core/x.css'],
            ['/m/assets/css/templates/widget-nav-menu.min.css', '/m/assets/css/templates/widget-nav-menu-rtl.min.css']);
        $this->assertSame([
            'custom-frontend.min.css' => '/core/x.css',
            'custom-pro-widget-nav-menu.min.css' => '/m/assets/css/templates/widget-nav-menu.min.css',
            'custom-pro-widget-nav-menu-rtl.min.css' => '/m/assets/css/templates/widget-nav-menu-rtl.min.css',
        ], $out);
    }

    public function test_config_has_pros_shape_including_popup_default(): void {
        $c = Frontend::config('https://x/wp-admin/admin-ajax.php', 'n0nce', 'https://x/m/assets/', 'https://x/wp-json/', true);
        $this->assertSame(['hasPopUps' => false], $c['popup']);
        $this->assertSame('https://x/m/assets/', $c['urls']['assets']);
        $this->assertTrue($c['settings']['lazy_load_background_images']);
    }

    public function test_frontend_settings_use_pros_compatibility_filter(): void {
        $settings = ['ajaxurl' => 'https://x/wp-admin/admin-ajax.php'];

        $filtered = Frontend::filter_settings($settings, static function (string $hook, array $value): array {
            self::assertSame('elementor_pro/frontend/localize_settings', $hook);
            $value['woocommerce'] = [
                'menu_cart' => [
                    'cart_page_url' => 'https://x/cart/',
                    'checkout_page_url' => 'https://x/checkout/',
                    'fragments_nonce' => 'wc-nonce',
                ],
                'productAddedToCart' => false,
            ];
            return $value;
        });

        $this->assertSame('wc-nonce', $filtered['woocommerce']['menu_cart']['fragments_nonce']);
        $this->assertFalse($filtered['woocommerce']['productAddedToCart']);
    }

    public function test_header_version_matches_constant(): void {
        $src = file_get_contents(dirname(__DIR__) . '/modulix.php');
        preg_match('/^ \* Version: (\S+)$/m', $src, $h);
        preg_match("/define\\('MODULIX_VERSION', '([^']+)'\\)/", $src, $c);
        $this->assertNotEmpty($h);
        $this->assertSame($h[1], $c[1] ?? null);
    }

    public function test_registry_has_plan3_modules_before_no_upsell(): void {
        $this->assertSame(['dynamic-tags', 'theme-builder', 'custom-css', 'sticky', 'motion-fx', 'nav-menu', 'floating-buttons', 'gallery', 'library', 'query-control', 'posts', 'animated-headline', 'flip-box', 'call-to-action', 'pricing', 'countdown', 'share-buttons', 'blockquote', 'table-of-contents', 'hotspot', 'off-canvas', 'progress-tracker', 'code-highlight', 'slides', 'video-playlist', 'social', 'mega-menu', 'lottie', 'link-in-bio', 'nested-carousel', 'carousel', 'page-transitions', 'scroll-snap', 'custom-attributes', 'attributes', 'role-manager', 'element-manager', 'custom-code', 'global-widget', 'theme-elements', 'loop-builder', 'loop-filter', 'search', 'popup', 'forms', 'wp-cli', 'assets-manager', 'woocommerce', 'display-conditions', 'app', 'editor', 'frontend', 'no-upsell'],
            \Modulix\Plugin::registry()->ids());
    }
}
