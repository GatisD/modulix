<?php
/**
 * Plugin Name: Modulix
 * Plugin URI: https://github.com/GatisD/modulix
 * Description: Open-source replacement for Elementor Pro features: Theme Builder (header, footer, single, archive, search, 404), Posts, Archive Posts, Portfolio, theme widgets, Gallery, Template, dynamic tags incl. ACF, Motion Effects, Sticky, Nav Menu, Floating Buttons, Custom CSS, and a readiness check to run before switching. Contains code derived from Elementor Pro (GPLv3), modified - see NOTICE.md.
 * Author: Gatis Daugavietis
 * Version: 0.7.0
 * Requires PHP: 8.1
 * Requires Plugins: elementor
 * License: GPL-3.0-or-later
 */
defined('ABSPATH') || exit;

define('MODULIX_VERSION', '0.7.0');
define('MODULIX_PATH', plugin_dir_path(__FILE__));
define('MODULIX_MODULES_PATH', MODULIX_PATH . 'modules/');
define('MODULIX_URL', plugins_url('/', __FILE__));
define('MODULIX_MODULES_URL', MODULIX_URL . 'modules/');
define('MODULIX_ASSETS_PATH', MODULIX_PATH . 'assets/');
define('MODULIX_ASSETS_URL', MODULIX_URL . 'assets/');

require_once __DIR__ . '/src/autoload.php';

function modulix_mode(): string {
    return \Modulix\ActivationGuard::mode(\Modulix\ActivationGuard::activePlugins(
        (array) get_option('active_plugins', []),
        is_multisite() ? (array) get_site_option('active_sitewide_plugins', []) : []
    ));
}

register_activation_hook(__FILE__, static function (): void {
    // During activation Modulix itself is not yet in active_plugins; the mode only looks at Pro and core.
    if (modulix_mode() === \Modulix\ActivationGuard::BLOCKED) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(esc_html((string) \Modulix\ActivationGuard::message(\Modulix\ActivationGuard::BLOCKED)), 'Modulix', ['back_link' => true]);
    }
    if (\Modulix\Mcp\Bootstrap::preview()) {
        \Modulix\Mcp\LogTable::install();
    }
});

// Registered when the plugin file loads, so themes and plugins that look for Elementor Pro classes later find them.
if (modulix_mode() === \Modulix\ActivationGuard::FULL) {
    \Modulix\Compat\ProAliases::register();
    \Modulix\Mcp\Bootstrap::register();
}

\Modulix\Readiness\AdminPage::register();
\Modulix\Readiness\Cli::register();
if (\Modulix\Mcp\Bootstrap::preview()) {
    \Modulix\Mcp\LogTable::registerUpgradeCheck();
}

add_action('plugins_loaded', static function (): void {
    $mode = modulix_mode();
    $msg = \Modulix\ActivationGuard::message($mode);
    if ($msg !== null) {
        $class = $mode === \Modulix\ActivationGuard::CHECK ? 'notice-warning' : 'notice-error';
        add_action('admin_notices', static function () use ($msg, $class): void {
            echo '<div class="notice ' . esc_attr($class) . '"><p>' . esc_html($msg) . '</p></div>';
        });
    }
    if ($mode !== \Modulix\ActivationGuard::FULL) {
        return;
    }
    add_action('elementor/init', static function (): void {
        \Modulix\Plugin::registry()->bootAll();
    });
});
