<?php
use Modulix\NoUpsell;
use PHPUnit\Framework\TestCase;

final class FakePromotionsModule {
    public function hook(): Closure { return function () { return 'promo'; }; }
    public function staticHook(): Closure { return static function () { return 'promo'; }; }
    public function method(): void {}
}

final class FakeAtomicElement {
    public function __construct(private string $name, private bool $promotion) {}
    public function get_name(): string { return $this->name; }
    public function get_meta_item(string $key): bool { return $key === 'is_pro_promotion' && $this->promotion; }
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
            [['name' => 'page_transitions_teaser', 'type' => 'raw_html'], true],
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

    public function test_editor_notice_bar_reads_as_dismissed(): void {
        $now = 1_800_000_000;
        $this->assertSame($now, NoUpsell::editor_notice_bar_dismissed(false, $now));
        $this->assertSame('_elementor_editor_upgrade_notice_dismissed', NoUpsell::EDITOR_NOTICE_BAR_OPTION);
    }

    public function test_callback_owner_by_namespace(): void {
        $m = new FakePromotionsModule();
        $this->assertTrue(NoUpsell::callback_in_namespace($m->hook(), 'FakePromotions'));
        $this->assertTrue(NoUpsell::callback_in_namespace($m->staticHook(), 'FakePromotions'));
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
        $this->assertSame($env['library_connect'], $filtered['library_connect']);
        $this->assertSame(1, $filtered['other']);
    }

    public function test_legacy_and_new_onboarding_settings_lose_only_promotion_data(): void {
        $legacy = [
            'onboardingVersion' => '1.0.0',
            'urls' => [
                'createNewPage' => '/wp-admin/post-new.php',
                'upgrade' => 'https://' . 'example.' . 'test/go-pro',
                'uploadPro' => '/wp-admin/admin.php?page=elementor-app#/onboarding/uploadAndInstallPro',
            ],
            'pageHeading' => 'Choose the capabilities you need',
            'pageSubheading' => 'Keep building your site',
        ];
        $new = [
            'version' => '2.0.0',
            'steps' => [
                ['id' => 'theme_selection', 'type' => 'single'],
                ['id' => 'site_features', 'type' => 'multiple'],
            ],
            'choices' => ['site_features' => ['forms', 'theme-builder']],
            'urls' => [
                'dashboard' => '/wp-admin/',
                'editor' => '/wp-admin/edit.php?post_type=elementor_library',
                'comparePlans' => 'https://' . 'example.' . 'test/compare',
                'upgradeUrl' => 'https://' . 'example.' . 'test/upgrade',
            ],
        ];

        $legacyFiltered = NoUpsell::without_onboarding_promotions($legacy);
        $newFiltered = NoUpsell::without_onboarding_promotions($new);

        $this->assertSame('/wp-admin/post-new.php', $legacyFiltered['urls']['createNewPage']);
        $this->assertSame('', $legacyFiltered['urls']['upgrade']);
        $this->assertSame('', $legacyFiltered['urls']['uploadPro']);
        $this->assertSame($legacy['pageHeading'], $legacyFiltered['pageHeading']);
        $this->assertSame($legacy['pageSubheading'], $legacyFiltered['pageSubheading']);
        $this->assertSame($new['steps'], $newFiltered['steps']);
        $this->assertSame($new['choices'], $newFiltered['choices']);
        $this->assertSame('/wp-admin/', $newFiltered['urls']['dashboard']);
        $this->assertSame('/wp-admin/edit.php?post_type=elementor_library', $newFiltered['urls']['editor']);
        $this->assertSame('', $newFiltered['urls']['comparePlans']);
        $this->assertSame('', $newFiltered['urls']['upgradeUrl']);
    }

    public function test_editor_category_and_config_promotions_are_removed_without_losing_categories(): void {
        $env = [
            'initial_document' => ['panel' => ['elements_categories' => [
                'pro-elements' => ['title' => 'Pro', 'promotion' => ['url' => 'https://example.test/upgrade']],
                'basic' => ['title' => 'Basic'],
            ]]],
            'promotion' => ['elements' => ['url' => 'https://example.test/upgrade']],
            'promotions' => ['notes' => ['url' => 'https://example.test/upgrade']],
            'atomicWidgetPromotions' => [['name' => 'locked']],
            'v4Promotions' => [['name' => 'locked']],
            'editingPanelStickyPromotion' => ['url' => 'https://example.test/upgrade'],
            'elementPromotionURL' => 'https://example.test/upgrade',
            'dynamicPromotionURL' => 'https://example.test/upgrade',
            'icons' => ['goProURL' => 'https://example.test/upgrade', 'other' => 'kept'],
        ];

        $filtered = NoUpsell::without_editor_promotions($env, false);

        $this->assertSame('Pro', $filtered['initial_document']['panel']['elements_categories']['pro-elements']['title']);
        $this->assertArrayNotHasKey('promotion', $filtered['initial_document']['panel']['elements_categories']['pro-elements']);
        foreach (['promotion', 'promotions', 'atomicWidgetPromotions', 'v4Promotions', 'editingPanelStickyPromotion',
                  'elementPromotionURL', 'dynamicPromotionURL'] as $key) {
            $this->assertArrayNotHasKey($key, $filtered);
        }
        $this->assertArrayNotHasKey('goProURL', $filtered['icons']);
        $this->assertSame('kept', $filtered['icons']['other']);
    }

    public function test_ai_preference_is_forced_off_by_default_and_preserved_when_requested(): void {
        $this->assertSame('0', NoUpsell::elementor_ai_user_option('1', false));
        $this->assertSame('custom-existing-value', NoUpsell::elementor_ai_user_option('custom-existing-value', true));
    }

    public function test_image_optimizer_notices_read_as_dismissed(): void {
        $editor = NoUpsell::dismissed_editor_notices(['existing-notice']);
        $this->assertContains('existing-notice', $editor);
        foreach (['image_optimizer_hint', 'image-optimization-once', 'image-optimization-once-media-modal',
                  'image-optimization-media-modal', 'ally_heading_notice'] as $notice) {
            $this->assertContains($notice, $editor);
        }

        $admin = NoUpsell::dismissed_admin_notices(['existing' => ['is_viewed' => false]]);
        $this->assertFalse($admin['existing']['is_viewed']);
        foreach (['plugin_image_optimization', 'role_manager_promote', 'ally_pages_promotion'] as $notice) {
            $this->assertTrue($admin[$notice]['is_viewed']);
        }
    }

    public function test_home_screen_remote_promotion_payloads_are_removed(): void {
        $data = [
            'sidebar_promotion_variants' => [['title' => 'Upgrade']],
            'top_with_licences' => [
                ['title' => 'Welcome', 'url' => '/safe'],
                ['title' => 'Upgrade now', 'url' => 'https://example.test/go-pro'],
            ],
            'nested' => ['upgrade_url' => 'https://example.test/upgrade', 'safe' => 1],
        ];
        $filtered = NoUpsell::without_home_promotions($data);
        $this->assertArrayNotHasKey('sidebar_promotion_variants', $filtered);
        $this->assertCount(1, $filtered['top_with_licences']);
        $this->assertSame('Welcome', $filtered['top_with_licences'][0]['title']);
        $this->assertSame(['safe' => 1], $filtered['nested']);
    }

    public function test_atomic_promotion_elements_are_identified_without_removing_real_elements(): void {
        $elements = [
            'e-form' => new FakeAtomicElement('e-form', true),
            'e-collection-loop' => new FakeAtomicElement('e-collection-loop', true),
            'e-div-block' => new FakeAtomicElement('e-div-block', false),
        ];
        $this->assertSame(['e-form', 'e-collection-loop'], NoUpsell::atomic_promotion_element_names($elements));
    }

    public function test_template_library_cleanup_covers_both_dialog_variants_and_reads_the_save_context(): void {
        foreach (['#elementor-template-library-save-template-form',
                  '#elementor-template-library-save-template-variant-b'] as $selector) {
            $this->assertStringContainsString($selector, NoUpsell::TEMPLATE_LIBRARY_JS);
        }
        $this->assertStringContainsString('input[name="local"]', NoUpsell::TEMPLATE_LIBRARY_JS);
        $this->assertStringContainsString('input[name="cloud"]', NoUpsell::TEMPLATE_LIBRARY_JS);
        $this->assertStringContainsString("getOption?.('context')", NoUpsell::TEMPLATE_LIBRARY_JS);
        $this->assertStringContainsString("'save' === context", NoUpsell::TEMPLATE_LIBRARY_JS);
    }

    public function test_ai_setting_is_exposed_on_tools_page_with_nonce_and_capability_guard(): void {
        $admin = (string) file_get_contents(__DIR__ . '/../src/Readiness/AdminPage.php');
        $bootstrap = (string) file_get_contents(__DIR__ . '/../modulix.php');

        $this->assertStringContainsString('Keep Elementor AI', $admin);
        $this->assertStringContainsString('wp_nonce_field(self::AI_NONCE)', $admin);
        $this->assertStringContainsString("current_user_can('manage_options')", $admin);
        $this->assertStringContainsString('NoUpsell::registerEarly()', $bootstrap);
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

    public function test_theme_builder_app_is_not_replaced_by_a_redirect(): void {
        $source = (string) file_get_contents(__DIR__ . '/../src/NoUpsell.php');

        $this->assertStringNotContainsString('theme_builder_redirect_script', $source);
        $this->assertStringNotContainsString('location.replace', $source);
        $this->assertStringContainsString("without_onboarding_promotions", $source);
        $this->assertStringNotContainsString("elementor/onboarding/is_elementor_pro_installed", $source);
    }

    public function test_elementor_one_admin_upgrade_buttons_are_switched_off(): void {
        $this->assertStringContainsString('[data-test="header-upgrade-button"]', NoUpsell::ADMIN_CSS);
        $this->assertSame('if (window.editorOneSidebarConfig) { window.editorOneSidebarConfig.hasPro = true; }', NoUpsell::SIDEBAR_JS);
        $this->assertStringContainsString('#elementor-panel-category-custom-widgets', NoUpsell::AI_CSS);
        $this->assertStringContainsString('go.elementor.com/go-pro', NoUpsell::EDITOR_PROMOTION_JS);
        $this->assertStringContainsString('MutationObserver', NoUpsell::EDITOR_PROMOTION_JS);
    }
}
