<?php
namespace Modulix;

interface ModuleInterface {
    /** Register widgets, documents, controls and hooks. Called once on elementor/init. */
    public function boot(): void;
}
