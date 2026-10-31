<?php
namespace Modulix\Mcp;

final class ElementTreeValidator {
    public const DEFAULT_MAX_DEPTH = 40;
    public const DEFAULT_MAX_ELEMENTS = 2000;
    public const MAX_DEPTH_CAP = 100;
    public const MAX_ELEMENTS_CAP = 10000;

    private const STRING_TYPES = [
        'text', 'textarea', 'wysiwyg', 'code', 'hidden', 'select', 'choose', 'color', 'date_time',
        'switcher', 'font', 'animation', 'hover_animation', 'popover_toggle',
    ];
    private const ARRAY_TYPES = [
        'array', 'media', 'gallery', 'repeater', 'slider', 'dimensions', 'gaps', 'image_dimensions',
        'url', 'icons', 'order',
    ];
    private const HTML_TYPES = ['wysiwyg'];

    /**
     * Elementor Core 4.3.5 save sources:
     * - includes/base/element-base.php:651-690 (id, elType, settings, elements, isInner, isLocked)
     * - includes/base/widget-base.php:783-795 (widgetType)
     * - modules/atomic-widgets/elements/base/has-atomic-base.php:247-276
     *   (version, styles, editor_settings, interactions)
     */
    private const ALLOWED_ELEMENT_KEYS = [
        'id', 'elType', 'widgetType', 'settings', 'elements', 'isInner', 'isLocked',
        'version', 'styles', 'editor_settings', 'interactions',
    ];

    private int $maxDepth;
    private int $maxElements;

    public function __construct(
        private SchemaRegistry $registry,
        private HtmlSanitizer $html,
        ?int $maxDepth = null,
        ?int $maxElements = null
    ) {
        $this->maxDepth = $this->resolveLimit(
            'modulix_mcp_validator_max_depth',
            $maxDepth,
            self::DEFAULT_MAX_DEPTH,
            self::MAX_DEPTH_CAP
        );
        $this->maxElements = $this->resolveLimit(
            'modulix_mcp_validator_max_elements',
            $maxElements,
            self::DEFAULT_MAX_ELEMENTS,
            self::MAX_ELEMENTS_CAP
        );
    }

    public function validate(array $elements): array {
        $limitError = $this->checkTreeLimits($elements);
        if ($limitError !== null) {
            return [
                'valid' => false,
                'errors' => [$limitError],
                'elements' => [],
            ];
        }

        $errors = [];
        $clean = $elements;
        $this->validateElements($clean, $errors);
        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'elements' => $clean,
        ];
    }

    public function validateSettingsPatch(array $settings, array $controls, string $path = 'settings'): array {
        $errors = [];
        $clean = $settings;
        $this->validateControlSettings($clean, $controls, $path, $errors);
        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'settings' => $clean,
        ];
    }

    private function resolveLimit(string $filter, ?int $override, int $default, int $cap): int {
        $value = $override ?? $default;
        if ($override === null) {
            $namespacedFilter = __NAMESPACE__ . '\\apply_filters';
            if (function_exists($namespacedFilter)) {
                $value = apply_filters($filter, $value);
            } elseif (function_exists('apply_filters')) {
                $value = \apply_filters($filter, $value);
            }
        }
        if (!is_int($value)) {
            return $default;
        }
        return min($cap, max(1, $value));
    }

    private function checkTreeLimits(array &$elements): ?array {
        $count = 0;
        reset($elements);
        $stack = [[
            'elements' => &$elements,
            'path' => 'elements',
            'depth' => 1,
        ]];

        while ($stack !== []) {
            $frameIndex = array_key_last($stack);
            $nodes = &$stack[$frameIndex]['elements'];
            $index = key($nodes);
            if ($index === null) {
                array_pop($stack);
                unset($nodes);
                continue;
            }
            $path = $this->appendIndex($stack[$frameIndex]['path'], $index);
            $depth = $stack[$frameIndex]['depth'];
            $element = &$nodes[$index];
            next($nodes);
            $count++;
            if ($count > $this->maxElements) {
                return $this->error('too_many_elements', $path, 'Element tree exceeds the total element limit.');
            }
            if (!is_array($element) || !isset($element['elements']) || !is_array($element['elements']) || $element['elements'] === []) {
                unset($element, $nodes);
                continue;
            }
            if ($depth >= $this->maxDepth) {
                return $this->error('tree_too_deep', $path . '.elements', 'Element tree exceeds the depth limit.');
            }
            $children = &$element['elements'];
            reset($children);
            $stack[] = [
                'elements' => &$children,
                'path' => $path . '.elements',
                'depth' => $depth + 1,
            ];
            unset($children, $element, $nodes);
        }

        return null;
    }

    private function validateElements(array &$elements, array &$errors): void {
        $ids = [];
        reset($elements);
        $stack = [[
            'elements' => &$elements,
            'path' => 'elements',
        ]];

        while ($stack !== []) {
            $frameIndex = array_key_last($stack);
            $nodes = &$stack[$frameIndex]['elements'];
            $index = key($nodes);
            if ($index === null) {
                array_pop($stack);
                unset($nodes);
                continue;
            }
            $elementPath = $this->appendIndex($stack[$frameIndex]['path'], $index);
            $element = &$nodes[$index];
            next($nodes);
            if (!is_array($element)) {
                $errors[] = $this->error('wrong_type', $elementPath, 'Element must be an object.');
                unset($element, $nodes);
                continue;
            }

            foreach (array_keys($element) as $key) {
                if (!is_string($key) || !in_array($key, self::ALLOWED_ELEMENT_KEYS, true)) {
                    $errors[] = $this->error(
                        'unknown_element_key',
                        $this->appendKey($elementPath, $key),
                        'Unknown Elementor element key.'
                    );
                }
            }
            $id = $element['id'] ?? null;
            if (!is_string($id) || $id === '') {
                $errors[] = $this->error(
                    'invalid_element_id',
                    $elementPath . '.id',
                    'Element id must be a non-empty string.'
                );
            } elseif (isset($ids[$id])) {
                $errors[] = $this->error(
                    'duplicate_element_id',
                    $elementPath . '.id',
                    'Element ids must be unique in the whole document.'
                );
            } else {
                $ids[$id] = $elementPath;
            }
            $this->validateElementFieldTypes($element, $elementPath, $errors);

            $elementType = isset($element['elType']) && is_string($element['elType'])
                ? $element['elType']
                : null;
            $schemaName = null;
            if ($elementType === 'widget') {
                if (isset($element['widgetType']) && is_string($element['widgetType']) && $element['widgetType'] !== '') {
                    $schemaName = $element['widgetType'];
                }
            } elseif ($elementType !== null && $elementType !== '') {
                $schemaName = $elementType;
            }

            if ($schemaName !== null) {
                $schema = $this->registry->schema($schemaName);
                if ($schema === null) {
                    $key = $elementType === 'widget' ? 'widgetType' : 'elType';
                    $errors[] = $this->error(
                        'unknown_widget',
                        $elementPath . '.' . $key,
                        'Unknown Elementor element type.'
                    );
                } elseif (isset($element['settings']) && is_array($element['settings'])) {
                    $controls = isset($schema['controls']) && is_array($schema['controls']) ? $schema['controls'] : [];
                    $this->validateControlSettings($element['settings'], $controls, $elementPath . '.settings', $errors);
                }
            }

            if (!array_key_exists('settings', $element)) {
                $element['settings'] = [];
            }
            if (!array_key_exists('elements', $element)) {
                $element['elements'] = [];
            }
            if (is_array($element['elements']) && $element['elements'] !== []) {
                $children = &$element['elements'];
                reset($children);
                $stack[] = [
                    'elements' => &$children,
                    'path' => $elementPath . '.elements',
                ];
                unset($children);
            }
            unset($element, $nodes);
        }
    }

    private function validateElementFieldTypes(array $element, string $path, array &$errors): void {
        if (!isset($element['elType']) || !is_string($element['elType']) || $element['elType'] === '') {
            $errors[] = $this->error('wrong_type', $path . '.elType', 'Element type must be a non-empty string.');
        }
        if (($element['elType'] ?? null) === 'widget'
            && (!isset($element['widgetType']) || !is_string($element['widgetType']) || $element['widgetType'] === '')) {
            $errors[] = $this->error('wrong_type', $path . '.widgetType', 'Widget type must be a non-empty string.');
        } elseif (isset($element['widgetType']) && !is_string($element['widgetType'])) {
            $errors[] = $this->error('wrong_type', $path . '.widgetType', 'Widget type must be a string.');
        }
        if (isset($element['settings']) && !is_array($element['settings'])) {
            $errors[] = $this->error('wrong_type', $path . '.settings', 'Settings must be an object.');
        }
        if (isset($element['elements']) && !is_array($element['elements'])) {
            $errors[] = $this->error('wrong_type', $path . '.elements', 'Elements must be an array.');
        }
        foreach (['isInner', 'isLocked'] as $key) {
            if (isset($element[$key]) && !is_bool($element[$key])) {
                $errors[] = $this->error('wrong_type', $path . '.' . $key, $key . ' must be a boolean.');
            }
        }
        if (isset($element['version']) && !is_string($element['version'])) {
            $errors[] = $this->error('wrong_type', $path . '.version', 'Atomic element version must be a string.');
        }
        foreach (['styles', 'editor_settings', 'interactions'] as $key) {
            if (isset($element[$key]) && !is_array($element[$key])) {
                $errors[] = $this->error('wrong_type', $path . '.' . $key, $key . ' must be an object or array.');
            } elseif (isset($element[$key])) {
                $this->rejectObjects($element[$key], $path . '.' . $key, $errors);
            }
        }
    }

    private function validateControlSettings(array &$settings, array $controls, string $path, array &$errors): void {
        $byName = [];
        foreach ($controls as $control) {
            if (is_array($control) && isset($control['name']) && is_string($control['name'])) {
                $byName[$control['name']] = $control;
            }
        }
        foreach ($settings as $name => &$value) {
            if (!is_string($name)) {
                $errors[] = $this->error(
                    'wrong_type',
                    $this->appendKey($path, $name),
                    'Setting names must be strings.'
                );
                continue;
            }
            if (in_array($name, ['__dynamic__', '__globals__'], true)) {
                if (!is_array($value)) {
                    $errors[] = $this->error('wrong_type', $path . '.' . $name, 'Dynamic and global settings must be objects.');
                    continue;
                }
                foreach ($value as $target => $binding) {
                    $bindingPath = $this->appendKey($path . '.' . $name, $target);
                    if (!is_string($target)) {
                        $errors[] = $this->error('wrong_type', $bindingPath, 'Binding names must be strings.');
                    } elseif (!isset($byName[$target])) {
                        $errors[] = $this->error('unknown_setting', $bindingPath, 'Unknown Elementor setting binding.');
                    } elseif (!is_string($binding)) {
                        $errors[] = $this->error('wrong_type', $bindingPath, 'Setting bindings must be strings.');
                    }
                }
                continue;
            }
            $control = $byName[$name] ?? null;
            if ($control === null && preg_match('/_(widescreen|laptop|tablet_extra|tablet|mobile_extra|mobile)$/', $name, $match)) {
                $base = substr($name, 0, -strlen($match[0]));
                if (!empty($byName[$base]['responsive'])) {
                    $control = $byName[$base];
                }
            }
            $settingPath = $path . '.' . $name;
            if ($control === null) {
                $errors[] = $this->error('unknown_setting', $settingPath, 'Unknown Elementor setting.');
                continue;
            }
            if ($this->rejectObjects($value, $settingPath, $errors)) {
                continue;
            }
            $type = isset($control['type']) && is_string($control['type']) ? $control['type'] : '';
            if (!$this->hasValidType($value, $type)) {
                $errors[] = $this->error('wrong_type', $settingPath, 'Setting value has the wrong type.');
                continue;
            }
            $options = isset($control['options']) && is_array($control['options']) ? $control['options'] : [];
            if ($options !== [] && !$this->hasValidOption($value, $options)) {
                $errors[] = $this->error('invalid_option', $settingPath, 'Setting value is not an allowed option.');
                continue;
            }
            $htmlCapable = in_array($type, self::HTML_TYPES, true)
                || ($type === 'code' && $name === 'html');
            if ($htmlCapable && is_string($value)) {
                $value = $this->html->sanitize($value);
            }
            if ($type === 'repeater' && is_array($value)) {
                $fields = isset($control['fields']) && is_array($control['fields']) ? $control['fields'] : [];
                foreach ($value as $rowIndex => &$row) {
                    $rowPath = $this->appendIndex($settingPath, $rowIndex);
                    if (!is_array($row)) {
                        $errors[] = $this->error('wrong_type', $rowPath, 'Repeater rows must be objects.');
                        continue;
                    }
                    $this->validateControlSettings($row, $fields, $rowPath, $errors);
                }
                unset($row);
            }
        }
        unset($value);
    }

    private function rejectObjects(mixed $value, string $path, array &$errors): bool {
        if (is_object($value)) {
            $errors[] = $this->error('wrong_type', $path, 'Objects are not allowed in element data.');
            return true;
        }
        if (!is_array($value)) {
            return false;
        }
        $found = false;
        $stack = [[$value, $path]];
        while ($stack !== []) {
            [$current, $currentPath] = array_pop($stack);
            foreach ($current as $key => $nested) {
                $nestedPath = $this->appendKey($currentPath, $key);
                if (is_object($nested)) {
                    $errors[] = $this->error('wrong_type', $nestedPath, 'Objects are not allowed in element data.');
                    $found = true;
                } elseif (is_array($nested)) {
                    $stack[] = [$nested, $nestedPath];
                }
            }
        }
        return $found;
    }

    private function hasValidType(mixed $value, string $type): bool {
        if ($type === 'number') {
            return is_int($value) || is_float($value) || (is_string($value) && is_numeric($value));
        }
        if (in_array($type, self::STRING_TYPES, true)) {
            return is_string($value) || is_bool($value) || is_int($value) || is_float($value);
        }
        if ($type === 'select2') {
            return is_string($value) || is_array($value);
        }
        if (in_array($type, self::ARRAY_TYPES, true)) {
            return is_array($value);
        }
        return is_scalar($value) || is_array($value) || $value === null;
    }

    private function hasValidOption(mixed $value, array $options): bool {
        $allowed = array_map('strval', array_keys($options));
        $values = is_array($value) ? $value : [$value];
        foreach ($values as $candidate) {
            if (!is_scalar($candidate) || !in_array((string) $candidate, $allowed, true)) {
                return false;
            }
        }
        return true;
    }

    private function appendIndex(string $path, int|string $index): string {
        return is_int($index) ? $path . '[' . $index . ']' : $path . '[' . json_encode($index) . ']';
    }

    private function appendKey(string $path, int|string $key): string {
        return is_string($key) ? $path . '.' . $key : $path . '[' . $key . ']';
    }

    private function error(string $code, string $path, string $message): array {
        return ['code' => $code, 'path' => $path, 'message' => $message];
    }
}
