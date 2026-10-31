<?php
namespace Modulix;

final class I18n {
    public const PHP_DOMAIN = 'modulix';
    public const JS_DOMAINS = ['elementor', 'elementor-pro', 'modulix'];
    public const BUNDLE_DOMAINS = [
        'modulix-admin-assets' => 'elementor-pro',
        'modulix-app' => 'modulix',
        'modulix-editor' => 'modulix',
        'modulix-editor-pro' => 'elementor-pro',
        'custom-code-metabox' => 'elementor-pro',
        'e-display-conditions' => 'modulix',
        'form-submission-admin' => 'elementor-pro',
    ];

    public static function setScriptTranslations(string $handle): bool {
        $domain = self::BUNDLE_DOMAINS[$handle] ?? null;
        if ($domain === null) {
            return false;
        }

        return \wp_set_script_translations($handle, $domain, MODULIX_PATH . 'languages');
    }
}
