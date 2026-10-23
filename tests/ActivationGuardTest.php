<?php
use Modulix\ActivationGuard;
use PHPUnit\Framework\TestCase;

final class ActivationGuardTest extends TestCase {
    public function test_blocks_when_pro_active(): void {
        $msg = ActivationGuard::check(['elementor/elementor.php', 'elementor-pro/elementor-pro.php']);
        $this->assertStringContainsString('Elementor Pro', $msg);
    }
    public function test_blocks_when_core_missing(): void {
        $msg = ActivationGuard::check(['akismet/akismet.php']);
        $this->assertStringContainsString('requires Elementor', $msg);
    }
    public function test_allows_core_without_pro(): void {
        $this->assertNull(ActivationGuard::check(['elementor/elementor.php', 'modulix/modulix.php']));
    }
    public function test_network_and_site_lists_are_merged(): void {
        $active = ActivationGuard::activePlugins(['elementor/elementor.php'], ['elementor-pro/elementor-pro.php' => 123]);
        $this->assertSame(['elementor/elementor.php', 'elementor-pro/elementor-pro.php'], $active);
    }
}
