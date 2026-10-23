<?php
/**
 * Plugin Name: Modulix
 * Plugin URI: https://github.com/GatisD/modulix
 * Description: Open-source replacement for Elementor Pro features: Theme Builder header and footer, Motion Effects, Sticky, Nav Menu, Floating Buttons, Custom CSS. Contains code derived from Elementor Pro (GPLv3), modified - see NOTICE.md.
 * Author: Gatis Daugavietis
 * Version: 0.4.1
 * Requires PHP: 8.1
 * Requires Plugins: elementor
 * License: GPL-3.0-or-later
 */
defined('ABSPATH') || exit;

define('MODULIX_VERSION', '0.4.1');
define('MODULIX_PATH', plugin_dir_path(__FILE__));
define('MODULIX_URL', plugins_url('/', __FILE__));
define('MODULIX_ASSETS_PATH', MODULIX_PATH . 'assets/');
define('MODULIX_ASSETS_URL', MODULIX_URL . 'assets/');

require_once __DIR__ . '/src/autoload.php';

function modulix_guard_message(): ?string {
    return \Modulix\ActivationGuard::check(\Modulix\ActivationGuard::activePlugins(
        (array) get_option('active_plugins', []),
        is_multisite() ? (array) get_site_option('active_sitewide_plugins', []) : []
    ));
}

register_activation_hook(__FILE__, static function (): void {
    // During activation Modulix itself is not yet in active_plugins; the guard only looks at Pro/core.
    $msg = modulix_guard_message();
    if ($msg !== null) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(esc_html($msg), 'Modulix', ['back_link' => true]);
    }
});

add_action('plugins_loaded', static function (): void {
    $msg = modulix_guard_message();
    if ($msg !== null) {
        add_action('admin_notices', static function () use ($msg): void {
            echo '<div class="notice notice-error"><p>' . esc_html($msg) . '</p></div>';
        });
        return;
    }
    add_action('elementor/init', static function (): void {
        \Modulix\Plugin::registry()->bootAll();
    });
});
