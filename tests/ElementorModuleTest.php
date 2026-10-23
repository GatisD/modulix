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
