<?php
use Modulix\ActivationGuard;
use PHPUnit\Framework\TestCase;

final class ActivationGuardTest extends TestCase {
    public function test_mode(): void {
        $this->assertSame(ActivationGuard::CHECK, ActivationGuard::mode(['elementor/elementor.php', 'elementor-pro/elementor-pro.php']));
        $this->assertSame(ActivationGuard::BLOCKED, ActivationGuard::mode(['akismet/akismet.php']));
        $this->assertSame(ActivationGuard::BLOCKED, ActivationGuard::mode(['elementor-pro/elementor-pro.php']));
        $this->assertSame(ActivationGuard::FULL, ActivationGuard::mode(['elementor/elementor.php', 'modulix/modulix.php']));
    }

    public function test_messages(): void {
        $this->assertStringContainsString('requires Elementor', ActivationGuard::message(ActivationGuard::BLOCKED));
        $this->assertStringContainsString('readiness check', ActivationGuard::message(ActivationGuard::CHECK));
        $this->assertNull(ActivationGuard::message(ActivationGuard::FULL));
    }

    public function test_network_and_site_lists_are_merged(): void {
        $active = ActivationGuard::activePlugins(['elementor/elementor.php'], ['elementor-pro/elementor-pro.php' => 123]);
        $this->assertSame(['elementor/elementor.php', 'elementor-pro/elementor-pro.php'], $active);
    }
}
