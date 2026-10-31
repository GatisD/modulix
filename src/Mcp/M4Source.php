<?php
namespace Modulix\Mcp;

interface M4Source {
    public function getDocument(int $postId): ?WritableDocument;
    public function activeKit(): ?WritableDocument;
    public function conditions(int $postId): ?array;
    public function conditionRegistry(): array;
    public function conditionTarget(string $object, int $targetId): ?array;
    public function saveConditions(int $postId, array $conditions): bool;
    public function status(int $postId): ?string;
    public function saveStatus(int $postId, string $status): bool;
    public function previewUrl(int $postId): ?string;
    public function readiness(): array;
    public function snapshotDocument(int $documentId): ?array;
    public function createRevision(int $postId): int;
    public function restoreRevision(int $documentId, int $revisionId, array $snapshot): bool;
    public function lockDocument(int $documentId): bool;
    public function unlockDocument(int $documentId): void;
}
