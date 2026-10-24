<?php
namespace Modulix\Mcp;

final class ArraySchemaRegistry implements SchemaRegistry {
    public function __construct(private array $schemas) {}

    public function schema(string $elementType): ?array {
        return $this->schemas[$elementType] ?? null;
    }
}
