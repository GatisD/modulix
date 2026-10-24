<?php
namespace Modulix\Security;

final class TlsPolicy {
    public static function sslverify(bool $filtered): bool {
        return $filtered;
    }
}
