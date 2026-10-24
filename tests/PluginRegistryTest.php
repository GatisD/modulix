<?php
use Modulix\Plugin;
use PHPUnit\Framework\TestCase;

final class PluginRegistryTest extends TestCase {
    public function test_phase1_modules_in_boot_order(): void {
        $this->assertSame(['dynamic-tags', 'theme-builder', 'custom-css', 'sticky', 'motion-fx', 'nav-menu', 'floating-buttons', 'gallery', 'library', 'query-control', 'posts', 'animated-headline', 'flip-box', 'call-to-action', 'pricing', 'countdown', 'share-buttons', 'blockquote', 'table-of-contents', 'hotspot', 'off-canvas', 'progress-tracker', 'code-highlight', 'slides', 'video-playlist', 'social', 'mega-menu', 'lottie', 'link-in-bio', 'nested-carousel', 'carousel', 'page-transitions', 'scroll-snap', 'custom-attributes', 'attributes', 'role-manager', 'element-manager', 'custom-code', 'global-widget', 'theme-elements', 'loop-builder', 'loop-filter', 'search', 'popup', 'forms', 'wp-cli', 'assets-manager', 'woocommerce', 'editor', 'frontend', 'no-upsell'], Plugin::registry()->ids());
    }
}
