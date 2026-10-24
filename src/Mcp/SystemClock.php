<?php
namespace Modulix\Mcp;

final class SystemClock implements Clock {
    public function now(): int {
        return time();
    }
}
