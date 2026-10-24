<?php
use Modulix\Security\TlsPolicy;
use PHPUnit\Framework\TestCase;

final class TlsPolicyTest extends TestCase {
    public function test_keeps_tls_verification_enabled_by_default(): void {
        $this->assertTrue(TlsPolicy::sslverify(true));
    }

    public function test_allows_an_explicit_filtered_opt_out(): void {
        $this->assertFalse(TlsPolicy::sslverify(false));
    }
}
