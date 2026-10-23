<?php
use Modulix\LiftedAutoloader;
use PHPUnit\Framework\TestCase;

/**
 * Static checks over lifted code: every Modulix class a lifted file names must exist on disk,
 * nothing still points at Elementor Pro, every file carries the GPL s.7 marker and parses.
 */
final class LiftedReferencesTest extends TestCase {
    private const DIRS = ['modules', 'core', 'base'];

    /** @return string[] */
    private static function files(): array {
        $root = dirname(__DIR__);
        $out = [];
        foreach (self::DIRS as $d) {
            if (!is_dir("$root/$d")) { continue; }
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$d", FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->getExtension() === 'php') { $out[] = $f->getPathname(); }
            }
        }
        sort($out);
        return $out;
    }

    public function test_there_is_lifted_code(): void {
        $this->assertGreaterThanOrEqual(40, count(self::files()));
    }

    public function test_every_modulix_reference_resolves(): void {
        $root = dirname(__DIR__);
        $missing = [];
        foreach (self::files() as $file) {
            $src = file_get_contents($file);
            $src = preg_replace('~/\*.*?\*/|//[^\n]*~s', '', $src);
            preg_match('/^namespace\s+([^;]+);/m', $src, $m);
            $ns = $m[1] ?? '';
            $aliases = [];
            preg_match_all('/^use\s+([A-Za-z0-9_\\\\]+)(?:\s+as\s+(\w+))?;/m', $src, $uses, PREG_SET_ORDER);
            foreach ($uses as $u) {
                $parts = explode('\\', $u[1]);
                $aliases[strtolower($u[2] ?? end($parts))] = $u[1];
            }
            $names = [];
            foreach ($aliases as $fqcn) { $names[] = $fqcn; }
            // Group 1 keeps a leading backslash: "\\WP_Query" is global, "WP_Query" is in the file's namespace.
            preg_match_all('/\b(?:extends|implements|new|instanceof)\s+(\\\\?[A-Z][A-Za-z0-9_\\\\]*)/', $src, $m1);
            preg_match_all('/(?<![\w\\\\$>:])(\\\\?[A-Z][A-Za-z0-9_\\\\]*)::/', $src, $m2);
            preg_match_all('/^\s*use\s+([A-Z][A-Za-z0-9_]*)\s*;/m', preg_replace('/^use\s.*$/m', '', $src), $m3); // trait use inside class
            foreach (array_merge($m1[1], $m2[1], $m3[1]) as $ref) {
                if (in_array($ref, ['self', 'static', 'parent'], true)) { continue; }
                if ($ref[0] === '\\') { $names[] = substr($ref, 1); continue; }
                $first = explode('\\', $ref)[0];
                if (isset($aliases[strtolower($first)])) {
                    $names[] = $aliases[strtolower($first)] . substr($ref, strlen($first));
                } elseif (!str_contains($ref, '\\') && $ns !== '') {
                    $names[] = $ns . '\\' . $ref;
                } else {
                    $names[] = $ref;
                }
            }
            foreach (array_unique($names) as $fqcn) {
                $fqcn = ltrim($fqcn, '\\');
                $path = LiftedAutoloader::path($fqcn, $root);
                if ($path === null) {
                    // Other Modulix\ classes must be our own PSR-4 classes in src/ (e.g. Modulix\License\API is not).
                    if (strncmp($fqcn, 'Modulix\\', 8) !== 0) { continue; }
                    $path = $root . '/src/' . str_replace('\\', '/', substr($fqcn, 8)) . '.php';
                }
                if (is_file($path) || is_dir(substr($path, 0, -4))) { continue; }
                $missing[] = basename(dirname($file)) . '/' . basename($file) . ' -> ' . $fqcn;
            }
        }
        $this->assertSame([], $missing);
    }

    public function test_no_elementor_pro_leftovers(): void {
        $hits = [];
        foreach (self::files() as $file) {
            $src = file_get_contents($file);
            if (preg_match('/ElementorPro\\\\|ELEMENTOR_PRO_|\'elementor-pro\'/', $src, $m)) {
                $hits[] = basename($file) . ': ' . $m[0];
            }
        }
        $this->assertSame([], $hits);
    }

    public function test_every_file_is_marked_and_parses(): void {
        $bad = [];
        foreach (self::files() as $file) {
            if (!str_contains(file_get_contents($file), 'Modified by Modulix (')) {
                $bad[] = basename($file) . ': no marker';
            }
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
            if ($code !== 0) { $bad[] = basename($file) . ': ' . implode(' ', $out); }
            $out = [];
        }
        $this->assertSame([], $bad);
    }
}
