<?php
use Modulix\Compat\ProAliases;
use Modulix\Compat\ProPluginShim;
use PHPUnit\Framework\TestCase;

final class ProAliasesTest extends TestCase {
    public function test_target_maps_pro_names(): void {
        $this->assertSame('Modulix\\Modules\\ThemeBuilder\\Module', ProAliases::target('ElementorPro\\Modules\\ThemeBuilder\\Module'));
        $this->assertSame('Modulix\\Modules\\ThemeBuilder\\Module', ProAliases::target('\\ElementorPro\\Modules\\ThemeBuilder\\Module'));
        $this->assertSame(ProPluginShim::class, ProAliases::target('ElementorPro\\Plugin'));
        $this->assertNull(ProAliases::target('Elementor\\Plugin'));
        $this->assertNull(ProAliases::target('ElementorPro\\'));
        $this->assertNull(ProAliases::target('ElementorProX\\Plugin'));
    }

    public function test_alias_only_for_classes_modulix_has(): void {
        ProAliases::register();
        try {
            $this->assertTrue(class_exists('ElementorPro\\Coverage'));
            $this->assertSame(\Modulix\Coverage::WIDGETS, \ElementorPro\Coverage::WIDGETS);
            $this->assertFalse(class_exists('ElementorPro\\License\\API'));
        } finally {
            spl_autoload_unregister([ProAliases::class, 'load']);
        }
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function test_no_alias_once_elementor_pro_is_loaded(): void {
        define('ELEMENTOR_PRO_VERSION', '4.0.4');
        ProAliases::register();
        $this->assertFalse(class_exists('ElementorPro\\Coverage'));
    }

    public function test_plugin_shim_is_one_instance_with_a_modules_manager(): void {
        $this->assertSame(ProPluginShim::instance(), ProPluginShim::instance());
        $this->assertNull(ProPluginShim::instance()->modules_manager->get_modules('license'));
    }
}
