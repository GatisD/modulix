<?php
namespace Modulix\Mcp;

final class AbilityRegistrar {
    public static function registerCategory(): void {
        if (!function_exists('wp_register_ability_category')) {
            return;
        }
        wp_register_ability_category('modulix', [
            'label' => 'Modulix',
            'description' => 'Read-only Elementor site and document abilities from Modulix.',
        ]);
    }

    public static function registerAbilities(array $enabled): void {
        if (!function_exists('wp_register_ability')) {
            return;
        }
        $access = new WordPressAccessGate();
        $schemas = new ElementorSchemaRegistry();
        $catalog = new AbilityCatalog(
            new WordPressReadSource($access),
            $access,
            new RequestLimits(new WordPressTransientStore(), new SystemClock()),
            $schemas
        );
        foreach (self::select($catalog->definitions(), $enabled) as $name => $definition) {
            wp_register_ability($name, $definition);
        }
    }

    public static function select(array $definitions, array $enabled): array {
        return array_intersect_key($definitions, array_fill_keys($enabled, true));
    }
}
