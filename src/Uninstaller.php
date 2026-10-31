<?php
namespace Modulix;

final class Uninstaller {
    public const OPTIONS = [
        'modulix_mcp',
        'modulix_mcp_log_version',
        'modulix_keep_elementor_ai',
    ];
    public const CACHE_PREFIXES = [
        'modulix_mcp_rate_',
        'modulix_readiness',
    ];
    public const CACHE_GROUPS = [
        'modulix_mcp_rate',
        'modulix_readiness',
    ];
    public const TABLE_SUFFIX = 'modulix_mcp_log';

    public static function cleanupSite(
        object $database,
        ?callable $deleteOption = null,
        ?callable $flushCacheGroup = null
    ): void {
        $deleteOption ??= static function (string $option): void {
            \delete_option($option);
        };
        $flushCacheGroup ??= static function (string $group): void {
            if (function_exists('wp_cache_flush_group')) {
                \wp_cache_flush_group($group);
            }
        };

        foreach (self::OPTIONS as $option) {
            $deleteOption($option);
        }

        $table = (string) ($database->prefix ?? '') . self::TABLE_SUFFIX;
        if (preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            $database->query("DROP TABLE IF EXISTS `{$table}`");
        }

        self::deleteCacheRows($database);

        foreach (self::CACHE_GROUPS as $group) {
            $flushCacheGroup($group);
        }
    }

    private static function deleteCacheRows(object $database): void {
        $optionsTable = (string) ($database->options ?? '');
        if (!preg_match('/^[A-Za-z0-9_]+$/', $optionsTable)
            || !method_exists($database, 'prepare')
            || !method_exists($database, 'query')) {
            return;
        }

        $conditions = [];
        $patterns = [];
        foreach (self::CACHE_PREFIXES as $prefix) {
            foreach (['_transient_', '_transient_timeout_'] as $optionPrefix) {
                $fixedPrefix = $optionPrefix . $prefix;
                $escaped = method_exists($database, 'esc_like')
                    ? $database->esc_like($fixedPrefix)
                    : addcslashes($fixedPrefix, '_%\\');
                $conditions[] = 'option_name LIKE %s';
                $patterns[] = $escaped . '%';
            }
        }

        $sql = "DELETE FROM {$optionsTable} WHERE " . implode(' OR ', $conditions);
        $database->query($database->prepare($sql, ...$patterns));
    }
}
