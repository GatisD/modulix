<?php
namespace Modulix\Mcp;

final class ElementorSchemaRegistry implements SchemaRegistry {
    public function __construct(private ?WidgetSchemaMapper $mapper = null, private ?object $plugin = null) {
        $this->mapper ??= new WidgetSchemaMapper();
    }

    public function schema(string $elementType): ?array {
        $plugin = $this->plugin;
        if ($plugin === null && class_exists('Elementor\\Plugin')) {
            $plugin = \Elementor\Plugin::$instance ?? null;
        }
        if ($plugin === null) {
            return null;
        }
        $element = $plugin->widgets_manager->get_widget_types($elementType);
        $kind = 'widget';
        if (!$element) {
            $element = $plugin->elements_manager->get_element_types($elementType);
            $kind = $elementType;
        }
        if (!$element || !method_exists($element, 'get_controls')) {
            return null;
        }
        return $this->mapper->map($elementType, $kind, (array) $element->get_controls());
    }
}
