<?php
// Modulix: derived from Elementor Pro 4.0.4, plugin.php (register_frontend_scripts, enqueue_frontend_scripts,
// get_frontend_file_url/path, get_responsive_stylesheet_templates). Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): script handles modulix-*, config ModulixFrontendConfig, no preview script,
// no licence or connect data, static helpers for lifted modules.
namespace Modulix;

/** Front-end runtime of the lifted modules: scripts, their config and responsive CSS templates. */
final class Frontend implements ModuleInterface {
    public const HANDLE = 'modulix-frontend';

    public function boot(): void {
        add_action('elementor/frontend/before_register_scripts', [$this, 'register_frontend_scripts']);
        add_action('elementor/frontend/before_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_filter('elementor/core/breakpoints/get_stylesheet_template', static function (array $templates): array {
            return self::add_responsive_templates($templates, glob(MODULIX_ASSETS_PATH . 'css/templates/*.css') ?: []);
        });
    }

    public function register_frontend_scripts(): void {
        $suffix = self::suffix();
        wp_register_script('modulix-webpack-runtime', MODULIX_URL . "assets/js/webpack-pro.runtime$suffix.js", [], MODULIX_VERSION, true);
        wp_register_script('modulix-elements-handlers', MODULIX_URL . "assets/js/elements-handlers$suffix.js", ['elementor-frontend'], MODULIX_VERSION, true);
        wp_register_script('smartmenus', MODULIX_URL . "assets/lib/smartmenus/jquery.smartmenus$suffix.js", ['jquery'], '1.2.1', true);
        $sticky_src = MODULIX_URL . "assets/lib/sticky/jquery.sticky$suffix.js";
        if (self::has_assets_loader()) {
            Plugin::elementor()->assets_loader->add_assets(['scripts' => ['e-sticky' => [
                'src' => $sticky_src, 'version' => MODULIX_VERSION, 'dependencies' => ['jquery'],
            ]]]);
        } else {
            wp_register_script('elementor-sticky', $sticky_src, ['jquery'], MODULIX_VERSION, true);
        }
    }

    public function enqueue_frontend_scripts(): void {
        // wp-i18n: elements-handlers.js has an external "wp.i18n" module (Pro gets it via wp_set_script_translations).
        $depends = ['modulix-webpack-runtime', 'elementor-frontend-modules', 'wp-i18n'];
        if (!self::has_assets_loader()) {
            $depends[] = 'elementor-sticky';
        }
        wp_enqueue_script(self::HANDLE, MODULIX_URL . 'assets/js/frontend' . self::suffix() . '.js', $depends, MODULIX_VERSION, true);
        wp_enqueue_script('modulix-elements-handlers');
        $settings = self::config(admin_url('admin-ajax.php'), wp_create_nonce('modulix-frontend'), MODULIX_ASSETS_URL,
            get_rest_url(), '1' === get_option('elementor_lazy_load_background_images', '1'));
        \Elementor\Utils::print_js_config(self::HANDLE, 'ModulixFrontendConfig', $settings);
    }

    /**
     * Same shape as Pro's ElementorProFrontendConfig. "popup" is normally added by Pro's popup module, which is
     * not lifted; the lifted elements-handlers.js reads popup.hasPopUps on every page.
     */
    public static function config(string $ajaxurl, string $nonce, string $assets_url, string $rest_url, bool $lazy_bg): array {
        return [
            'ajaxurl' => $ajaxurl,
            'nonce' => $nonce,
            'urls' => ['assets' => $assets_url, 'rest' => $rest_url],
            'settings' => ['lazy_load_background_images' => $lazy_bg],
            'popup' => ['hasPopUps' => false],
        ];
    }

    /** @param string[] $paths template files; generated names keep Pro's "custom-pro-" prefix */
    public static function add_responsive_templates(array $templates, array $paths): array {
        foreach ($paths as $path) {
            $templates['custom-pro-' . basename($path)] = $path;
        }
        return $templates;
    }

    public static function get_frontend_file_url(string $frontend_file_name, bool $custom_file): string {
        return $custom_file ? self::frontend_file($frontend_file_name)->get_url() : MODULIX_ASSETS_URL . 'css/' . $frontend_file_name;
    }

    public static function get_frontend_file_path(string $frontend_file_name, bool $custom_file): string {
        return $custom_file ? self::frontend_file($frontend_file_name)->get_path() : MODULIX_ASSETS_PATH . 'css/' . $frontend_file_name;
    }

    private static function frontend_file(string $frontend_file_name) {
        return Plugin::elementor()->frontend->get_frontend_file($frontend_file_name, 'custom-pro-', MODULIX_ASSETS_PATH . 'css/templates/' . $frontend_file_name);
    }

    private static function suffix(): string {
        return \Elementor\Utils::is_script_debug() ? '' : '.min';
    }

    private static function has_assets_loader(): bool {
        return (bool) Plugin::elementor()->assets_loader;
    }
}
