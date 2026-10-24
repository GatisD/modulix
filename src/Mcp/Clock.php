<?php
namespace Modulix\Mcp;

interface Clock {
    public function now(): int;
}
