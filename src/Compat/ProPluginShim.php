<?php
namespace Modulix\Compat;

/**
 * Stands in for ElementorPro\Plugin (aliased by ProAliases). Covers the calls seen in themes and plugins:
 * Plugin::instance(), ->modules_manager->get_modules($name) and Plugin::elementor().
 */
final class ProPluginShim {
    private static ?self $instance = null;
    public ProModulesManager $modules_manager;

    private function __construct() {
        $this->modules_manager = new ProModulesManager();
    }

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    public static function elementor(): \Elementor\Plugin {
        return \Elementor\Plugin::$instance;
    }
}
