<?php
namespace Modulix;

/** Decides whether Modulix may run next to the currently active plugins. */
final class ActivationGuard {
    public const PRO_FILE  = 'elementor-pro/elementor-pro.php';
    public const CORE_FILE = 'elementor/elementor.php';

    /** @param string[] $active plugin basenames */
    public static function check(array $active): ?string {
        if (in_array(self::PRO_FILE, $active, true)) {
            return 'Modulix cannot run while Elementor Pro is active (same widget names). Deactivate Elementor Pro first.';
        }
        if (!in_array(self::CORE_FILE, $active, true)) {
            return 'Modulix requires Elementor (free) to be active.';
        }
        return null;
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
