<?php
use Modulix\Uninstaller;
use PHPUnit\Framework\TestCase;

final class UninstallTest extends TestCase {
    private static function sqlLikeMatches(string $pattern, string $value): bool {
        $expression = '';
        $length = strlen($pattern);
        for ($index = 0; $index < $length; $index++) {
            if ($pattern[$index] === '\\' && $index + 1 < $length) {
                $expression .= preg_quote($pattern[++$index], '/');
            } elseif ($pattern[$index] === '%') {
                $expression .= '.*';
            } elseif ($pattern[$index] === '_') {
                $expression .= '.';
            } else {
                $expression .= preg_quote($pattern[$index], '/');
            }
        }
        return preg_match('/^' . $expression . '$/D', $value) === 1;
    }

    /** @param string[] $patterns */
    private static function anySqlLikePatternMatches(array $patterns, string $value): bool {
        foreach ($patterns as $pattern) {
            if (self::sqlLikeMatches($pattern, $value)) {
                return true;
            }
        }
        return false;
    }

    public function test_cleanup_contract_contains_only_modulix_owned_data(): void {
        $this->assertSame([
            'modulix_mcp',
            'modulix_mcp_log_version',
            'modulix_keep_elementor_ai',
        ], Uninstaller::OPTIONS);
        $this->assertSame([
            'modulix_mcp_rate_',
            'modulix_readiness',
        ], Uninstaller::CACHE_PREFIXES);
        $this->assertSame(['modulix_mcp_rate', 'modulix_readiness'], Uninstaller::CACHE_GROUPS);

        $contract = json_encode([
            Uninstaller::OPTIONS,
            Uninstaller::CACHE_PREFIXES,
            Uninstaller::CACHE_GROUPS,
            Uninstaller::TABLE_SUFFIX,
        ], JSON_THROW_ON_ERROR);
        foreach (['elementor_pro_', 'elementor_woocommerce', 'woocommerce_', 'posts', 'postmeta', 'submissions'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $contract);
        }
    }

    public function test_uninstall_entry_is_guarded_and_delegates_cleanup(): void {
        $source = (string) file_get_contents(dirname(__DIR__) . '/uninstall.php');

        $this->assertStringContainsString("defined( 'WP_UNINSTALL_PLUGIN' )", $source);
        $this->assertStringContainsString('Uninstaller::cleanupSite', $source);
        $this->assertStringNotContainsString('wp_delete_post', $source);
        $this->assertStringNotContainsString('elementor', $source);
    }

    public function test_cleanup_deletes_exact_options_own_table_and_own_cache_rows(): void {
        $database = new class {
            public string $prefix = 'wp_';
            public string $options = 'wp_options';
            public array $prepared = [];
            public array $queries = [];

            public function esc_like(string $value): string {
                return addcslashes($value, '_%\\');
            }

            public function prepare(string $query, mixed ...$arguments): string {
                $this->prepared[] = [$query, $arguments];
                return 'prepared-' . count($this->prepared);
            }

            public function query(string $query): int {
                $this->queries[] = $query;
                return 1;
            }
        };
        $deletedOptions = [];
        $flushedGroups = [];

        Uninstaller::cleanupSite(
            $database,
            static function (string $option) use (&$deletedOptions): void { $deletedOptions[] = $option; },
            static function (string $group) use (&$flushedGroups): void { $flushedGroups[] = $group; }
        );

        $this->assertSame(Uninstaller::OPTIONS, $deletedOptions);
        $this->assertSame(Uninstaller::CACHE_GROUPS, $flushedGroups);
        $this->assertSame('DROP TABLE IF EXISTS `wp_modulix_mcp_log`', $database->queries[0]);
        $this->assertSame('prepared-1', $database->queries[1]);
        $this->assertCount(4, $database->prepared[0][1]);
        $this->assertSame([
            '\\_transient\\_modulix\\_mcp\\_rate\\_%',
            '\\_transient\\_timeout\\_modulix\\_mcp\\_rate\\_%',
            '\\_transient\\_modulix\\_readiness%',
            '\\_transient\\_timeout\\_modulix\\_readiness%',
        ], $database->prepared[0][1]);
        foreach ([
            '_transient_modulix_mcp_rate_user',
            '_transient_timeout_modulix_mcp_rate_user',
            '_transient_modulix_readiness',
            '_transient_timeout_modulix_readiness_key',
        ] as $ownedOption) {
            $this->assertTrue(self::anySqlLikePatternMatches($database->prepared[0][1], $ownedOption));
        }
        foreach ([
            'XtransientYmodulix_readiness',
            '_transientXmodulix_readiness',
            'Xtransient_timeoutYmodulix_mcp_rate_user',
            '_transient_timeoutXmodulix_mcp_rate_user',
        ] as $nearMissOption) {
            $this->assertFalse(self::anySqlLikePatternMatches($database->prepared[0][1], $nearMissOption));
        }
        foreach ($database->prepared[0][1] as $pattern) {
            $this->assertStringContainsString('modulix', $pattern);
            $this->assertStringNotContainsString('elementor', $pattern);
        }
    }
}
