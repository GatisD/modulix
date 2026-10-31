<?php
namespace Modulix\Mcp;

interface AccessGate {
    public function canReadSite(): bool;
    public function canReadDocument(int $postId): bool;
    public function canEditDocument(int $postId): bool;
    public function canCreateDocument(string $postType): bool;
    public function userId(): int;
}
