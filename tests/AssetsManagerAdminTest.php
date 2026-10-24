<?php
use Modulix\AssetsManagerAdmin;
use PHPUnit\Framework\TestCase;

final class AssetsManagerAdminTest extends TestCase {
    public function test_assets_load_only_for_custom_font_and_icon_post_types(): void {
        $this->assertTrue(AssetsManagerAdmin::supportsPostType('elementor_font'));
        $this->assertTrue(AssetsManagerAdmin::supportsPostType('elementor_icons'));
        $this->assertFalse(AssetsManagerAdmin::supportsPostType('post'));
        $this->assertFalse(AssetsManagerAdmin::supportsPostType(null));
    }

    public function test_assets_manager_module_registers_the_scoped_admin_enqueue(): void {
        $source = (string) file_get_contents(__DIR__ . '/../modules/assets-manager/module.php');

        $this->assertStringContainsString("add_action( 'admin_enqueue_scripts', [ AssetsManagerAdmin::class, 'enqueue' ] )", $source);
    }
}
