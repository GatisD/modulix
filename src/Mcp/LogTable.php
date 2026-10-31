<?php
namespace Modulix\Mcp;

final class LogTable {
    private const SCHEMA_VERSION = '2';
    private const VERSION_OPTION = 'modulix_mcp_log_version';

    public static function schemaSql(string $prefix, string $charsetCollate): string {
        return "CREATE TABLE {$prefix}modulix_mcp_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            ability varchar(191) NOT NULL,
            document_id bigint(20) unsigned NOT NULL DEFAULT 0,
            status varchar(16) NOT NULL DEFAULT 'done',
            revision_id bigint(20) unsigned NOT NULL DEFAULT 0,
            summary text NOT NULL,
            size bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY user_created (user_id, created_at),
            KEY document_id (document_id)
        ) {$charsetCollate};";
    }

    public static function install(): void {
        global $wpdb;
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        dbDelta(self::schemaSql($wpdb->prefix, $wpdb->get_charset_collate()));
        update_option(self::VERSION_OPTION, self::installedVersion(), false);
    }

    public static function registerUpgradeCheck(): void {
        add_action('plugins_loaded', static function (): void {
            if ((string) get_option(self::VERSION_OPTION, '') !== self::installedVersion()) {
                self::install();
            }
        }, 1);
    }

    private static function installedVersion(): string {
        return self::SCHEMA_VERSION;
    }
}
