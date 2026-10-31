<?php
namespace Modulix\Mcp;

final class Settings {
    public const OPTION = 'modulix_mcp';
    public const READ_ABILITIES = [
        'modulix/site-overview',
        'modulix/list-documents',
        'modulix/get-document',
        'modulix/widget-schema',
    ];
    public const OPTIONAL_READ_ABILITIES = [
        'modulix/preview-url',
        'modulix/readiness',
    ];
    public const WRITE_ABILITIES = [
        'modulix/create-document',
        'modulix/update-elements',
        'modulix/set-page-settings',
        'modulix/set-conditions',
        'modulix/update-kit',
        'modulix/publish',
    ];

    public static function defaults(): array {
        return [
            'enabled' => false,
            'abilities' => self::READ_ABILITIES,
        ];
    }

    public static function load(): array {
        $stored = get_option(self::OPTION, []);
        if (!is_array($stored)) {
            return self::defaults();
        }
        return self::sanitize(array_merge(self::defaults(), $stored));
    }

    public static function sanitize(array $input): array {
        $abilities = [];
        foreach ((array) ($input['abilities'] ?? []) as $ability) {
            if (!is_scalar($ability)) {
                continue;
            }
            $parts = explode('/', (string) $ability, 2);
            $parts = array_map(static function (string $part): string {
                return function_exists('sanitize_key')
                    ? sanitize_key($part)
                    : (preg_replace('/[^a-z0-9_-]/', '', strtolower($part)) ?? '');
            }, $parts);
            $ability = implode('/', $parts);
            if (in_array($ability, array_merge(self::READ_ABILITIES, self::OPTIONAL_READ_ABILITIES, self::WRITE_ABILITIES), true)
                && !in_array($ability, $abilities, true)) {
                $abilities[] = $ability;
            }
        }
        return [
            'enabled' => filter_var($input['enabled'] ?? false, FILTER_VALIDATE_BOOL),
            'abilities' => $abilities,
        ];
    }
}
