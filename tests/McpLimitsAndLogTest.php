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

    public function test_write_check_applies_request_batch_and_rate_limits_before_work(): void {
        $clock = new class implements Clock { public function now(): int { return 0; } };
        $store = new class implements TransientStore {
            public int $increments = 0;
            public function increment(string $key, int $expiration): int|false { return ++$this->increments; }
        };
        $limits = new RequestLimits($store, $clock, 60, 1, 30, 2);

        $this->assertSame('request_too_large', $limits->checkWrite(7, ['payload' => str_repeat('x', 40)], 1));
        $this->assertSame('batch_too_large', $limits->checkWrite(7, [], 3));
        $this->assertSame(0, $store->increments);
        $this->assertNull($limits->checkWrite(7, [], 2));
        $this->assertSame('rate_limit', $limits->checkWrite(7, [], 2));
    }

    public function test_log_table_schema_has_the_specified_columns(): void {
        $sql = LogTable::schemaSql('wp_', 'DEFAULT CHARACTER SET utf8mb4');

        $this->assertStringContainsString('wp_modulix_mcp_log', $sql);
        foreach (['created_at', 'user_id', 'ability', 'document_id', 'revision_id', 'status', 'summary', 'size'] as $column) {
            $this->assertStringContainsString($column, $sql);
        }
        $this->assertStringContainsString("status varchar(16) NOT NULL DEFAULT 'done'", $sql);
    }

    public function test_change_log_lifecycle_inserts_pending_then_marks_done_with_the_revision_id(): void {
        $db = new class {
            public string $prefix = 'wp_';
            public int $insert_id = 77;
            public array $prepared = [];
            public array $queries = [];
            public function prepare(string $query, mixed ...$args): string {
                $this->prepared[] = [$query, $args];
                return 'prepared-' . count($this->prepared);
            }
            public function query(string $query): int {
                $this->queries[] = $query;
                return 1;
            }
        };
        $log = new ChangeLog($db, static fn(): string => '2026-10-24 12:00:00');

        $rowId = $log->begin(9, 'modulix/update-elements', 12, 456, 4, ['updated' => 1]);

        $this->assertSame(77, $rowId);
        $this->assertSame('pending', $db->prepared[0][1][4]);
        $this->assertSame(0, $db->prepared[0][1][5]);
        $this->assertTrue($log->complete($rowId, 13));
        $this->assertStringContainsString("status = 'done'", $db->prepared[1][0]);
        $this->assertContains(13, $db->prepared[1][1]);
    }

    public function test_change_log_can_mark_a_pending_row_failed(): void {
        $db = new class {
            public string $prefix = 'wp_';
            public int $insert_id = 8;
            public array $prepared = [];
            public function prepare(string $query, mixed ...$args): string {
                $this->prepared[] = [$query, $args];
                return 'prepared';
            }
            public function query(string $query): int { return 1; }
        };
        $log = new ChangeLog($db);

        $this->assertTrue($log->fail(8));
        $this->assertStringContainsString('SET status = %s, revision_id = %d', $db->prepared[0][0]);
        $this->assertSame(['failed', 0, 8], $db->prepared[0][1]);
    }

    public function test_change_log_can_mark_a_restore_failure_with_its_revision_id(): void {
        $db = new class {
            public string $prefix = 'wp_';
            public array $prepared = [];
            public function prepare(string $query, mixed ...$args): string {
                $this->prepared[] = [$query, $args];
                return 'prepared';
            }
            public function query(string $query): int { return 1; }
        };
        $log = new ChangeLog($db);

        $this->assertTrue($log->fail(8, 'restore_failed', 700));
        $this->assertSame(['restore_failed', 700, 8], $db->prepared[0][1]);
        $this->assertFalse($log->fail(8, 'unsupported', 700));
    }

    public function test_change_log_uses_a_prepared_query_and_a_server_generated_summary(): void {
        $db = new class {
            public string $prefix = 'wp_';
            public int $insert_id = 41;
            public array $prepared = [];
            public array $queries = [];
            public function prepare(string $query, mixed ...$args): string {
                $this->prepared[] = [$query, $args];
                return 'prepared-' . count($this->prepared);
            }
            public function query(string $query): int {
                $this->queries[] = $query;
                return 1;
            }
        };
        $log = new ChangeLog($db, static fn(): string => '2026-10-24 12:00:00');

        $this->assertSame(41, $log->begin(9, 'modulix/update-elements', 12, 456, 4));
        $this->assertSame('prepared-1', $db->queries[0]);
        $this->assertStringContainsString('INSERT INTO wp_modulix_mcp_log', $db->prepared[0][0]);
        $this->assertSame(
            'ability=modulix/update-elements document_id=12 revision_id=0 element_count=4',
            $db->prepared[0][1][6]
        );
        $this->assertTrue($log->complete(41, 13));
    }

    public function test_change_log_records_only_server_generated_change_counts(): void {
        $db = new class {
            public string $prefix = 'wp_';
            public int $insert_id = 42;
            public array $prepared = [];
            public function prepare(string $query, mixed ...$args): string { $this->prepared[] = [$query, $args]; return 'prepared'; }
            public function query(string $query): int { return 1; }
        };

        (new ChangeLog($db, static fn(): string => '2026-10-24 12:00:00'))->begin(
            9,
            'modulix/update-elements',
            12,
            456,
            4,
            ['added' => 1, 'updated' => 2, 'moved' => 1, 'deleted' => 0, 'page_settings' => 0, 'ignored' => 99]
        );

        $this->assertSame(
            'ability=modulix/update-elements document_id=12 revision_id=0 element_count=4'
                . ' added_count=1 updated_count=2 moved_count=1 deleted_count=0 page_settings_count=0',
            $db->prepared[0][1][6]
        );
        $this->assertStringNotContainsString('ignored', $db->prepared[0][1][6]);
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
            public int $insert_id = 43;
            public array $prepared = [];
            public function prepare(string $query, mixed ...$args): string { $this->prepared[] = [$query, $args]; return 'prepared'; }
            public function query(string $query): int { return 1; }
        };

        $request = [
            'password' => 'hunter2',
            'token' => 'abc123',
            'elements' => [['id' => 'one'], ['id' => 'two']],
        ];
        $payload = json_encode($request, JSON_THROW_ON_ERROR);
        (new ChangeLog($db, static fn(): string => '2026-10-24 12:00:00'))
            ->begin(1, 'modulix/update-elements', 12, strlen($payload), count($request['elements']));

        $this->assertSame(
            'ability=modulix/update-elements document_id=12 revision_id=0 element_count=2',
            $db->prepared[0][1][6]
        );
        $this->assertStringNotContainsString('hunter2', $db->prepared[0][1][6]);
        $this->assertStringNotContainsString('abc123', $db->prepared[0][1][6]);
        $this->assertStringNotContainsString('password', $db->prepared[0][1][6]);
        $this->assertStringNotContainsString('token', $db->prepared[0][1][6]);
    }
}
