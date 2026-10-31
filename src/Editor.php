<?php
namespace Modulix;

/** Elementor Pro's entry-trimmed editor runtime plus the earlier Query control fallback. */
final class Editor implements ModuleInterface {
    public const HANDLE = 'modulix-editor';
    public const PRO_HANDLE = 'modulix-editor-pro';
    public const PRO_DEPENDENCIES = [
        'backbone-marionette',
        'elementor-common',
        'elementor-editor-modules',
        'elementor-editor-document',
    ];

    public function boot(): void {
        add_action('elementor/editor/after_enqueue_styles', [self::class, 'enqueueStyles']);
        add_action('elementor/editor/before_enqueue_scripts', [self::class, 'enqueueProScripts']);
        add_action('elementor/editor/after_enqueue_scripts', [self::class, 'enqueueLegacyScripts']);
    }

    /** @return array<string,string[]> */
    public static function scriptHooks(): array {
        return [
            'elementor/editor/before_enqueue_scripts' => [self::PRO_HANDLE],
            'elementor/editor/after_enqueue_scripts' => [self::HANDLE],
        ];
    }

    /** @return array<string,array{src:string,dependencies:string[],version:string}> */
    public static function scriptDefinitions(string $mode, string $assetsUrl, string $version): array {
        if (ActivationGuard::FULL !== $mode) {
            return [];
        }

        return [
            self::PRO_HANDLE => [
                'src' => $assetsUrl . 'js/modulix-editor-pro.js',
                'dependencies' => self::PRO_DEPENDENCIES,
                'version' => $version,
            ],
            self::HANDLE => [
                'src' => $assetsUrl . 'js/modulix-editor.js',
                'dependencies' => [self::PRO_HANDLE, 'elementor-editor', 'wp-i18n'],
                'version' => $version,
            ],
        ];
    }

    public static function enqueueStyles(): void {
        if (ActivationGuard::FULL !== self::mode()) {
            return;
        }

        $suffix = \Elementor\Utils::is_script_debug() ? '' : '.min';
        wp_enqueue_style(
            self::PRO_HANDLE,
            MODULIX_ASSETS_URL . "css/editor{$suffix}.css",
            ['elementor-editor'],
            MODULIX_VERSION
        );
    }

    public static function enqueueProScripts(): void {
        if (ActivationGuard::FULL !== self::mode()) {
            return;
        }

        $script = self::scriptDefinitions(self::mode(), MODULIX_ASSETS_URL, MODULIX_VERSION)[self::PRO_HANDLE];
        wp_enqueue_script(self::PRO_HANDLE, $script['src'], $script['dependencies'], $script['version'], true);
        I18n::setScriptTranslations(self::PRO_HANDLE);
        \Elementor\Utils::print_js_config(
            self::PRO_HANDLE,
            'elementorProEditorConfig',
            self::config(MODULIX_VERSION, MODULIX_MODULES_URL)
        );
    }

    public static function enqueueLegacyScripts(): void {
        if (ActivationGuard::FULL !== self::mode()) {
            return;
        }

        $script = self::scriptDefinitions(self::mode(), MODULIX_ASSETS_URL, MODULIX_VERSION)[self::HANDLE];
        wp_enqueue_script(self::HANDLE, $script['src'], $script['dependencies'], $script['version'], true);
        I18n::setScriptTranslations(self::HANDLE);
    }

    /**
     * @param null|callable(string,array):array $filter
     * @return array<string,mixed>
     */
    public static function config(string $version, string $modulesUrl, ?callable $filter = null): array {
        $settings = [
            'version' => $version,
            'isActive' => true,
            'urls' => [
                'modules' => $modulesUrl,
                'connect' => '',
            ],
            'i18n' => [],
            'forms' => [],
            'widget_templates' => [],
            'should_show_promotion' => false,
            'loopFilter' => ['mainQueryPostType' => 'post'],
            'shareButtonsNetworks' => [],
            'woocommerce' => ['woocommercePages' => []],
            'promotionWidgets' => [],
            'elementPromotionURL' => '',
            'dynamicPromotionURL' => '',
        ];

        $settings = $filter !== null
            ? $filter('elementor_pro/editor/localize_settings', $settings)
            : apply_filters('elementor_pro/editor/localize_settings', $settings);

        // Modulix always exposes the licensed paths and never exposes Connect or upgrade entry points.
        $settings['isActive'] = true;
        $settings['should_show_promotion'] = false;
        $settings['promotionWidgets'] = [];
        $settings['elementPromotionURL'] = '';
        $settings['dynamicPromotionURL'] = '';
        $settings['urls']['connect'] = '';

        return $settings;
    }

    private static function mode(): string {
        return function_exists('modulix_mode') ? modulix_mode() : ActivationGuard::FULL;
    }
}
