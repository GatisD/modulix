<?php
namespace Modulix\Mcp;

interface WritableDocument {
    public function id(): int;
    public function postType(): string;
    public function elements(): array;
    public function pageSettings(): array;
    public function pageSettingsControls(): array;
    public function canReadAttachment(int $attachmentId): bool;
    public function save(array $data): bool;
}
