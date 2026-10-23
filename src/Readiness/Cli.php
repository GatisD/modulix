<?php
namespace Modulix\Readiness;

/** wp modulix check [--format=table|json]. Exit code 0 when Modulix covers the site's pages, 1 when not. */
final class Cli {
    public static function register(): void {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('modulix check', [self::class, 'check']);
        }
    }

    /** @param string[] $args @param array<string, string> $assoc */
    public static function check(array $args, array $assoc): void {
        $missing = Scanner::forModulix()->missing(WpSource::documents());
        $ecosystem = WpSource::ecosystem();
        if (($assoc['format'] ?? 'table') === 'json') {
            \WP_CLI::line((string) wp_json_encode(['missing' => $missing, 'ecosystem' => $ecosystem], JSON_PRETTY_PRINT));
        } else {
            $rows = Report::rows($missing);
            if ($rows === []) {
                \WP_CLI::success('Modulix covers every Elementor Pro feature these pages and templates use.');
            } else {
                \WP_CLI\Utils\format_items('table', $rows, ['label', 'documents', 'ids']);
            }
            foreach ($ecosystem as $name => $r) {
                if ($r['missing'] !== [] || $r['constants'] !== []) {
                    \WP_CLI::line($name . ': ' . implode(', ', array_merge($r['missing'], $r['constants'])));
                }
            }
        }
        if ($missing !== []) {
            \WP_CLI::halt(1);
        }
    }
}
