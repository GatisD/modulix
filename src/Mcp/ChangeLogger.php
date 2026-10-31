<?php
namespace Modulix\Mcp;

interface ChangeLogger {
    public function begin(
        int $userId,
        string $ability,
        int $documentId,
        int $size,
        int $elementCount = 0,
        array $counts = []
    ): int;

    public function complete(int $rowId, int $revisionId): bool;

    public function fail(int $rowId, string $status = 'failed', int $revisionId = 0): bool;
}
