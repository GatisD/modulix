<?php
namespace Modulix\Mcp;

interface M4AccessGate {
    public function canManageOptions(): bool;
    public function canEditDocument(int $postId): bool;
    public function canPublishDocument(int $postId, string $status): bool;
    public function canPreviewDocument(int $postId): bool;
    public function userId(): int;
}
