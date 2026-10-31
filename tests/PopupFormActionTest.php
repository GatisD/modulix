<?php
use PHPUnit\Framework\TestCase;

final class PopupFormActionTest extends TestCase {
    public function test_popup_registers_its_form_action_after_modules_are_loaded(): void {
        $source = (string) file_get_contents(dirname(__DIR__) . '/modules/popup/module.php');
        $method = explode('public function add_form_action()', $source, 2)[1] ?? '';
        $method = explode('public static function add_popup_to_location', $method, 2)[0] ?? '';

        $this->assertStringContainsString(
            '$this->add_component( \'form-action\', new Form_Action() );',
            $method
        );
    }

    public function test_plugin_fires_the_compatibility_init_hook_after_booting_modules(): void {
        $source = (string) file_get_contents(dirname(__DIR__) . '/modulix.php');
        $boot = '\\Modulix\\Plugin::registry()->bootAll();';
        $init = "do_action('elementor_pro/init');";

        $this->assertStringContainsString($boot, $source);
        $this->assertStringContainsString($init, $source);
        $this->assertLessThan(strpos($source, $init), strpos($source, $boot));
    }
}
