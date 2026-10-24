<?php
namespace Modulix\Mcp;

/**
 * Modulix MCP is a preview until its write abilities and security review are done: nothing registers, no settings
 * are shown and no table is created unless wp-config.php sets `define('MODULIX_MCP_PREVIEW', true);`.
 */
final class Bootstrap {
    public static function previewFrom(mixed $constant): bool {
        return $constant === true;
    }

    public static function preview(): bool {
        return self::previewFrom(defined('MODULIX_MCP_PREVIEW') ? constant('MODULIX_MCP_PREVIEW') : null);
    }

    public static function shouldRegister(array $settings, bool $abilitiesApiAvailable, bool $preview): bool {
        return $preview && $abilitiesApiAvailable && !empty($settings['enabled']);
    }

    public static function register(): void {
        $available = function_exists('wp_register_ability');
        $settings = Settings::load();
        if (!self::shouldRegister($settings, $available, self::preview())) {
            return;
        }
        add_action('wp_abilities_api_categories_init', [AbilityRegistrar::class, 'registerCategory']);
        $enabled = $settings['abilities'];
        add_action('wp_abilities_api_init', static function () use ($enabled): void {
            AbilityRegistrar::registerAbilities($enabled);
        });
    }
}
