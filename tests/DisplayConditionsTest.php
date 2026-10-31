<?php
namespace Elementor {
    if (!class_exists(Controls_Stack::class, false)) {
        class Controls_Stack {
            public function __construct(array $data = []) {}
        }
    }
}

namespace Modulix\Modules\DisplayConditions\Conditions {
    final class DisplayConditionsTestState {
        public static bool $loggedIn = false;
    }

    function is_user_logged_in(): bool {
        return DisplayConditionsTestState::$loggedIn;
    }
}

namespace {
    if (!defined('ABSPATH')) {
        define('ABSPATH', dirname(__DIR__) . '/');
    }
    require_once dirname(__DIR__) . '/modules/display-conditions/classes/comparator-provider.php';
    require_once dirname(__DIR__) . '/modules/display-conditions/classes/comparators-checker.php';
    require_once dirname(__DIR__) . '/modules/display-conditions/classes/and-condition.php';
    require_once dirname(__DIR__) . '/modules/display-conditions/classes/or-condition.php';
    require_once dirname(__DIR__) . '/modules/display-conditions/conditions/base/condition-base.php';
    require_once dirname(__DIR__) . '/modules/display-conditions/conditions/login-status-condition.php';

    use Modulix\Modules\DisplayConditions\Classes\Comparator_Provider;
    use Modulix\Modules\DisplayConditions\Classes\Or_Condition;
    use Modulix\Modules\DisplayConditions\Conditions\DisplayConditionsTestState;
    use Modulix\Modules\DisplayConditions\Conditions\Login_Status_Condition;
    use PHPUnit\Framework\TestCase;

    final class DisplayConditionsTest extends TestCase {
        public function test_login_status_condition_matches_the_actual_visitor_state(): void {
            $condition = new Login_Status_Condition([new stdClass()]);

            DisplayConditionsTestState::$loggedIn = false;
            $this->assertFalse($condition->check([
                'comparator' => Comparator_Provider::COMPARATOR_IS,
                'status' => 'logged_in',
            ]));

            DisplayConditionsTestState::$loggedIn = true;
            $this->assertTrue($condition->check([
                'comparator' => Comparator_Provider::COMPARATOR_IS,
                'status' => 'logged_in',
            ]));
        }

        public function test_modulix_keeps_display_conditions_available_without_a_pro_license(): void {
            $source = (string) file_get_contents(dirname(__DIR__) . '/modules/display-conditions/module.php');

            $this->assertMatchesRegularExpression('/should_show_promo\(\): bool\s*\{\s*return false;/', $source);
            $this->assertMatchesRegularExpression('/can_use_display_conditions\(\): bool\s*\{\s*return true;/', $source);
            $this->assertStringNotContainsString('Modulix\\License', $source);
        }

        public function test_condition_groups_require_every_and_condition_but_only_one_or_group(): void {
            $condition = new class {
                public function check(array $options): bool {
                    return (bool) ($options['passes'] ?? false);
                }
            };
            $manager = new class($condition) {
                public function __construct(private object $condition) {}
                public function get_condition(string $name): object {
                    return $this->condition;
                }
            };
            $groups = new Or_Condition($manager, [
                [
                    ['condition' => 'first', 'passes' => true],
                    ['condition' => 'second', 'passes' => false],
                ],
                [
                    ['condition' => 'third', 'passes' => true],
                    ['condition' => 'fourth', 'passes' => true],
                ],
            ]);

            $this->assertTrue($groups->check());

            $allFail = new Or_Condition($manager, [
                [['condition' => 'first', 'passes' => false]],
                [['condition' => 'second', 'passes' => false]],
            ]);
            $this->assertFalse($allFail->check());
        }

        public function test_editor_enqueue_selects_both_bundles_and_registers_matching_translation_catalog(): void {
            $development = dirname(__DIR__) . '/assets/js/display-conditions.js';
            $production = dirname(__DIR__) . '/assets/js/display-conditions.min.js';
            $probe = <<<'PHP'
namespace Elementor {
    class Utils {
        public static bool $scriptDebug = false;
        public static function is_script_debug(): bool { return self::$scriptDebug; }
    }
}
namespace Modulix\Base { class Module_Base {} }
namespace Modulix\Modules\DisplayConditions {
    final class State {
        public static array $enqueued = [];
        public static array $inline = [];
        public static array $translations = [];
    }
    function wp_enqueue_script(...$arguments): void { State::$enqueued[] = $arguments; }
    function wp_add_inline_script(...$arguments): void { State::$inline[] = $arguments; }
    function wp_set_script_translations(...$arguments): void { State::$translations[] = $arguments; }
    function wp_json_encode($value): string { return json_encode($value, JSON_THROW_ON_ERROR); }
}
namespace {
    define('ABSPATH', dirname($argv[1], 3) . '/');
    define('MODULIX_ASSETS_URL', '/plugin/assets/');
    define('MODULIX_PATH', dirname($argv[1], 3) . '/');
    define('MODULIX_VERSION', 'test');
    function wp_set_script_translations(...$arguments): bool {
        \Modulix\Modules\DisplayConditions\State::$translations[] = $arguments;
        return true;
    }
    require dirname($argv[1], 3) . '/src/I18n.php';
    require $argv[1];
    $module = (new ReflectionClass(\Modulix\Modules\DisplayConditions\Module::class))->newInstanceWithoutConstructor();
    $enqueue = new ReflectionMethod(\Modulix\Modules\DisplayConditions\Module::class, 'enqueue_main_script');
    $results = [];
    foreach ([false, true] as $debug) {
        \Elementor\Utils::$scriptDebug = $debug;
        \Modulix\Modules\DisplayConditions\State::$enqueued = [];
        \Modulix\Modules\DisplayConditions\State::$inline = [];
        \Modulix\Modules\DisplayConditions\State::$translations = [];
        $enqueue->invoke($module);
        $results[] = [
            'source' => \Modulix\Modules\DisplayConditions\State::$enqueued[0][1],
            'inline_handle' => \Modulix\Modules\DisplayConditions\State::$inline[0][0],
            'translations' => \Modulix\Modules\DisplayConditions\State::$translations[0],
        ];
    }
    echo json_encode($results, JSON_THROW_ON_ERROR);
}
PHP;
            $command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($probe) . ' ' . escapeshellarg(dirname(__DIR__) . '/modules/display-conditions/module.php');
            exec($command . ' 2>&1', $output, $code);

            $this->assertSame(0, $code, implode("\n", $output));
            $this->assertSame([
                [
                    'source' => '/plugin/assets/js/display-conditions.min.js',
                    'inline_handle' => 'e-display-conditions',
                    'translations' => ['e-display-conditions', 'modulix', dirname(__DIR__) . '/languages'],
                ],
                [
                    'source' => '/plugin/assets/js/display-conditions.js',
                    'inline_handle' => 'e-display-conditions',
                    'translations' => ['e-display-conditions', 'modulix', dirname(__DIR__) . '/languages'],
                ],
            ], json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR));
            $this->assertFileExists($development);
            $this->assertFileExists($production);
            foreach ([$development, $production] as $bundle) {
                $source = (string) file_get_contents($bundle);
                $this->assertStringContainsString('modulix', $source);
                $this->assertStringNotContainsString("'elementor-pro'", $source);
                $this->assertStringNotContainsString('"elementor-pro"', $source);
            }
        }
    }
}
