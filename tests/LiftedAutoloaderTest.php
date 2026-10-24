<?php
use Modulix\LiftedAutoloader;
use PHPUnit\Framework\TestCase;

final class LiftedAutoloaderTest extends TestCase {
    /** @dataProvider classes */
    public function test_maps_like_elementor_pro(string $class, ?string $expected): void {
        $this->assertSame($expected, LiftedAutoloader::path($class, '/p'));
    }
    public static function classes(): array {
        return [
            ['Modulix\Modules\ThemeBuilder\Classes\Locations_Manager', '/p/modules/theme-builder/classes/locations-manager.php'],
            ['Modulix\Modules\ThemeBuilder\Conditions\Not_Found404', '/p/modules/theme-builder/conditions/not-found404.php'],
            ['Modulix\Modules\ThemeBuilder\Conditions\Any_Child_Of_Term', '/p/modules/theme-builder/conditions/any-child-of-term.php'],
            ['Modulix\Modules\DynamicTags\Tags\Site_URL', '/p/modules/dynamic-tags/tags/site-url.php'],
            ['Modulix\Modules\QueryControl\Module', '/p/modules/query-control/module.php'],
            ['Modulix\Core\Utils', '/p/core/utils.php'],
            ['Modulix\Base\Module_Base', '/p/base/module-base.php'],
            // PHP class names are case-insensitive; some Pro files import with a lowercase namespace part.
            ['Modulix\core\utils\Hints', '/p/core/utils/hints.php'],
            ['modulix\Modules\Popup\Module', '/p/modules/popup/module.php'],
            ['Modulix\ActivationGuard', null],
            ['Modulix\Plugin', null],
            ['ElementorPro\Plugin', null],
        ];
    }
}
