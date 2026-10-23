<?php
namespace Modulix;

final class Plugin {
    public static function registry(): ModuleRegistry {
        $registry = new ModuleRegistry();
        $registry->add('dynamic-tags', fn() => new ElementorModule(\Modulix\Modules\DynamicTags\Module::class));
        $registry->add('theme-builder', fn() => new ElementorModule(\Modulix\Modules\ThemeBuilder\Module::class));
        $registry->add('custom-css', fn() => new ElementorModule(\Modulix\Modules\CustomCss\Module::class));
        $registry->add('sticky', fn() => new ElementorModule(\Modulix\Modules\Sticky\Module::class));
        $registry->add('motion-fx', fn() => new ElementorModule(\Modulix\Modules\MotionFx\Module::class));
        $registry->add('nav-menu', fn() => new ElementorModule(\Modulix\Modules\NavMenu\Module::class));
        $registry->add('floating-buttons', fn() => new ElementorModule(\Modulix\Modules\FloatingButtons\Module::class));
        $registry->add('gallery', fn() => new ElementorModule(\Modulix\Modules\Gallery\Module::class));
        $registry->add('library', fn() => new ElementorModule(\Modulix\Modules\Library\Module::class));
        $registry->add('query-control', fn() => new ElementorModule(\Modulix\Modules\QueryControl\Module::class));
        $registry->add('posts', fn() => new ElementorModule(\Modulix\Modules\Posts\Module::class));
        $registry->add('editor', fn() => new Editor());
        $registry->add('frontend', fn() => new Frontend());
        $registry->add('no-upsell', fn() => new NoUpsell());
        return $registry;
    }

    /** Lifted code calls Plugin::elementor() the way Elementor Pro does. */
    public static function elementor(): \Elementor\Plugin {
        return \Elementor\Plugin::$instance;
    }
}
