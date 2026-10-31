<?php
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class ThemeBuilderConditionsRuntimeTest extends TestCase {
    public static function setUpBeforeClass(): void {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/');
        }

        eval(<<<'PHP'
namespace Modulix\Modules\ThemeBuilder {
    final class Module {
        public static ?object $testInstance = null;
        public static function instance(): object { return self::$testInstance; }
    }
}
namespace {
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed { return $value; }
    function get_post_status(int $postId): string { return 'publish'; }
}
PHP);

        require_once __DIR__ . '/../modules/theme-builder/classes/conditions-manager.php';
    }

    public function test_hierarchical_target_id_reaches_the_runtime_leaf_condition(): void {
        $locationManager = new class {
            public function inspector_log(array $entry): void {}
        };
        \Modulix\Modules\ThemeBuilder\Module::$testInstance = new class($locationManager) {
            public function __construct(private object $locationManager) {}
            public function get_locations_manager(): object { return $this->locationManager; }
        };

        $reflection = new ReflectionClass(\Modulix\Modules\ThemeBuilder\Classes\Conditions_Manager::class);
        $manager = $reflection->newInstanceWithoutConstructor();
        $cache = new class {
            public function get_by_location(string $location): array {
                return [42 => ['include/singular/page/17']];
            }
        };
        $cacheProperty = $reflection->getProperty('cache');
        $cacheProperty->setValue($manager, $cache);

        $singular = new class {
            public array $calls = [];
            public function get_name(): string { return 'singular'; }
            public function check(array $args): bool { $this->calls[] = $args; return true; }
            public static function get_priority(): int { return 60; }
            public function get_sub_conditions(): array { return ['page']; }
        };
        $page = new class {
            public array $calls = [];
            public function get_name(): string { return 'page'; }
            public function check(array $args): bool { $this->calls[] = $args; return true; }
            public static function get_priority(): int { return 60; }
            public function get_sub_conditions(): array { return []; }
        };
        $manager->register_condition_instance($singular);
        $manager->register_condition_instance($page);

        $templates = $manager->get_location_templates('header');

        $this->assertSame([[]], $singular->calls);
        $this->assertSame([['id' => '17']], $page->calls);
        $this->assertArrayHasKey(42, $templates);
    }
}
