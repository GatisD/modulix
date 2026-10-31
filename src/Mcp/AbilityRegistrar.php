<?php
namespace Modulix\Mcp;

final class AbilityRegistrar {
    public static function registerCategory(): void {
        if (!function_exists('wp_register_ability_category')) {
            return;
        }
        wp_register_ability_category('modulix', [
            'label' => 'Modulix',
            'description' => 'Elementor site and document abilities from Modulix.',
        ]);
    }

    public static function registerAbilities(array $enabled): void {
        if (!function_exists('wp_register_ability')) {
            return;
        }
        $access = new WordPressAccessGate();
        $schemas = new ElementorSchemaRegistry();
        $limits = new RequestLimits(new WordPressTransientStore(), new SystemClock());
        $readCatalog = new AbilityCatalog(
            new WordPressReadSource($access),
            $access,
            $limits,
            $schemas
        );
        $writeCatalog = new WriteAbilityCatalog(
            new WordPressWriteSource(),
            $access,
            $limits,
            new ElementTreeValidator($schemas, new WordPressHtmlSanitizer()),
            ChangeLog::wordpress()
        );
        $definitions = array_merge($readCatalog->definitions(), $writeCatalog->definitions());
        if (self::previewEnabled()) {
            $m4Catalog = new M4AbilityCatalog(
                new WordPressM4Source(),
                $access,
                $limits,
                new ElementTreeValidator($schemas, new WordPressHtmlSanitizer()),
                ChangeLog::wordpress()
            );
            $definitions = array_merge($definitions, $m4Catalog->definitions());
        }
        foreach (self::select($definitions, $enabled) as $name => $definition) {
            wp_register_ability($name, $definition);
        }
    }

    public static function select(array $definitions, array $enabled): array {
        return array_intersect_key($definitions, array_fill_keys($enabled, true));
    }

    public static function previewEnabled(?bool $override = null): bool {
        return $override ?? (defined('MODULIX_MCP_PREVIEW') && MODULIX_MCP_PREVIEW === true);
    }
}
