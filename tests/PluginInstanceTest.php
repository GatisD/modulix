<?php
use Modulix\Compat\ProPluginShim;
use Modulix\Plugin;
use PHPUnit\Framework\TestCase;

/** Lifted Pro code calls Plugin::instance()->modules_manager->get_modules(); Modulix\Plugin must answer it. */
final class PluginInstanceTest extends TestCase {
    public function test_instance_is_the_pro_plugin_shim(): void {
        $this->assertSame(ProPluginShim::instance(), Plugin::instance());
        $this->assertNull(Plugin::instance()->modules_manager->get_modules('license'));
    }

    public function test_instance_has_the_php_api_member_forms_uploads_use(): void {
        $this->assertTrue(property_exists(Plugin::instance(), 'php_api'));
    }

    public function test_title_is_modulix(): void {
        $this->assertSame('Modulix', Plugin::get_title());
    }
}
