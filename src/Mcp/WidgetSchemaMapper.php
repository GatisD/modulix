<?php
namespace Modulix\Mcp;

final class WidgetSchemaMapper {
    private const NON_SETTING_TYPES = ['section', 'tab', 'tabs', 'divider', 'heading', 'raw_html', 'button'];

    public function map(string $name, string $elementType, array $controls): array {
        $mapped = [];
        foreach ($controls as $controlName => $control) {
            if (!is_array($control)) {
                continue;
            }
            $type = (string) ($control['type'] ?? '');
            if ($type === '' || in_array($type, self::NON_SETTING_TYPES, true)) {
                continue;
            }
            $item = [
                'name' => (string) $controlName,
                'type' => $type,
                'options' => is_array($control['options'] ?? null) ? $control['options'] : [],
                'default' => $control['default'] ?? null,
            ];
            if (!empty($control['responsive'])) {
                $item['responsive'] = true;
            }
            if ('repeater' === $type && is_array($control['fields'] ?? null)) {
                $item['fields'] = $this->mapFields($control['fields']);
            }
            $mapped[] = $item;
        }
        return [
            'name' => $name,
            'type' => $elementType,
            'controls' => $mapped,
        ];
    }

    private function mapFields(array $fields): array {
        $mapped = [];
        foreach ($fields as $name => $field) {
            if (!is_array($field)) {
                continue;
            }
            $type = (string) ($field['type'] ?? '');
            if ($type === '' || in_array($type, self::NON_SETTING_TYPES, true)) {
                continue;
            }
            $mapped[] = [
                'name' => (string) $name,
                'type' => $type,
                'options' => is_array($field['options'] ?? null) ? $field['options'] : [],
                'default' => $field['default'] ?? null,
            ];
        }
        return $mapped;
    }
}
