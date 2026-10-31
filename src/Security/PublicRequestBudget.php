<?php
namespace Modulix\Security;

final class PublicRequestBudget {
    /**
     * @param array{start: int, count: int}|null $state
     * @return array{0: bool, 1: array{start: int, count: int}}
     */
    public static function consume(?array $state, int $now, int $limit, int $window): array {
        if ($limit < 1 || $window < 1) {
            return [false, ['start' => $now, 'count' => 0]];
        }

        if (!isset($state['start'], $state['count']) || $state['start'] > $now || $state['start'] + $window <= $now) {
            $state = ['start' => $now, 'count' => 0];
        }

        if ($state['count'] >= $limit) {
            return [false, $state];
        }

        $state['count']++;
        return [true, $state];
    }
}
