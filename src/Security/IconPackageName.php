<?php
namespace Modulix\Security;

final class IconPackageName {
    public static function validate(string $name): string {
        $hasControlCharacter = preg_match('/\p{Cc}/u', $name);

        if (
            $name === ''
            || str_contains($name, '/')
            || str_contains($name, '\\')
            || str_contains($name, '..')
            || $hasControlCharacter !== 0
        ) {
            throw new \InvalidArgumentException('The icon package name is not safe for a file path.');
        }

        return $name;
    }
}
