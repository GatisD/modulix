<?php
namespace Modulix;

/**
 * Class-to-file rule for code lifted from Elementor Pro, same as Pro's own autoloader
 * (CamelCase -> dashes, "_" -> "-", lower case), so lifted files keep Pro's paths.
 */
final class LiftedAutoloader {
    private const PREFIXES = ['Modulix\\Modules\\', 'Modulix\\Core\\', 'Modulix\\Base\\'];

    public static function path(string $class, string $root): ?string {
        foreach (self::PREFIXES as $prefix) {
            // PHP class names are case-insensitive (some Pro files import "...\core\utils\...").
            if (strncasecmp($class, $prefix, strlen($prefix)) === 0) {
                $rel = strtolower(preg_replace(
                    ['/([a-z])([A-Z])/', '/_/', '/\\\\/'],
                    ['$1-$2', '-', '/'],
                    substr($class, strlen('Modulix\\'))
                ));
                return $root . '/' . $rel . '.php';
            }
        }
        return null;
    }
}
