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

    public function test_menu_cart_fragments_bound_ids_and_enforce_document_access(): void {
        $source = (string) file_get_contents(__DIR__ . '/../modules/woocommerce/module.php');
        $start = strpos($source, 'public function menu_cart_fragments()');
        $end = strpos($source, "\n\tpublic function", $start + 1);
        $handler = substr($source, $start, $end - $start);

        $this->assertStringContainsString('PublicDocumentAccess::normalizeIds', $handler);
        $this->assertStringContainsString('PublicDocumentAccess::allowed', $handler);
        $this->assertStringContainsString('current_user_can( \'edit_post\', $id )', $handler);
        $this->assertStringContainsString('self::MAX_MENU_CART_DOCUMENTS', $handler);
        $this->assertLessThan(strpos($handler, 'editor->set_edit_mode( true )'), strpos($handler, 'current_user_can'));
    }
}
