<?php
namespace Modulix\Security;

final class IconArchive {
    public const MAX_ARCHIVE_BYTES = 5 * 1024 * 1024;
    public const MAX_EXPANDED_BYTES = 20 * 1024 * 1024;
    public const MAX_FILE_BYTES = 5 * 1024 * 1024;
    public const MAX_ENTRIES = 256;
    public const MAX_RATIO = 100;

    private const EXTENSIONS = ['css', 'eot', 'json', 'otf', 'ttf', 'txt', 'woff', 'woff2'];

    public static function pathAllowed(string $name): bool {
        if ($name === '' || str_contains($name, "\0") || str_contains($name, '\\') || str_starts_with($name, '/')) {
            return false;
        }

        $trimmed = rtrim($name, '/');
        if ($trimmed === '') {
            return false;
        }
        foreach (explode('/', $trimmed) as $part) {
            if ($part === '' || $part === '.' || $part === '..') {
                return false;
            }
        }

        return true;
    }

    public static function entryNameAllowed(string $name): bool {
        if (!self::pathAllowed($name)) {
            return false;
        }

        if (str_ends_with($name, '/')) {
            return true;
        }
        if (basename($name) === 'icons-reference.html') {
            return true;
        }

        return in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::EXTENSIONS, true);
    }

    public static function entryAllowed(string $name, int $size, int $compressedSize, int $mode): bool {
        if (!self::pathAllowed($name) || $size < 0 || $compressedSize < 0 || $size > self::MAX_FILE_BYTES) {
            return false;
        }

        $type = $mode & 0170000;
        if ($type !== 0 && $type !== 0100000 && $type !== 0040000) {
            return false;
        }

        if ($size > 0 && ($compressedSize === 0 || $size > $compressedSize * self::MAX_RATIO)) {
            return false;
        }

        return true;
    }
}
