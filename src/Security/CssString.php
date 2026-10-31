<?php
namespace Modulix\Security;

final class CssString {
    public static function quote(string $value): string {
        $encoded = preg_replace_callback(
            '/[\\x00-\\x1F\\x7F\\\\\']/',
            static fn(array $match): string => '\\' . dechex(ord($match[0])) . ' ',
            $value
        );

        return "'" . ($encoded ?? '') . "'";
    }
}
