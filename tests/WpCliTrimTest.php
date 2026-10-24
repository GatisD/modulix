<?php
use PHPUnit\Framework\TestCase;

final class WpCliTrimTest extends TestCase {
    public function test_only_theme_builder_command_is_registered(): void {
        $module = file_get_contents(dirname(__DIR__) . '/modules/wp-cli/module.php');

        $this->assertStringContainsString("add_command( 'elementor-pro theme-builder'", $module);
        $this->assertStringNotContainsString("add_command( 'elementor-pro update'", $module);
        $this->assertFileDoesNotExist(dirname(__DIR__) . '/modules/wp-cli/update.php');
    }
}
