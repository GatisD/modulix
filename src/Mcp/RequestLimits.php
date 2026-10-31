<?php
namespace Modulix\Mcp;

final class RequestLimits implements RequestGate {
    public const DEFAULT_READS_PER_MINUTE = 60;
    public const DEFAULT_WRITES_PER_MINUTE = 20;
    public const DEFAULT_MAX_REQUEST_BYTES = 1048576;
    public const DEFAULT_MAX_BATCH_ELEMENTS = 200;

    public function __construct(
        private TransientStore $store,
        private Clock $clock,
        private int $readsPerMinute = self::DEFAULT_READS_PER_MINUTE,
        private int $writesPerMinute = self::DEFAULT_WRITES_PER_MINUTE,
        private int $maxRequestBytes = self::DEFAULT_MAX_REQUEST_BYTES,
        private int $maxBatchElements = self::DEFAULT_MAX_BATCH_ELEMENTS
    ) {}

    public function checkRead(int $userId, array $input): ?string {
        $encoded = json_encode($input);
        if ($encoded === false) {
            return 'invalid_request';
        }
        return $this->checkRequestSize($encoded) ?? $this->checkRate($userId, false);
    }

    public function checkWrite(int $userId, array $input, int $batchSize): ?string {
        $encoded = json_encode($input);
        if ($encoded === false) {
            return 'invalid_request';
        }
        return $this->checkRequestSize($encoded)
            ?? ($batchSize > $this->maxBatchElements ? 'batch_too_large' : null)
            ?? $this->checkRate($userId, true);
    }

    public function checkRate(int $userId, bool $write): ?string {
        $limit = $write ? $this->writesPerMinute : $this->readsPerMinute;
        $window = intdiv($this->clock->now(), 60);
        $kind = $write ? 'write' : 'read';
        $key = 'modulix_mcp_rate_' . $kind . '_' . $userId . '_' . $window;
        $count = $this->store->increment($key, 65);
        if ($count === false || $count > $limit) {
            return 'rate_limit';
        }
        return null;
    }

    public function checkRequestSize(string $payload): ?string {
        return strlen($payload) > $this->maxRequestBytes ? 'request_too_large' : null;
    }

    public function checkBatchSize(array $elements): ?string {
        return count($elements) > $this->maxBatchElements ? 'batch_too_large' : null;
    }
}
