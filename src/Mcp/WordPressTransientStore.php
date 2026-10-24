<?php
namespace Modulix\Mcp;

final class WordPressTransientStore implements TransientStore {
    private const CACHE_GROUP = 'modulix_mcp_rate';

    public function increment(string $key, int $expiration): int|false {
        if (function_exists('wp_using_ext_object_cache') && \wp_using_ext_object_cache()) {
            if (\wp_cache_add($key, 1, self::CACHE_GROUP, $expiration)) {
                return 1;
            }
            $count = \wp_cache_incr($key, 1, self::CACHE_GROUP);
            return is_int($count) && $count > 0 ? $count : false;
        }

        global $wpdb;
        if (!is_object($wpdb) || !isset($wpdb->options) || !method_exists($wpdb, 'prepare') || !method_exists($wpdb, 'query')) {
            return false;
        }
        $counterSql = $wpdb->prepare(
            "INSERT INTO {$wpdb->options} (option_name, option_value, autoload)
                VALUES (%s, %s, %s)
                ON DUPLICATE KEY UPDATE option_value = LAST_INSERT_ID(CAST(option_value AS UNSIGNED) + 1)",
            '_transient_' . $key,
            '1',
            'no'
        );
        $result = $wpdb->query($counterSql);
        if ($result === false) {
            return false;
        }
        $count = $result === 1 ? 1 : (int) ($wpdb->insert_id ?? 0);
        if ($count < 1) {
            return false;
        }

        $timeoutSql = $wpdb->prepare(
            "INSERT INTO {$wpdb->options} (option_name, option_value, autoload)
                VALUES (%s, %s, %s)
                ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)",
            '_transient_timeout_' . $key,
            (string) (time() + max(1, $expiration)),
            'no'
        );
        if ($wpdb->query($timeoutSql) === false) {
            return false;
        }
        return $count;
    }
}
