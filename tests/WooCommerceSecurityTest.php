<?php
use PHPUnit\Framework\TestCase;

final class WooCommerceSecurityTest extends TestCase {
    public function test_checkout_login_verifies_nonce_before_logging_out(): void {
        $source = (string) file_get_contents(__DIR__ . '/../modules/woocommerce/module.php');
        $start = strpos($source, 'public function elementor_woocommerce_checkout_login_user()');
        $end = strpos($source, "\n\tpublic function", $start + 1);
        $handler = substr($source, $start, $end - $start);

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $this->assertLessThan(strpos($handler, 'wp_logout();'), strpos($handler, "wp_verify_nonce("));
    }
}
