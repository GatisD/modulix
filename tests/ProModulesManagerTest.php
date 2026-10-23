<?php
use Modulix\Compat\ProModulesManager;
use Modulix\Plugin;
use PHPUnit\Framework\TestCase;

final class ProModulesManagerTest extends TestCase {
    public function test_names_match_the_lifted_modules_in_the_registry(): void {
        $ids = array_values(array_diff(Plugin::registry()->ids(), ['editor', 'frontend', 'no-upsell']));
        $this->assertSame($ids, array_keys(ProModulesManager::MODULES));
    }

    public function test_unknown_or_unloadable_modules_are_null(): void {
        $m = new ProModulesManager();
        $this->assertNull($m->get_modules('forms'));
        // Lifted module classes need Elementor, which unit tests do not load, so they are not loadable here.
        $this->assertNull($m->get_modules('theme-builder'));
        $this->assertSame([], $m->get_modules());
    }
}
