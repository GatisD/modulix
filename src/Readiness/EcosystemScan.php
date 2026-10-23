<?php
namespace Modulix\Readiness;

use Modulix\Compat\ProAliases;
use Modulix\LiftedAutoloader;

/** Finds Elementor Pro class names and constants in a theme's or plugin's PHP files. Loads none of their code. */
final class EcosystemScan {
    /** @return string[] sorted unique references, e.g. "ElementorPro\Plugin", "ELEMENTOR_PRO_VERSION" */
    public static function references(string $php): array {
        preg_match_all('/ElementorPro(?:\\\\{1,2}[A-Za-z0-9_]+)+|ELEMENTOR_PRO_[A-Z0-9_]+/', $php, $m);
        $refs = array_map(static fn(string $r): string => str_replace('\\\\', '\\', $r), $m[0]);
        $refs = array_values(array_unique($refs));
        sort($refs);
        return $refs;
    }

    /**
     * @param string[] $refs
     * @param callable(string): bool $has whether Modulix ships the class
     * @return array{provided: string[], missing: string[], constants: string[]}
     */
    public static function classify(array $refs, callable $has): array {
        $out = ['provided' => [], 'missing' => [], 'constants' => []];
        foreach ($refs as $ref) {
            if (str_starts_with($ref, 'ELEMENTOR_PRO_')) {
                $out['constants'][] = $ref;
                continue;
            }
            $target = ProAliases::target($ref);
            $out[$target !== null && $has($target) ? 'provided' : 'missing'][] = $ref;
        }
        return $out;
    }

    /** @return callable(string): bool true when Modulix ships the file for that class; loads no code */
    public static function modulixHas(string $root): callable {
        return static function (string $class) use ($root): bool {
            $lifted = LiftedAutoloader::path($class, $root);
            if ($lifted !== null) {
                return is_file($lifted);
            }
            return strncmp($class, 'Modulix\\', 8) === 0
                && is_file($root . '/src/' . str_replace('\\', '/', substr($class, 8)) . '.php');
        };
    }

    /** @return string[] references in all PHP files under $dir; vendor, node_modules and .git are skipped */
    public static function scanDir(string $dir): array {
        $skip = ['vendor', 'node_modules', '.git'];
        $it = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            static fn(\SplFileInfo $f): bool => !($f->isDir() && in_array($f->getFilename(), $skip, true))
        ));
        $refs = [];
        foreach ($it as $file) {
            if ($file->getExtension() === 'php' && $file->getSize() < 2_000_000) {
                array_push($refs, ...self::references((string) file_get_contents($file->getPathname())));
            }
        }
        $refs = array_values(array_unique($refs));
        sort($refs);
        return $refs;
    }
}
