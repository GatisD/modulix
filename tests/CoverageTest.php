<?php
use Modulix\Coverage;
use PHPUnit\Framework\TestCase;

/** The coverage lists must match what modules/ registers, so the readiness check never claims too much or too little. */
final class CoverageTest extends TestCase {
    private const NAME_RE = "~function get_name\\(\\)[^{]*\\{\\s*(?://[^\\n]*\\n\\s*)*return\\s+'([^']+)'\\s*;~";
    private const TYPE_RE = "~static function get_type\\(\\)[^{]*\\{\\s*return\\s+'([^']+)'\\s*;~";

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

    public function test_widgets_match_modules(): void {
        $this->assertSame(self::names(['modules/*/widgets/*.php'], self::NAME_RE), self::sorted(Coverage::WIDGETS));
    }

    public function test_tags_match_modules(): void {
        $globs = ['modules/*/tags/*.php', 'modules/*/*/tags/*.php', 'modules/*/tag.php'];
        $this->assertSame(self::names($globs, self::NAME_RE), self::sorted(Coverage::TAGS));
    }

    public function test_document_types_match_modules(): void {
        $types = self::names(['modules/*/documents/*.php', 'modules/*/document.php'], self::TYPE_RE);
        // Some documents return Module::DOCUMENT_TYPE; read that constant from the module's module.php.
        foreach (glob(dirname(__DIR__) . '/modules/*/document.php') ?: [] as $file) {
            if (preg_match('/function get_type\(\)[^{]*\{\s*return\s+Module::DOCUMENT_TYPE\s*;/', (string) file_get_contents($file))
                && preg_match("/const DOCUMENT_TYPE = '([^']+)'/", (string) file_get_contents(dirname($file) . '/module.php'), $m)) {
                $types[] = $m[1];
            }
        }
        // Others return static::DOCUMENT_TYPE (or self::) declared in the same file.
        foreach (glob(dirname(__DIR__) . '/modules/*/documents/*.php') ?: [] as $file) {
            $src = (string) file_get_contents($file);
            if (!preg_match('/^\s*abstract\s+class\s/m', $src)
                && preg_match('/function get_type\(\)[^{]*\{\s*return\s+(?:static|self)::DOCUMENT_TYPE\s*;/', $src)
                && preg_match("/const DOCUMENT_TYPE = '([^']+)'/", $src, $m)) {
                $types[] = $m[1];
            }
        }
        $types = array_values(array_unique($types));
        sort($types);
        $this->assertSame($types, self::sorted(Coverage::DOCUMENT_TYPES));
    }
}
