<?php
namespace Modulix;

/** Editor-only scripts: control views that Elementor Pro ships in its editor bundle (Query control). */
final class Editor implements ModuleInterface {
    public const HANDLE = 'modulix-editor';

    public function boot(): void {
        add_action('elementor/editor/after_enqueue_scripts', static function (): void {
            wp_enqueue_script(self::HANDLE, MODULIX_ASSETS_URL . 'js/modulix-editor.js', ['elementor-editor', 'wp-i18n'], MODULIX_VERSION, true);
        });
    }
}
