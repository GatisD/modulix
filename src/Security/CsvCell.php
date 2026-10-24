<?php
namespace Modulix\Security;

final class CsvCell {
    public static function encode(string $cell): string {
        if ($cell !== '' && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            $cell = "'" . $cell;
        }

        return '"' . str_replace('"', '""', $cell) . '"';
    }
}
