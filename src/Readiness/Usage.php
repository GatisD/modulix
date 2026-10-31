<?php
namespace Modulix\Readiness;

/**
 * What one document uses, as widget, tag, document, theme location and feature items.
 * The feature keys are controls only Elementor Pro adds (measured in the editor, docs/inventory/pro-keys-*.json).
 */
final class Usage {
    private const DOCUMENT_LOCATIONS = [
        'archive' => 'archive',
        'error-404' => 'single',
        'floating-buttons' => 'floating_buttons',
        'footer' => 'footer',
        'header' => 'header',
        'popup' => 'popup',
        'product' => 'single',
        'product-archive' => 'archive',
        'search-results' => 'archive',
        'single' => 'single',
        'single-page' => 'single',
        'single-post' => 'single',
    ];

    private const ELEMENT_FEATURES = [
        'custom_css' => 'custom_css',
        '_attributes' => 'attributes',
        '_section_attributes' => 'attributes',
        'motion_fx_motion_fx_scrolling' => 'motion_fx',
        'motion_fx_motion_fx_mouse' => 'motion_fx',
        'background_motion_fx_motion_fx_scrolling' => 'motion_fx',
        'background_motion_fx_motion_fx_mouse' => 'motion_fx',
        'sticky' => 'sticky',
        'e_display_conditions' => 'display_conditions',
        'display-conditions' => 'atomic_display_conditions',
    ];
    private const PRO_INTERACTION_TRIGGERS = ['scrollOut', 'scrollOn', 'hover', 'click'];
    private const PRO_INTERACTION_EASINGS = ['easeOut', 'easeInOut', 'backIn', 'backInOut', 'backOut', 'linear'];
    private const CORE_INTERACTION_CONFIG_DEFAULTS = [
        'replay' => false,
        'relativeTo' => 'viewport',
        'repeat' => '',
        'times' => 1,
        'start' => ['size' => 85, 'unit' => '%'],
        'end' => ['size' => 15, 'unit' => '%'],
    ];
    private const INTERACTION_TRIGGERS_WITHOUT_REPLAY = ['load', 'scrollOn', 'hover', 'click'];
    private const PAGE_FEATURES = ['custom_css' => 'custom_css', 'scroll_snap' => 'scroll_snap'];
    private const KIT_FEATURES = [
        'custom_css' => 'custom_css',
        'scroll_snap' => 'scroll_snap',
        'settings_page_transitions_entrance_animation' => 'page_transitions',
        'settings_page_transitions_exit_animation' => 'page_transitions',
        'settings_page_transitions_preloader_type' => 'page_transitions',
    ];

    /** @return string[] sorted, unique */
    public static function of(Document $doc): array {
        $items = [];
        if ($doc->templateType !== '') {
            $items[] = 'document:' . $doc->templateType;
        }
        $location = self::DOCUMENT_LOCATIONS[$doc->templateType] ?? $doc->themeLocation;
        if ($location !== '') {
            $items[] = 'location:' . $location;
        }
        self::walk($doc->elements, $items);
        self::features($doc->settings, $doc->templateType === 'kit' ? self::KIT_FEATURES : self::PAGE_FEATURES, $items);
        foreach (self::tags($doc->settings) as $tag) {
            $items[] = 'tag:' . $tag;
        }
        $items = array_values(array_unique($items));
        sort($items);
        return $items;
    }

    /** Dynamic tag names in every __dynamic__ map, including repeater items. @return string[] */
    public static function tags(mixed $settings): array {
        $out = [];
        if (!is_array($settings)) {
            return $out;
        }
        foreach ($settings as $key => $value) {
            if ($key === '__dynamic__' && is_array($value)) {
                foreach ($value as $tag) {
                    if (is_string($tag) && preg_match_all('/name="([A-Za-z0-9_-]+)"/', $tag, $m)) {
                        array_push($out, ...$m[1]);
                    }
                }
            } elseif (is_array($value)) {
                array_push($out, ...self::tags($value));
            }
        }
        return $out;
    }

    /** @param array<int, mixed> $elements @param string[] $items */
    private static function walk(array $elements, array &$items): void {
        foreach ($elements as $el) {
            if (!is_array($el)) {
                continue;
            }
            $settings = isset($el['settings']) && is_array($el['settings']) ? $el['settings'] : [];
            if (($el['elType'] ?? '') === 'widget' && isset($el['widgetType']) && is_string($el['widgetType'])) {
                $items[] = 'widget:' . $el['widgetType'];
            }
            self::features($settings, self::ELEMENT_FEATURES, $items);
            if (self::hasAtomicCustomCss($el['styles'] ?? null)) {
                $items[] = 'feature:atomic_custom_css';
            }
            if (self::hasProInteractions($el['interactions'] ?? null)) {
                $items[] = 'feature:interactions';
            }
            foreach (self::tags($settings) as $tag) {
                $items[] = 'tag:' . $tag;
            }
            if (isset($el['elements']) && is_array($el['elements'])) {
                self::walk($el['elements'], $items);
            }
        }
    }

    /** @param array<string, mixed> $settings @param array<string, string> $map @param string[] $items */
    private static function features(array $settings, array $map, array &$items): void {
        foreach ($map as $key => $feature) {
            if (array_key_exists($key, $settings) && self::isSet($settings[$key])) {
                $items[] = 'feature:' . $feature;
            }
        }
    }

    private static function hasAtomicCustomCss(mixed $styles): bool {
        if (!is_array($styles)) {
            return false;
        }
        foreach ($styles as $style) {
            if (!is_array($style)) {
                continue;
            }
            $variants = self::unwrap($style['variants'] ?? []);
            if (!is_array($variants)) {
                continue;
            }
            foreach ($variants as $variant) {
                $variant = self::unwrap($variant);
                if (is_array($variant) && array_key_exists('custom_css', $variant)
                    && self::isSet(self::unwrap($variant['custom_css']))) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function hasProInteractions(mixed $interactions): bool {
        if (is_string($interactions)) {
            $interactions = json_decode($interactions, true);
        }
        $interactions = self::unwrap($interactions);
        if (!is_array($interactions)) {
            return false;
        }
        $items = self::unwrap($interactions['items'] ?? []);
        if (!is_array($items)) {
            return false;
        }
        foreach ($items as $item) {
            $item = self::unwrap($item);
            if (!is_array($item)) {
                continue;
            }
            $trigger = self::unwrap($item['trigger'] ?? null);
            $trigger = is_string($trigger) ? $trigger : 'load';
            if (is_string($trigger) && in_array($trigger, self::PRO_INTERACTION_TRIGGERS, true)) {
                return true;
            }
            $animation = self::unwrap($item['animation'] ?? null);
            if (!is_array($animation)) {
                continue;
            }
            if (self::unwrap($animation['effect'] ?? null) === 'custom'
                || self::isSet(self::unwrap($animation['custom_effect'] ?? null))) {
                return true;
            }
            $config = self::unwrap($animation['config'] ?? null);
            if (!is_array($config)) {
                continue;
            }
            $easing = self::unwrap($config['easing'] ?? null);
            if (is_string($easing) && in_array($easing, self::PRO_INTERACTION_EASINGS, true)) {
                return true;
            }
            foreach (self::CORE_INTERACTION_CONFIG_DEFAULTS as $key => $default) {
                if (!array_key_exists($key, $config)) {
                    continue;
                }
                $value = self::unwrap($config[$key]);
                if (!self::interactionConfigMatchesDefault($value, $default)
                    && self::interactionConfigAffectsTrigger($key, $trigger, $config)) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function interactionConfigMatchesDefault(mixed $value, mixed $default): bool {
        return is_array($value) && is_array($default) ? $value == $default : $value === $default;
    }

    /** @param array<string, mixed> $config */
    private static function interactionConfigAffectsTrigger(string $key, string $trigger, array $config): bool {
        return match ($key) {
            'replay' => !in_array($trigger, self::INTERACTION_TRIGGERS_WITHOUT_REPLAY, true),
            'relativeTo', 'start', 'end' => $trigger === 'scrollOn',
            'repeat' => $trigger !== 'scrollOn',
            'times' => $trigger !== 'scrollOn' && self::unwrap($config['repeat'] ?? null) === 'times',
            default => false,
        };
    }

    private static function unwrap(mixed $value): mixed {
        while (is_array($value) && array_key_exists('$$type', $value) && array_key_exists('value', $value)) {
            $value = $value['value'];
        }
        return $value;
    }

    /** Elementor stores unset controls as '', 'no', [] or null. */
    private static function isSet(mixed $value): bool {
        if (is_array($value)) {
            foreach ($value as $v) {
                if (self::isSet($v)) {
                    return true;
                }
            }
            return false;
        }
        return !($value === null || $value === '' || $value === 'no' || $value === false);
    }
}
