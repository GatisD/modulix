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

    /** PHP source without comments and string contents, so text such as "the new Search widget" is not read as code. */
    private static function code_only(string $src): string {
        $out = '';
        foreach (token_get_all($src) as $t) {
            if (!is_array($t)) { $out .= $t; continue; }
            [$id, $text] = $t;
            if ($id === T_COMMENT || $id === T_DOC_COMMENT) { $out .= str_repeat("\n", substr_count($text, "\n")); continue; }
            if ($id === T_CONSTANT_ENCAPSED_STRING) { $out .= "''"; continue; }
            if ($id === T_ENCAPSED_AND_WHITESPACE) { continue; }
            $out .= $text;
        }
        return $out;
    }

    public function test_string_contents_are_not_read_as_code(): void {
        $src = "<?php\nnamespace Modulix\\X;\n\$a = esc_html__( 'Any new Search widget', 'modulix' );\n\$b = \"see new Thing\";\n\$u = 'https://example.com/new Url';\n// new Comment\n\$c = new Real();\n";
        $code = self::code_only($src);
        $this->assertStringNotContainsString('Search', $code);
        $this->assertStringNotContainsString('Thing', $code);
        $this->assertStringNotContainsString('Comment', $code);
        $this->assertStringNotContainsString('Url', $code);
        $this->assertStringContainsString('new Real()', $code);
    }

    private static function alias_is_used(string $alias, string $code): bool {
        return (bool) preg_match('/(?<![\\w\\\\$])' . preg_quote($alias, '/') . '(?![\\w])/i', $code);
    }

    public function test_unused_imports_are_not_references(): void {
        $code = self::code_only("<?php\nnamespace Modulix\\X;\nuse Modulix\\Nope\\Base;\nuse Modulix\\Real\\Thing;\nclass A extends Thing {}\n\$base_url = 1;\n");
        $body = preg_replace('/^use\\s.*$/m', '', $code);
        $this->assertFalse(self::alias_is_used('base', $body));
        $this->assertTrue(self::alias_is_used('thing', $body));
    }

    public function test_every_modulix_reference_resolves(): void {
        $root = dirname(__DIR__);
        $this->assertSame([], self::missing_references(self::files(), $root));
    }

    public function test_declared_classes_resolve_case_insensitively(): void {
        $root = dirname(__DIR__);
        $fixtures = "$root/tests/fixtures/lifted-references";
        $this->assertSame([], self::missing_references([
            "$fixtures/case-consumer.php",
            "$fixtures/case-declaration.php",
        ], $root));
    }

    public function test_truly_missing_class_is_reported(): void {
        $root = dirname(__DIR__);
        $fixture = "$root/tests/fixtures/lifted-references/missing-reference.php";
        $this->assertSame(
            ['lifted-references/missing-reference.php -> Modulix\\Fixture\\DefinitelyMissing'],
            self::missing_references([$fixture], $root)
        );
    }

    /** @param string[] $files @return string[] */
    private static function missing_references(array $files, string $root): array {
        $declared = [];
        foreach ($files as $file) {
            $src = self::code_only((string) file_get_contents($file));
            preg_match('/^namespace\s+([^;]+);/m', $src, $namespace);
            preg_match_all(
                '/\b(?:(?:abstract|final|readonly)\s+)*(?:class|interface|trait)\s+([A-Za-z_]\w*)/',
                $src,
                $types
            );
            foreach ($types[1] as $type) {
                $fqcn = ($namespace[1] ?? '') === '' ? $type : $namespace[1] . '\\' . $type;
                $declared[strtolower($fqcn)] = true;
            }
        }
        return self::scan_missing_references($files, $root, $declared);
    }

    /** @param string[] $files @param array<string, bool> $declared @return string[] */
    private static function scan_missing_references(array $files, string $root, array $declared): array {
        $missing = [];
        foreach ($files as $file) {
            $src = self::code_only((string) file_get_contents($file));
            preg_match('/^namespace\s+([^;]+);/m', $src, $m);
            $ns = $m[1] ?? '';
            $aliases = [];
            preg_match_all('/^use\s+([A-Za-z0-9_\\\\]+)(?:\s+as\s+(\w+))?;/m', $src, $uses, PREG_SET_ORDER);
            foreach ($uses as $u) {
                $parts = explode('\\', $u[1]);
                $aliases[strtolower($u[2] ?? end($parts))] = $u[1];
            }
            $names = [];
            // An import that the code never uses cannot fail at runtime (Pro itself has some); count only used ones.
            $body = preg_replace('/^use\s.*$/m', '', $src);
            foreach ($aliases as $alias => $fqcn) {
                if (self::alias_is_used($alias, $body)) { $names[] = $fqcn; }
            }
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
                // Popup's Forms action is lifted for runtime registration after the Forms module exists.
                if (str_ends_with($file, '/modules/popup/form-action.php') && str_starts_with($fqcn, 'Modulix\\Modules\\Forms\\')) { continue; }
                if (isset($declared[strtolower($fqcn)])) { continue; }
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
        return $missing;
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

    /** Lifted code renames ELEMENTOR_PRO_* to MODULIX_*; each such constant must be defined in modulix.php (PHP 8 fails on undefined ones). */
    public function test_every_modulix_constant_is_defined(): void {
        $root = dirname(__DIR__);
        preg_match_all("/define\\('(MODULIX_[A-Z_]+)'/", (string) file_get_contents("$root/modulix.php"), $d);
        $used = [];
        foreach (self::files() as $file) {
            preg_match_all('/\bMODULIX_[A-Z_]+\b/', (string) file_get_contents($file), $m);
            foreach ($m[0] as $c) {
                $used[$c][] = substr($file, strlen($root) + 1);
            }
        }
        $missing = array_diff_key($used, array_flip($d[1]));
        $this->assertSame([], array_map(static fn(array $f): string => $f[0], $missing));
    }

    /** Plugin::instance() in lifted code is the Pro shim: only the members the shim has may be used. */
    public function test_plugin_instance_members_exist(): void {
        $supported = ['app', 'modules_manager', 'php_api'];
        $bad = [];
        foreach (self::files() as $file) {
            $src = (string) file_get_contents($file);
            if (!preg_match_all('/^use\s+Modulix\\\\Plugin(?:\s+as\s+(\w+))?;/m', $src, $uses, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($uses as $u) {
                $alias = $u[1] ?? 'Plugin';
                preg_match_all('/\b' . preg_quote($alias, '/') . '::instance\(\)->(\w+)/', $src, $m);
                foreach (array_diff(array_unique($m[1]), $supported) as $member) {
                    $bad[] = substr($file, strlen(dirname(__DIR__)) + 1) . ": $alias::instance()->$member";
                }
            }
        }
        $this->assertSame([], $bad);
    }

    /** Modulix\Core\Utils is trimmed: every static method lifted code calls on it must exist there. */
    public function test_core_utils_methods_exist(): void {
        $root = dirname(__DIR__);
        preg_match_all('/function\s+(\w+)\s*\(/', (string) file_get_contents("$root/core/utils.php"), $defined);
        $have = array_map('strtolower', $defined[1]);
        $missing = [];
        foreach (self::files() as $file) {
            $src = self::code_only((string) file_get_contents($file));
            if (!preg_match_all('/^use\s+Modulix\\\\Core\\\\Utils(?:\s+as\s+(\w+))?;/m', $src, $uses, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($uses as $u) {
                $alias = $u[1] ?? 'Utils';
                preg_match_all('/(?<![\\w\\\\])' . preg_quote($alias, '/') . '::(\w+)\s*\(/', $src, $m);
                foreach (array_unique($m[1]) as $method) {
                    if (!in_array(strtolower($method), $have, true)) {
                        $missing[] = substr($file, strlen($root) + 1) . ": $alias::$method()";
                    }
                }
            }
        }
        $this->assertSame([], $missing);
    }

    /** @return array{namespace: string, aliases: array<string, string>, class: ?string, parent: ?string, traits: string[]} */
    private static function class_info(string $src): array {
        $src = self::code_only($src);
        preg_match('/^namespace\s+([^;]+);/m', $src, $namespace);
        $aliases = [];
        preg_match_all('/^use\s+([A-Za-z0-9_\\\\]+)(?:\s+as\s+(\w+))?;/m', $src, $uses, PREG_SET_ORDER);
        foreach ($uses as $use) {
            $parts = explode('\\', $use[1]);
            $aliases[strtolower($use[2] ?? end($parts))] = $use[1];
        }
        preg_match('/\b(?:abstract\s+|final\s+)?(?:class|trait)\s+(\w+)(?:\s+extends\s+(\\\\?[A-Za-z_][A-Za-z0-9_\\\\]*))?/', $src, $class);
        $resolve = static function (string $name) use ($aliases, $namespace): string {
            if ($name[0] === '\\') { return substr($name, 1); }
            $first = explode('\\', $name)[0];
            if (isset($aliases[strtolower($first)])) { return $aliases[strtolower($first)] . substr($name, strlen($first)); }
            return str_contains($name, '\\') ? $name : (($namespace[1] ?? '') === '' ? $name : $namespace[1] . '\\' . $name);
        };
        $traits = [];
        if (!empty($class[0])) {
            $body = substr($src, strpos($src, $class[0]) + strlen($class[0]));
            preg_match_all('/^\s*use\s+([A-Z][A-Za-z0-9_\\\\]*)\s*;/m', $body, $trait_uses);
            foreach ($trait_uses[1] as $trait) { $traits[] = $resolve($trait); }
        }
        return [
            'namespace' => $namespace[1] ?? '',
            'aliases' => $aliases,
            'class' => empty($class[1]) ? null : $resolve($class[1]),
            'parent' => empty($class[2]) ? null : $resolve($class[2]),
            'traits' => $traits,
        ];
    }

    private static function resolve_name(string $name, array $info): string {
        if ($name[0] === '\\') { return substr($name, 1); }
        $first = explode('\\', $name)[0];
        if (isset($info['aliases'][strtolower($first)])) {
            return $info['aliases'][strtolower($first)] . substr($name, strlen($first));
        }
        return str_contains($name, '\\') ? $name : ($info['namespace'] === '' ? $name : $info['namespace'] . '\\' . $name);
    }

    private static function class_path(string $class, string $root): ?string {
        $path = LiftedAutoloader::path($class, $root);
        if ($path !== null) { return $path; }
        if (str_starts_with($class, 'Modulix\\')) {
            return $root . '/src/' . str_replace('\\', '/', substr($class, 8)) . '.php';
        }
        return null;
    }

    /** @param array<string, bool> $seen */
    private static function method_resolves(string $class, string $method, string $root, array &$seen): bool {
        if (!str_starts_with($class, 'Modulix\\')) { return true; }
        $key = strtolower($class . '::' . $method);
        if (isset($seen[$key])) { return false; }
        $seen[$key] = true;
        $path = self::class_path($class, $root);
        if ($path === null || !is_file($path)) { return false; }
        $src = (string) file_get_contents($path);
        if (preg_match('/function\s+__callStatic\s*\(/i', self::code_only($src))) { return true; }
        if (preg_match('/function\s+' . preg_quote($method, '/') . '\s*\(/i', self::code_only($src))) { return true; }
        $info = self::class_info($src);
        foreach (array_merge($info['traits'], $info['parent'] === null ? [] : [$info['parent']]) as $ancestor) {
            if (self::method_resolves($ancestor, $method, $root, $seen)) { return true; }
        }
        return false;
    }

    public function test_static_calls_resolve(): void {
        $root = dirname(__DIR__);
        $missing = [];
        foreach (self::files() as $file) {
            $src = self::code_only((string) file_get_contents($file));
            $info = self::class_info($src);
            preg_match_all('/(?<![\\w\\\\$])((?:\\\\?[A-Z][A-Za-z0-9_\\\\]*)|self|static|parent)::(\w+)\s*\(/i', $src, $calls, PREG_SET_ORDER);
            foreach ($calls as $call) {
                $name = strtolower($call[1]);
                if ($name === 'parent') {
                    $class = $info['parent'];
                } elseif ($name === 'self' || $name === 'static') {
                    $class = $info['class'];
                } else {
                    $class = self::resolve_name($call[1], $info);
                }
                if ($class === null || !str_starts_with($class, 'Modulix\\')) { continue; }
                if (($path = self::class_path($class, $root)) === null || !is_file($path)) { continue; }
                $seen = [];
                if (!self::method_resolves($class, $call[2], $root, $seen)) {
                    $missing[] = substr($file, strlen($root) + 1) . ': ' . $class . '::' . $call[2] . '()';
                }
            }
        }
        $missing = array_values(array_unique($missing));
        $this->assertSame([], $missing);
    }
}
