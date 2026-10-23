<?php
use Modulix\ModuleInterface;
use Modulix\ModuleRegistry;
use PHPUnit\Framework\TestCase;

final class FakeModule implements ModuleInterface {
    public static array $booted = [];
    public function __construct(private string $id) {}
    public function boot(): void { self::$booted[] = $this->id; }
}

final class ModuleRegistryTest extends TestCase {
    protected function setUp(): void { FakeModule::$booted = []; }

    public function test_boots_in_insertion_order(): void {
        $r = new ModuleRegistry();
        $r->add('theme-builder', fn() => new FakeModule('theme-builder'));
        $r->add('nav-menu', fn() => new FakeModule('nav-menu'));
        $this->assertSame(['theme-builder', 'nav-menu'], $r->bootAll());
        $this->assertSame(['theme-builder', 'nav-menu'], FakeModule::$booted);
    }
    public function test_duplicate_id_is_rejected(): void {
        $r = new ModuleRegistry();
        $r->add('form', fn() => new FakeModule('form'));
        $this->expectException(LogicException::class);
        $r->add('form', fn() => new FakeModule('form'));
    }
    public function test_empty_registry_boots_nothing(): void {
        $this->assertSame([], (new ModuleRegistry())->bootAll());
    }
}
