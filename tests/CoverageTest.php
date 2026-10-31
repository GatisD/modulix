<?php
use Modulix\Coverage;
use PHPUnit\Framework\TestCase;

/** The coverage lists must match what modules/ registers, so the readiness check never claims too much or too little. */
final class CoverageTest extends TestCase {
    private const NAME_RE = "~function get_name\\(\\)[^{]*\\{\\s*(?://[^\\n]*\\n\\s*)*return\\s+'([^']+)'\\s*;~";
    private const TYPE_RE = "~static function get_type\\(\\)[^{]*\\{\\s*return\\s+'([^']+)'\\s*;~";

    private const FEATURE_MODULES = [
        'attributes' => 'attributes',
        'custom-attributes' => 'attributes',
        'custom-css' => 'custom_css',
        'display-conditions' => 'display_conditions',
        'motion-fx' => 'motion_fx',
        'page-transitions' => 'page_transitions',
        'scroll-snap' => 'scroll_snap',
        'sticky' => 'sticky',
    ];

    /** @param string[] $globs @return string[] names returned by the non-abstract classes in those files, sorted */
    private static function names(array $globs, string $re): array {
        $root = dirname(__DIR__);
        $out = [];
        foreach ($globs as $glob) {
            foreach (glob("$root/$glob") ?: [] as $file) {
                $src = (string) file_get_contents($file);
                if (preg_match('/^\s*abstract\s+class\s/m', $src)) {
                    continue;
                }
                if (preg_match($re, $src, $m)) {
                    $out[] = $m[1];
                }
            }
        }
        $out = array_values(array_unique($out));
        sort($out);
        return $out;
    }

    /** @param string[] $list @return string[] */
    private static function sorted(array $list): array {
        sort($list);
        return $list;
    }

    /** @return string[] document types registered by shipped modules, sorted */
    private static function documentTypes(): array {
        $types = self::names(['modules/*/documents/*.php', 'modules/*/document.php'], self::TYPE_RE);
        $root = dirname(__DIR__);

        // Some registered documents expose their stored type through get_name(), inherited get_type(), or constants.
        foreach (array_merge(
            glob("$root/modules/*/documents/*.php") ?: [],
            glob("$root/modules/*/document.php") ?: []
        ) as $file) {
            $src = (string) file_get_contents($file);
            if (preg_match('/^\s*abstract\s+class\s/m', $src)) {
                continue;
            }
            if (preg_match(self::NAME_RE, $src, $m)) {
                $types[] = $m[1];
            }
            if (preg_match('/function get_type\(\)[^{]*\{\s*return\s+(?:static|self)::DOCUMENT_TYPE\s*;/', $src)
                && preg_match("/const DOCUMENT_TYPE = '([^']+)'/", $src, $m)) {
                $types[] = $m[1];
            }
            if (preg_match('/function get_type\(\)[^{]*\{\s*return\s+Module::DOCUMENT_TYPE\s*;/', $src)
                && preg_match("/const DOCUMENT_TYPE = '([^']+)'/", (string) file_get_contents(dirname($file) . '/module.php'), $m)) {
                $types[] = $m[1];
            }
        }

        // Elementor Core 4.x registers this document type; Modulix ships its matching Pro widgets on that document.
        $floatingButtons = (string) file_get_contents("$root/modules/floating-buttons/module.php");
        if (preg_match("/const FLOATING_BUTTONS_DOCUMENT_TYPE = '([^']+)'/", $floatingButtons, $m)) {
            $types[] = $m[1];
        }

        return self::sorted(array_values(array_unique($types)));
    }

    /** @return string[] theme locations registered by shipped modules, sorted */
    private static function themeLocations(): array {
        $root = dirname(__DIR__);
        $files = array_merge(
            glob("$root/modules/theme-builder/documents/*.php") ?: [],
            ["$root/modules/popup/document.php"]
        );
        $locations = [];
        foreach ($files as $file) {
            if (preg_match("/\\['location'\\]\\s*=\\s*'([^']+)'/", (string) file_get_contents($file), $m)) {
                $locations[] = $m[1];
            }
        }
        $customCode = (string) file_get_contents("$root/modules/custom-code/custom-code-metabox.php");
        if (preg_match_all("/const OPTION_LOCATION_[A-Z_]+ = '([^']+)'/", $customCode, $m)) {
            array_push($locations, ...$m[1]);
        }
        return self::sorted(array_values(array_unique($locations)));
    }

    /** @return string[] element features provided by registered modules, sorted */
    private static function features(): array {
        $plugin = (string) file_get_contents(dirname(__DIR__) . '/src/Plugin.php');
        $features = [];
        foreach (self::FEATURE_MODULES as $module => $feature) {
            if (str_contains($plugin, "registry->add('$module'")) {
                $features[] = $feature;
            }
        }
        return self::sorted(array_values(array_unique($features)));
    }

    /** @return array<string, string[]> */
    private static function shipped(): array {
        return [
            'widgets' => self::names(['modules/*/widgets/*.php'], self::NAME_RE),
            'tags' => self::names(['modules/*/tags/*.php', 'modules/*/*/tags/*.php', 'modules/*/tag.php'], self::NAME_RE),
            'documents' => self::documentTypes(),
            'locations' => self::themeLocations(),
            'features' => self::features(),
        ];
    }

    /** @return array<string, string[]> */
    private static function covered(): array {
        return [
            'widgets' => self::sorted(Coverage::WIDGETS),
            'tags' => self::sorted(Coverage::TAGS),
            'documents' => self::sorted(Coverage::DOCUMENT_TYPES),
            'locations' => defined(Coverage::class . '::THEME_LOCATIONS')
                ? self::sorted(constant(Coverage::class . '::THEME_LOCATIONS'))
                : [],
            'features' => self::sorted(Coverage::FEATURES),
        ];
    }

    public function test_every_shipped_item_is_in_coverage(): void {
        $covered = self::covered();
        foreach (self::shipped() as $kind => $items) {
            $this->assertSame([], array_values(array_diff($items, $covered[$kind])), "Shipped $kind missing from Coverage");
        }
    }

    public function test_every_coverage_item_is_shipped(): void {
        $shipped = self::shipped();
        foreach (self::covered() as $kind => $items) {
            $this->assertSame([], array_values(array_diff($items, $shipped[$kind])), "Coverage $kind not shipped");
        }
    }
}
