<?php
namespace Modulix;

/** Decides how Modulix runs next to the currently active plugins. */
final class ActivationGuard {
    public const PRO_FILE  = 'elementor-pro/elementor-pro.php';
    public const CORE_FILE = 'elementor/elementor.php';

    /** Everything runs. */
    public const FULL = 'full';
    /** Elementor Pro is active (same widget names): only the readiness check runs. */
    public const CHECK = 'check';
    /** Elementor (free) is not active: nothing runs. */
    public const BLOCKED = 'blocked';

    /** @param string[] $active plugin basenames */
    public static function mode(array $active): string {
        if (!in_array(self::CORE_FILE, $active, true)) {
            return self::BLOCKED;
        }
        return in_array(self::PRO_FILE, $active, true) ? self::CHECK : self::FULL;
    }

    public static function message(string $mode): ?string {
        return match ($mode) {
            self::BLOCKED => 'Modulix requires Elementor (free) to be active.',
            self::CHECK => 'Elementor Pro is active, so Modulix only runs its readiness check (Tools -> Modulix). '
                . 'Deactivate Elementor Pro when the check shows nothing your site needs is missing.',
            default => null,
        };
    }

    /**
     * @param string[] $site    option active_plugins
     * @param array<string,int> $network option active_sitewide_plugins (basename => time)
     * @return string[]
     */
    public static function activePlugins(array $site, array $network): array {
        return array_values(array_unique(array_merge($site, array_keys($network))));
    }
}
