<?php
namespace Modulix\Mcp;

final class ChangeLog implements ChangeLogger {
    private object $database;
    private $now;

    public function __construct(object $database, ?callable $now = null) {
        $this->database = $database;
        $this->now = $now ?? static fn(): string => current_time('mysql', true);
    }

    public static function wordpress(): self {
        global $wpdb;
        return new self($wpdb);
    }

    public function begin(
        int $userId,
        string $ability,
        int $documentId,
        int $size,
        int $elementCount = 0,
        array $counts = []
    ): int {
        $ability = $this->sanitizeAbility($ability);
        $documentId = max(0, $documentId);
        $summary = $this->summary($ability, $documentId, 0, $elementCount, $counts);
        $sql = $this->database->prepare(
            "INSERT INTO {$this->database->prefix}modulix_mcp_log
                (created_at, user_id, ability, document_id, status, revision_id, summary, size)
                VALUES (%s, %d, %s, %d, %s, %d, %s, %d)",
            ($this->now)(),
            max(0, $userId),
            $ability,
            $documentId,
            'pending',
            0,
            $summary,
            max(0, $size)
        );
        if (false === $this->database->query($sql)) {
            return 0;
        }
        return isset($this->database->insert_id) && is_numeric($this->database->insert_id)
            ? max(0, (int) $this->database->insert_id)
            : 0;
    }

    public function complete(int $rowId, int $revisionId): bool {
        if ($rowId <= 0 || $revisionId <= 0) {
            return false;
        }
        $sql = $this->database->prepare(
            "UPDATE {$this->database->prefix}modulix_mcp_log
                SET status = 'done', revision_id = %d,
                    summary = REPLACE(summary, %s, %s)
                WHERE id = %d AND status = 'pending'",
            $revisionId,
            'revision_id=0 ',
            'revision_id=' . $revisionId . ' ',
            $rowId
        );
        return (int) $this->database->query($sql) === 1;
    }

    public function fail(int $rowId, string $status = 'failed', int $revisionId = 0): bool {
        if ($rowId <= 0 || !in_array($status, ['failed', 'restore_failed'], true)) {
            return false;
        }
        $sql = $this->database->prepare(
            "UPDATE {$this->database->prefix}modulix_mcp_log
                SET status = %s, revision_id = %d
                WHERE id = %d AND status IN ('pending', 'done')",
            $status,
            max(0, $revisionId),
            $rowId
        );
        return (int) $this->database->query($sql) === 1;
    }

    private function sanitizeAbility(string $ability): string {
        return substr(preg_replace('/[^a-z0-9_\/-]/', '', strtolower($ability)) ?? '', 0, 191);
    }

    private function summary(
        string $ability,
        int $documentId,
        int $revisionId,
        int $elementCount,
        array $counts
    ): string {
        $summary = sprintf(
            'ability=%s document_id=%d revision_id=%d element_count=%d',
            $ability,
            $documentId,
            max(0, $revisionId),
            max(0, $elementCount)
        );
        foreach ([
            'added' => 'added_count',
            'updated' => 'updated_count',
            'moved' => 'moved_count',
            'deleted' => 'deleted_count',
            'page_settings' => 'page_settings_count',
        ] as $key => $label) {
            if (array_key_exists($key, $counts)) {
                $summary .= sprintf(' %s=%d', $label, max(0, (int) $counts[$key]));
            }
        }
        return $summary;
    }

    public function recent(int $limit = 20): array {
        $limit = min(100, max(1, $limit));
        $sql = $this->database->prepare(
            "SELECT created_at, user_id, ability, document_id, status, revision_id, summary, size
                FROM {$this->database->prefix}modulix_mcp_log
                ORDER BY id DESC LIMIT %d",
            $limit
        );
        $format = defined('ARRAY_A') ? ARRAY_A : 'ARRAY_A';
        $rows = $this->database->get_results($sql, $format);
        return is_array($rows) ? $rows : [];
    }
}
