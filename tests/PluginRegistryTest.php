<?php
use Modulix\Plugin;
use PHPUnit\Framework\TestCase;

final class PluginRegistryTest extends TestCase {
    public function test_phase1_modules_in_boot_order(): void {
        $this->assertSame(['dynamic-tags', 'theme-builder', 'custom-css', 'sticky', 'motion-fx', 'nav-menu', 'floating-buttons', 'frontend', 'no-upsell'], Plugin::registry()->ids());
    }
}
