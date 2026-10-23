<?php
namespace Modulix\Compat;

/**
 * Lets themes and plugins that call Elementor Pro classes use Modulix: ElementorPro\X resolves to Modulix\X when
 * Modulix has that class, interface or trait. Names Modulix does not have stay undefined, so class_exists() checks
 * in those themes and plugins keep taking their fallback path. Registered only while Elementor Pro is not active.
 */
final class ProAliases {
    public const PREFIX = 'ElementorPro\\';

    public static function target(string $class): ?string {
        $class = ltrim($class, '\\');
        if (strncmp($class, self::PREFIX, strlen(self::PREFIX)) !== 0) {
            return null;
        }
        $rest = substr($class, strlen(self::PREFIX));
        if ($rest === '') {
            return null;
        }
        return $rest === 'Plugin' ? ProPluginShim::class : 'Modulix\\' . $rest;
    }

    public static function load(string $class): void {
        // In the request that activates Elementor Pro next to Modulix, Pro's own classes must load, not aliases.
        if (defined('ELEMENTOR_PRO_VERSION')) {
            return;
        }
        $target = self::target($class);
        if ($target !== null && (class_exists($target) || interface_exists($target) || trait_exists($target))) {
            class_alias($target, $class);
        }
    }

    public static function register(): void {
        spl_autoload_register([self::class, 'load']);
    }
}
