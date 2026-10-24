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
            [['name' => 'site_mailer_promo', 'type' => 'raw_html'], true],
            [['name' => 'site_mailer_promo_2', 'type' => 'raw_html'], true],
            [['name' => 'email_help', 'type' => 'raw_html'], false],
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
        $env = [
            'integrationWidgets' => [['name' => 'ally-accessibility']],
            'library_connect' => [
                'is_connected' => false,
                'current_access_level' => 0,
                'current_access_tier' => 'free',
                'plan_type' => 'free',
                'subscription_plans' => ['essential' => ['promotion_url' => 'https://upgrade.test/']],
            ],
            'other' => 1,
        ];
        $filtered = NoUpsell::without_integration_promos($env);

        $this->assertSame([], $filtered['integrationWidgets']);
        $this->assertTrue($filtered['library_connect']['is_connected']);
        $this->assertSame(PHP_INT_MAX, $filtered['library_connect']['current_access_level']);
        $this->assertSame('agency', $filtered['library_connect']['current_access_tier']);
        $this->assertSame('agency', $filtered['library_connect']['plan_type']);
        $this->assertSame([], $filtered['library_connect']['subscription_plans']);
        $this->assertSame(1, $filtered['other']);
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

    public function test_theme_builder_promotion_route_redirects_to_theme_templates(): void {
        $js = NoUpsell::theme_builder_redirect_script('https://x.test/wp-admin/edit.php?post_type=elementor_library&tabs_group=theme');
        $this->assertStringContainsString('site-editor', $js);
        $this->assertStringContainsString('location.replace("https:\\/\\/x.test\\/wp-admin\\/edit.php?post_type=elementor_library&tabs_group=theme")', $js);
        $this->assertStringStartsWith('<script>', $js);
    }

    public function test_elementor_one_admin_upgrade_buttons_are_switched_off(): void {
        $this->assertStringContainsString('[data-test="header-upgrade-button"]', NoUpsell::ADMIN_CSS);
        $this->assertSame('if (window.editorOneSidebarConfig) { window.editorOneSidebarConfig.hasPro = true; }', NoUpsell::SIDEBAR_JS);
    }
}
