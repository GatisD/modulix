<?php
namespace Modulix;

/** Boots a lifted Elementor-style module: its constructor (via instance()) registers its hooks. */
final class ElementorModule implements ModuleInterface {
    /** @param class-string $class */
    public function __construct(private string $class) {}

    public function boot(): void {
        // Same rule as Elementor's modules manager: a module that reports itself inactive is not loaded.
        if (method_exists($this->class, 'is_active') && !($this->class)::is_active()) {
            return;
        }
        ($this->class)::instance();
    }
}
