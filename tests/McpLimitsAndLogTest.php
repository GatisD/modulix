<?php
use Modulix\Mcp\ChangeLog;
use Modulix\Mcp\Clock;
use Modulix\Mcp\LogTable;
use Modulix\Mcp\RequestLimits;
use Modulix\Mcp\TransientStore;
use Modulix\Mcp\WordPressTransientStore;
use PHPUnit\Framework\TestCase;

final class McpLimitsAndLogTest extends TestCase {
    public function test_default_limits_match_the_specification(): void {
        $this->assertSame(60, RequestLimits::DEFAULT_READS_PER_MINUTE);
        $this->assertSame(20, RequestLimits::DEFAULT_WRITES_PER_MINUTE);
        $this->assertSame(1048576, RequestLimits::DEFAULT_MAX_REQUEST_BYTES);
        $this->assertSame(200, RequestLimits::DEFAULT_MAX_BATCH_ELEMENTS);
    }

    public function test_read_and_write_rates_have_separate_per_user_limits(): void {
        $clock = new class implements Clock {
            public function now(): int { return 120; }
        };
        $store = new class implements TransientStore {
            public array $values = [];
            public array $keys = [];
            public array $counts = [];
            public function increment(string $key, int $expiration): int|false {
                $this->keys[] = $key;
                $count = $this->values[$key] = ($this->values[$key] ?? 0) + 1;
                $this->counts[] = $count;
                return $count;
            }
        };
        $limits = new RequestLimits($store, $clock, 2, 1, 12, 2);

        $this->assertNull($limits->checkRate(7, false));
        $this->assertNull($limits->checkRate(7, false));
        $this->assertSame('rate_limit', $limits->checkRate(7, false));
        $this->assertNull($limits->checkRate(7, true));
        $this->assertSame('rate_limit', $limits->checkRate(7, true));
        $this->assertNull($limits->checkRate(8, false));
        $this->assertSame($store->keys[0], $store->keys[1]);
        $this->assertSame([1, 2], array_slice($store->counts, 0, 2));
    }

    public function test_rate_limit_fails_closed_when_atomic_store_fails(): void {
        $clock = new class implements Clock { public function now(): int { return 120; } };
        $store = new class implements TransientStore {
            public function increment(string $key, int $expiration): int|false { return false; }
        };
        $limits = new RequestLimits($store, $clock, 2, 1, 12, 2);

        $this->assertSame('rate_limit', $limits->checkRate(7, false));
        $this->assertSame('rate_limit', $limits->checkRate(7, false));
    }

    public function test_wordpress_store_uses_a_prepared_atomic_sql_increment_without_persistent_cache(): void {
        $this->assertFalse(function_exists('wp_using_ext_object_cache'));
        $previousDatabase = $GLOBALS['wpdb'] ?? null;
        $hadDatabase = array_key_exists('wpdb', $GLOBALS);
        $database = new class {
            public string $options = 'wp_options';
            public int $insert_id = 0;
            public int $counterQueries = 0;
            public array $prepared = [];
            public function prepare(string $query, mixed ...$args): string {
                $this->prepared[] = [$query, $args];
                return $query;
            }
            public function query(string $query): int {
                if (str_contains($query, 'LAST_INSERT_ID')) {
                    $this->counterQueries++;
                    if ($this->counterQueries === 1) {
                        return 1;
                    }
                    $this->insert_id = $this->counterQueries;
                    return 2;
                }
                return 1;
            }
        };
        $GLOBALS['wpdb'] = $database;

        try {
            $store = new WordPressTransientStore();
            $this->assertSame(1, $store->increment('same_window', 65));
            $this->assertSame(2, $store->increment('same_window', 65));
        } finally {
            if ($hadDatabase) {
                $GLOBALS['wpdb'] = $previousDatabase;
            } else {
                unset($GLOBALS['wpdb']);
            }
        }

        $this->assertCount(4, $database->prepared);
        $this->assertStringContainsString('ON DUPLICATE KEY UPDATE', $database->prepared[0][0]);
        $this->assertStringContainsString('LAST_INSERT_ID', $database->prepared[0][0]);
        $this->assertSame('_transient_same_window', $database->prepared[0][1][0]);
    }

    public function test_request_and_batch_size_limits_are_exact(): void {
        $clock = new class implements Clock { public function now(): int { return 0; } };
        $store = new class implements TransientStore {
            public function increment(string $key, int $expiration): int|false { return 1; }
        };
        $limits = new RequestLimits($store, $clock, 60, 20, 10, 2);

        $this->assertNull($limits->checkRequestSize('1234567890'));
        $this->assertSame('request_too_large', $limits->checkRequestSize('12345678901'));
        $this->assertNull($limits->checkBatchSize([['id' => 1], ['id' => 2]]));
        $this->assertSame('batch_too_large', $limits->checkBatchSize([['id' => 1], ['id' => 2], ['id' => 3]]));
    }

    public function test_log_table_schema_has_the_specified_columns(): void {
        $sql = LogTable::schemaSql('wp_', 'DEFAULT CHARACTER SET utf8mb4');

        $this->assertStringContainsString('wp_modulix_mcp_log', $sql);
        foreach (['created_at', 'user_id', 'ability', 'document_id', 'revision_id', 'summary', 'size'] as $column) {
            $this->assertStringContainsString($column, $sql);
        }
    }

    public function test_change_log_uses_a_prepared_query_and_a_server_generated_summary(): void {
        $db = new class {
            public string $prefix = 'wp_';
            public array $prepared = [];
            public string $query = '';
            public function prepare(string $query, mixed ...$args): string {
                $this->prepared = [$query, $args];
                return 'prepared-query';
            }
            public function query(string $query): int {
                $this->query = $query;
                return 1;
            }
        };
        $log = new ChangeLog($db, static fn(): string => '2026-10-24 12:00:00');

        $this->assertTrue($log->write(9, 'modulix/update-elements', 12, 13, 456, 4));
        $this->assertSame('prepared-query', $db->query);
        $this->assertStringContainsString('INSERT INTO wp_modulix_mcp_log', $db->prepared[0]);
        $this->assertSame(
            'ability=modulix/update-elements document_id=12 revision_id=13 element_count=4',
            $db->prepared[1][5]
        );
    }

    public function test_recent_change_log_rows_use_a_prepared_query(): void {
        $db = new class {
            public string $prefix = 'wp_';
            public array $prepared = [];
            public function prepare(string $query, mixed ...$args): string {
                $this->prepared = [$query, $args];
                return 'prepared-select';
            }
            public function get_results(string $query, string $format): array {
                return $query === 'prepared-select' ? [['ability' => 'modulix/test']] : [];
            }
        };
        $log = new ChangeLog($db);

        $this->assertSame([['ability' => 'modulix/test']], $log->recent(25));
        $this->assertStringContainsString('FROM wp_modulix_mcp_log', $db->prepared[0]);
        $this->assertSame([25], $db->prepared[1]);
    }

    public function test_request_credentials_cannot_enter_the_change_log_summary(): void {
        $db = new class {
            public string $prefix = 'wp_';
            public array $args = [];
            public function prepare(string $query, mixed ...$args): string { $this->args = $args; return 'prepared'; }
            public function query(string $query): int { return 1; }
        };

        $request = [
            'password' => 'hunter2',
            'token' => 'abc123',
            'elements' => [['id' => 'one'], ['id' => 'two']],
        ];
        $payload = json_encode($request, JSON_THROW_ON_ERROR);
        (new ChangeLog($db, static fn(): string => '2026-10-24 12:00:00'))
            ->write(1, 'modulix/update-elements', 12, 13, strlen($payload), count($request['elements']));

        $this->assertSame(
            'ability=modulix/update-elements document_id=12 revision_id=13 element_count=2',
            $db->args[5]
        );
        $this->assertStringNotContainsString('hunter2', $db->args[5]);
        $this->assertStringNotContainsString('abc123', $db->args[5]);
        $this->assertStringNotContainsString('password', $db->args[5]);
        $this->assertStringNotContainsString('token', $db->args[5]);
    }
}
