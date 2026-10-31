<?php
namespace Modulix;

/** Boots the local-only Elementor Pro Theme Builder app and exposes its deterministic asset/config contract. */
final class App implements ModuleInterface {
    public const HANDLE = 'modulix-app';
    public const TRANSLATION_DOMAIN = 'modulix';

    public function boot(): void {
        if (ActivationGuard::FULL !== self::mode()) {
            return;
        }

        $app = new \Modulix\Core\App\App();
        Plugin::instance()->app = $app;
        $app->set_menu_url();
    }

    /** @return array<string,array{src:string,dependencies:string[],version:string}> */
    public static function assetDefinitions(string $mode, string $assetsUrl, string $version): array {
        if (ActivationGuard::FULL !== $mode) {
            return [];
        }

        return [
            'style' => [
                'src' => $assetsUrl . 'css/modulix-app.css',
                'dependencies' => ['elementor-app', 'select2'],
                'version' => $version,
            ],
            'script' => [
                'src' => $assetsUrl . 'js/modulix-app.js',
                'dependencies' => [
                    'wp-i18n',
                    'elementor-app-packages',
                    'elementor-common',
                    'elementor-v2-ui',
                    'elementor-v2-icons',
                    'select2',
                    'react-dom',
                ],
                'version' => $version,
            ],
        ];
    }

    /** @return array{baseUrl:string}|array{} */
    public static function baseConfig(string $mode, string $pluginUrl): array {
        return ActivationGuard::FULL === $mode ? ['baseUrl' => $pluginUrl] : [];
    }

    /** @return array{menu_url:string,hasPro:true} */
    public static function coreAppSettings(string $menuUrl): array {
        return [
            'menu_url' => $menuUrl,
            'hasPro' => true,
        ];
    }

    /** Remove Elementor-hosted links from a site-editor config while preserving its local text and media. */
    public static function sanitizeTemplateTypeConfig(array $config): array {
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $config[$key] = self::sanitizeTemplateTypeConfig($value);
                continue;
            }

            if (!is_string($value)) {
                continue;
            }

            $host = parse_url($value, PHP_URL_HOST);
            if (('go.' . 'elementor.' . 'com') === $host) {
                unset($config[$key]);
            }
        }

        return $config;
    }

    /** @return array{urls:array{legacy_view:string}}|array{} */
    public static function siteEditorConfig(string $mode, string $legacyUrl): array {
        return ActivationGuard::FULL === $mode ? ['urls' => ['legacy_view' => $legacyUrl]] : [];
    }

    public static function mode(): string {
        return function_exists('modulix_mode') ? modulix_mode() : ActivationGuard::FULL;
    }
}
