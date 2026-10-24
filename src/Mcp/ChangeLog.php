<?php
namespace Modulix\Mcp;

final class ChangeLog {
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

    public function write(
        int $userId,
        string $ability,
        int $documentId,
        int $revisionId,
        int $size,
        int $elementCount = 0
    ): bool {
        $ability = substr(preg_replace('/[^a-z0-9_\/-]/', '', strtolower($ability)) ?? '', 0, 191);
        $documentId = max(0, $documentId);
        $revisionId = max(0, $revisionId);
        $summary = sprintf(
            'ability=%s document_id=%d revision_id=%d element_count=%d',
            $ability,
            $documentId,
            $revisionId,
            max(0, $elementCount)
        );
        $sql = $this->database->prepare(
            "INSERT INTO {$this->database->prefix}modulix_mcp_log
                (created_at, user_id, ability, document_id, revision_id, summary, size)
                VALUES (%s, %d, %s, %d, %d, %s, %d)",
            ($this->now)(),
            max(0, $userId),
            $ability,
            $documentId,
            $revisionId,
            $summary,
            max(0, $size)
        );
        return false !== $this->database->query($sql);
    }

    public function recent(int $limit = 20): array {
        $limit = min(100, max(1, $limit));
        $sql = $this->database->prepare(
            "SELECT created_at, user_id, ability, document_id, revision_id, summary, size
                FROM {$this->database->prefix}modulix_mcp_log
                ORDER BY id DESC LIMIT %d",
            $limit
        );
        $format = defined('ARRAY_A') ? ARRAY_A : 'ARRAY_A';
        $rows = $this->database->get_results($sql, $format);
        return is_array($rows) ? $rows : [];
    }
}
