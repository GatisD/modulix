<?php
namespace Modulix\Mcp;

interface TransientStore {
    public function increment(string $key, int $expiration): int|false;
}
