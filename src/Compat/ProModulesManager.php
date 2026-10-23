<?php
namespace Modulix\Compat;

/** Answers ElementorPro\Plugin::instance()->modules_manager->get_modules() with Modulix's modules. */
final class ProModulesManager {
    /** Pro module name => Modulix module class; ProModulesManagerTest keeps it in step with Plugin::registry(). */
    public const MODULES = [
        'dynamic-tags' => \Modulix\Modules\DynamicTags\Module::class,
        'theme-builder' => \Modulix\Modules\ThemeBuilder\Module::class,
        'custom-css' => \Modulix\Modules\CustomCss\Module::class,
        'sticky' => \Modulix\Modules\Sticky\Module::class,
        'motion-fx' => \Modulix\Modules\MotionFx\Module::class,
        'nav-menu' => \Modulix\Modules\NavMenu\Module::class,
        'floating-buttons' => \Modulix\Modules\FloatingButtons\Module::class,
        'gallery' => \Modulix\Modules\Gallery\Module::class,
        'library' => \Modulix\Modules\Library\Module::class,
        'query-control' => \Modulix\Modules\QueryControl\Module::class,
        'posts' => \Modulix\Modules\Posts\Module::class,
    ];

    /** Like Elementor Pro: one module by name, all active modules without a name, null when unknown or inactive. */
    public function get_modules(?string $name = null): object|array|null {
        if ($name === null) {
            $all = [];
            foreach (array_keys(self::MODULES) as $id) {
                $module = $this->get_modules($id);
                if ($module !== null) {
                    $all[$id] = $module;
                }
            }
            return $all;
        }
        $class = self::MODULES[$name] ?? null;
        if ($class === null || !class_exists($class)) {
            return null;
        }
        if (method_exists($class, 'is_active') && !$class::is_active()) {
            return null;
        }
        return $class::instance();
    }
}
