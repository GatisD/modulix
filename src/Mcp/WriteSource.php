<?php
namespace Modulix\Mcp;

interface WriteSource {
    public function getDocument(int $postId): ?WritableDocument;
    public function getCreateValidationDocument(string $postType, string $documentType): ?WritableDocument;
    public function createDraft(string $postType, string $documentType, string $title): ?WritableDocument;
    public function snapshotDocument(int $documentId): ?array;
    public function createRevision(int $postId): int;
    public function restoreRevision(int $documentId, int $revisionId, array $snapshot): bool;
    public function deleteDocument(int $documentId): bool;
    public function lockDocument(int $documentId): bool;
    public function unlockDocument(int $documentId): void;
}
