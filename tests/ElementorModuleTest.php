<?php
use Modulix\ElementorModule;
use PHPUnit\Framework\TestCase;

final class FakeElementorStyleModule {
    public static int $calls = 0;
    public static function instance(): self { self::$calls++; return new self(); }
}

final class FakeInactiveModule {
    public static int $calls = 0;
    public static function is_active(): bool { return false; }
    public static function instance(): self { self::$calls++; return new self(); }
}

final class ElementorModuleTest extends TestCase {
    public function test_lifted_experiment_is_forced_active_hidden_and_immutable_when_dependencies_are_active(): void {
        $data = ElementorModule::prepareExperimentalData([
            'name' => 'mega-menu',
            'default' => 'inactive',
            'mutable' => true,
            'hidden' => false,
            'dependencies' => ['container'],
        ], static fn(string $dependency): bool => 'container' === $dependency);

        $this->assertSame('active', $data['default']);
        $this->assertFalse($data['mutable']);
        $this->assertTrue($data['hidden']);
    }

    public function test_lifted_experiment_is_not_offered_when_a_dependency_is_inactive(): void {
        $data = ElementorModule::prepareExperimentalData([
            'name' => 'mega-menu',
            'dependencies' => ['container'],
        ], static fn(string $dependency): bool => false);

        $this->assertNull($data);
    }

    public function test_inactive_module_is_not_booted(): void {
        FakeInactiveModule::$calls = 0;
        (new ElementorModule(FakeInactiveModule::class))->boot();
        $this->assertSame(0, FakeInactiveModule::$calls);
    }

    public function test_boot_calls_instance_once(): void {
        FakeElementorStyleModule::$calls = 0;
        (new ElementorModule(FakeElementorStyleModule::class))->boot();
        $this->assertSame(1, FakeElementorStyleModule::$calls);
    }
}
