<?php
namespace Modulix\Compat;

/**
 * Stands in for ElementorPro\Plugin (aliased by ProAliases). Covers the calls seen in themes and plugins:
 * Plugin::instance(), ->modules_manager->get_modules($name), ->php_api and Plugin::elementor().
 */
final class ProPluginShim {
    private static ?self $instance = null;
    public ProModulesManager $modules_manager;
    /** Wrapper around move_uploaded_file() that Forms file uploads call (lifted Pro class; absent in unit tests). */
    public ?object $php_api;

    private function __construct() {
        $this->modules_manager = new ProModulesManager();
        $this->php_api = class_exists(\Modulix\Core\PHP_Api::class) ? new \Modulix\Core\PHP_Api() : null;
    }

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    public static function elementor(): \Elementor\Plugin {
        return \Elementor\Plugin::$instance;
    }
}
