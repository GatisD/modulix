<?php
namespace Modulix\Mcp;

interface ReadSource {
    public function siteOverview(): array;
    public function listDocuments(int $page, int $perPage): array;
    public function getDocument(int $id): ?array;
}
