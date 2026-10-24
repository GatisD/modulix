<?php
namespace Modulix\Mcp;

interface RequestGate {
    public function checkRead(int $userId, array $input): ?string;
}
