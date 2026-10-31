<?php
namespace Modulix;

/** Loads the scoped Custom Fonts and Custom Icons assets only on their WordPress admin screens. */
final class AssetsManagerAdmin {
    private const POST_TYPES = ['elementor_font', 'elementor_icons'];

    public static function supportsPostType(?string $postType): bool {
        return in_array($postType, self::POST_TYPES, true);
    }

    public static function enqueue(): void {
        $screen = get_current_screen();
        if (!$screen || !self::supportsPostType($screen->post_type ?? null)) {
            return;
        }

        wp_enqueue_style(
            'modulix-admin-assets',
            MODULIX_ASSETS_URL . 'css/modulix-admin-assets.css',
            [],
            MODULIX_VERSION
        );
        wp_enqueue_script(
            'modulix-admin-assets',
            MODULIX_ASSETS_URL . 'js/modulix-admin-assets.js',
            ['elementor-admin', 'wp-i18n'],
            MODULIX_VERSION,
            true
        );
        I18n::setScriptTranslations('modulix-admin-assets');
    }
}
