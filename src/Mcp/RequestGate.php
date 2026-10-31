<?php
namespace Modulix\Mcp;

interface RequestGate {
    public function checkRead(int $userId, array $input): ?string;
    public function checkWrite(int $userId, array $input, int $batchSize): ?string;
}
