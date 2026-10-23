<?php
namespace Modulix\Readiness;

/**
 * What one document uses, as items "widget:<name>", "tag:<name>", "document:<type>" and "feature:<name>".
 * The feature keys are controls only Elementor Pro adds (measured in the editor, docs/inventory/pro-keys-*.json).
 */
final class Usage {
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
    ];
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
