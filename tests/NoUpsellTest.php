<?php
use Modulix\NoUpsell;
use PHPUnit\Framework\TestCase;

final class FakePromotionsModule {
    public function hook(): Closure { return function () { return 'promo'; }; }
    public function method(): void {}
}

final class NoUpsellTest extends TestCase {
    public function test_strip_pro_widgets_keeps_other_keys(): void {
        $info = ['timestamp' => 1, 'upgrade_notice' => ['v' => '4'], 'pro_widgets' => [['name' => 'posts']]];
        $this->assertSame(['timestamp' => 1, 'upgrade_notice' => ['v' => '4'], 'pro_widgets' => []], NoUpsell::strip_pro_widgets($info));
        $this->assertFalse(NoUpsell::strip_pro_widgets(false));
    }

    /** @dataProvider controls */
    public function test_teaser_controls(array $control, bool $expected): void {
        $this->assertSame($expected, NoUpsell::is_teaser_control($control));
    }
    public static function controls(): array {
        return [
            [['name' => 'section_custom_attributes_pro', 'type' => 'section'], true],
            [['name' => 'custom_attributes_pro', 'type' => 'raw_html'], true],
            [['name' => 'sticky_pro', 'type' => 'raw_html'], true],
            [['name' => 'display_conditions_pro', 'type' => 'promotion_control'], true],
            [['name' => 'motion_effects_promotion_divider', 'type' => 'divider'], true],
            [['name' => 'section_effects', 'type' => 'section'], false],
            [['name' => 'custom_css', 'type' => 'code'], false],
            [['name' => 'image_pro', 'type' => 'media'], false],
            [['name' => 'divider', 'type' => 'divider'], false],
        ];
    }

    public function test_hello_promotion_links_lose_go_pro(): void {
        $links = [['type' => 'go-pro', 'upgrade' => true], ['type' => 'hello-plus'], ['type' => 'x', 'upgrade' => true]];
        $this->assertSame([['type' => 'hello-plus']], NoUpsell::without_upsell_links($links));
    }

    public function test_hello_welcome_upsell_removed_other_welcome_kept(): void {
        $upsell = ['welcome' => ['title' => 'Go Pro', 'buttons' => [['link' => 'https://go.elementor.com/hello-upgrade-epro/']]], 'other' => 1];
        $this->assertSame(['other' => 1], NoUpsell::hello_admin_config($upsell));
        $install = ['welcome' => ['buttons' => [['link' => 'install']]]];
        $this->assertSame($install, NoUpsell::hello_admin_config($install));
    }

    public function test_hello_banner_reads_as_dismissed(): void {
        $this->assertSame([true], NoUpsell::hello_banner_dismissed(null, 1, NoUpsell::HELLO_BANNER_META, true));
        $this->assertNull(NoUpsell::hello_banner_dismissed(null, 1, 'other_key', true));
    }

    public function test_callback_owner_by_namespace(): void {
        $m = new FakePromotionsModule();
        $this->assertTrue(NoUpsell::callback_in_namespace($m->hook(), 'FakePromotions'));
        $this->assertTrue(NoUpsell::callback_in_namespace([$m, 'method'], 'FakePromotions'));
        $this->assertFalse(NoUpsell::callback_in_namespace(function () {}, 'FakePromotions'));
        $this->assertFalse(NoUpsell::callback_in_namespace('strlen', 'FakePromotions'));
    }

    public function test_integration_widget_promos_removed(): void {
        $env = ['integrationWidgets' => [['name' => 'ally-accessibility']], 'other' => 1];
        $this->assertSame(['integrationWidgets' => [], 'other' => 1], NoUpsell::without_integration_promos($env));
        $this->assertSame(['other' => 1], NoUpsell::without_integration_promos(['other' => 1]));
    }

    public function test_submenu_item_removed_under_any_parent(): void {
        $submenu = ['elementor-home' => [['Home', 'manage_options', 'elementor-home'], ['Upgrade', 'manage_options', 'elementor-one-upgrade']],
                    'tools.php' => [['Tools', 'read', 'tools.php']]];
        $out = NoUpsell::remove_submenu_slug($submenu, 'elementor-one-upgrade');
        $this->assertSame([['Home', 'manage_options', 'elementor-home']], $out['elementor-home']);
        $this->assertSame($submenu['tools.php'], $out['tools.php']);
    }

    public function test_no_upsell_is_registered_last(): void {
        $this->assertSame('no-upsell', array_slice(\Modulix\Plugin::registry()->ids(), -1)[0]);
    }
}
