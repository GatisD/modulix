<?php
use Modulix\Coverage;
use Modulix\Readiness\ProCatalog;
use PHPUnit\Framework\TestCase;

final class ProCatalogTest extends TestCase {
    public function test_everything_modulix_covers_is_a_pro_name(): void {
        $this->assertSame([], array_values(array_diff(Coverage::WIDGETS, ProCatalog::WIDGETS)));
        $this->assertSame([], array_values(array_diff(Coverage::TAGS, ProCatalog::TAGS)));
        $this->assertSame([], array_values(array_diff(Coverage::DOCUMENT_TYPES, ProCatalog::DOCUMENT_TYPES)));
        $this->assertSame([], array_values(array_diff(Coverage::FEATURES, ProCatalog::FEATURES)));
    }

    public function test_catalog_knows_the_big_gaps(): void {
        foreach (['form', 'loop-grid', 'media-carousel', 'slides', 'woocommerce-products'] as $w) {
            $this->assertContains($w, ProCatalog::WIDGETS);
        }
        $this->assertContains('popup', ProCatalog::TAGS);
        $this->assertContains('code_snippet', ProCatalog::DOCUMENT_TYPES);
    }
}
