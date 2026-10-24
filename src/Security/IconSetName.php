<?php
namespace Modulix\Security;

final class IconSetName {
    public static function sanitize(string $name): string {
        $name = (string) preg_replace('/[^A-Za-z0-9_-]+/', '', $name);

        if ($name === '') {
            throw new \InvalidArgumentException('The icon set name is empty after sanitization.');
        }

        return $name;
    }
}
