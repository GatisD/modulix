<?php
namespace Modulix\Mcp;

interface SchemaRegistry {
    public function schema(string $elementType): ?array;
}
