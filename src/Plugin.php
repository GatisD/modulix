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
        $registry->add('animated-headline', fn() => new ElementorModule(\Modulix\Modules\AnimatedHeadline\Module::class));
        $registry->add('flip-box', fn() => new ElementorModule(\Modulix\Modules\FlipBox\Module::class));
        $registry->add('call-to-action', fn() => new ElementorModule(\Modulix\Modules\CallToAction\Module::class));
        $registry->add('pricing', fn() => new ElementorModule(\Modulix\Modules\Pricing\Module::class));
        $registry->add('countdown', fn() => new ElementorModule(\Modulix\Modules\Countdown\Module::class));
        $registry->add('share-buttons', fn() => new ElementorModule(\Modulix\Modules\ShareButtons\Module::class));
        $registry->add('blockquote', fn() => new ElementorModule(\Modulix\Modules\Blockquote\Module::class));
        $registry->add('table-of-contents', fn() => new ElementorModule(\Modulix\Modules\TableOfContents\Module::class));
        $registry->add('hotspot', fn() => new ElementorModule(\Modulix\Modules\Hotspot\Module::class));
        $registry->add('off-canvas', fn() => new ElementorModule(\Modulix\Modules\OffCanvas\Module::class));
        $registry->add('progress-tracker', fn() => new ElementorModule(\Modulix\Modules\ProgressTracker\Module::class));
        $registry->add('code-highlight', fn() => new ElementorModule(\Modulix\Modules\CodeHighlight\Module::class));
        $registry->add('slides', fn() => new ElementorModule(\Modulix\Modules\Slides\Module::class));
        $registry->add('video-playlist', fn() => new ElementorModule(\Modulix\Modules\VideoPlaylist\Module::class));
        $registry->add('social', fn() => new ElementorModule(\Modulix\Modules\Social\Module::class));
        $registry->add('mega-menu', fn() => new ElementorModule(\Modulix\Modules\MegaMenu\Module::class));
        $registry->add('lottie', fn() => new ElementorModule(\Modulix\Modules\Lottie\Module::class));
        $registry->add('link-in-bio', fn() => new ElementorModule(\Modulix\Modules\LinkInBio\Module::class));
        $registry->add('nested-carousel', fn() => new ElementorModule(\Modulix\Modules\NestedCarousel\Module::class));
        $registry->add('carousel', fn() => new ElementorModule(\Modulix\Modules\Carousel\Module::class));
        $registry->add('page-transitions', fn() => new ElementorModule(\Modulix\Modules\PageTransitions\Module::class));
        $registry->add('scroll-snap', fn() => new ElementorModule(\Modulix\Modules\ScrollSnap\Module::class));
        $registry->add('custom-attributes', fn() => new ElementorModule(\Modulix\Modules\CustomAttributes\Module::class));
        $registry->add('attributes', fn() => new ElementorModule(\Modulix\Modules\Attributes\Module::class));
        $registry->add('role-manager', fn() => new ElementorModule(\Modulix\Modules\RoleManager\Module::class));
        $registry->add('element-manager', fn() => new ElementorModule(\Modulix\Modules\ElementManager\Module::class));
        $registry->add('custom-code', fn() => new ElementorModule(\Modulix\Modules\CustomCode\Module::class));
        $registry->add('global-widget', fn() => new ElementorModule(\Modulix\Modules\GlobalWidget\Module::class));
        $registry->add('theme-elements', fn() => new ElementorModule(\Modulix\Modules\ThemeElements\Module::class));
        $registry->add('loop-builder', fn() => new ElementorModule(\Modulix\Modules\LoopBuilder\Module::class));
        $registry->add('loop-filter', fn() => new ElementorModule(\Modulix\Modules\LoopFilter\Module::class));
        $registry->add('search', fn() => new ElementorModule(\Modulix\Modules\Search\Module::class));
        $registry->add('popup', fn() => new ElementorModule(\Modulix\Modules\Popup\Module::class));
        $registry->add('forms', fn() => new ElementorModule(\Modulix\Modules\Forms\Module::class));
        $registry->add('wp-cli', fn() => new ElementorModule(\Modulix\Modules\WpCli\Module::class));
        $registry->add('assets-manager', fn() => new ElementorModule(\Modulix\Modules\AssetsManager\Module::class));
        $registry->add('woocommerce', fn() => new ElementorModule(\Modulix\Modules\Woocommerce\Module::class));
        $registry->add('editor', fn() => new Editor());
        $registry->add('frontend', fn() => new Frontend());
        $registry->add('no-upsell', fn() => new NoUpsell());
        return $registry;
    }

    /** Lifted code calls Plugin::instance()->modules_manager the way Elementor Pro does; the Pro shim answers it. */
    public static function instance(): \Modulix\Compat\ProPluginShim {
        return \Modulix\Compat\ProPluginShim::instance();
    }

    /** Lifted modules register their widget CSS through Plugin::get_frontend_file_url() as in Elementor Pro. */
    public static function get_frontend_file_url(string $frontend_file_name, bool $custom_file): string {
        return Frontend::get_frontend_file_url($frontend_file_name, $custom_file);
    }

    public static function get_frontend_file_path(string $frontend_file_name, bool $custom_file): string {
        return Frontend::get_frontend_file_path($frontend_file_name, $custom_file);
    }

    public static function get_title(): string {
        return 'Modulix';
    }

    /** Lifted code calls Plugin::elementor() the way Elementor Pro does. */
    public static function elementor(): \Elementor\Plugin {
        return \Elementor\Plugin::$instance;
    }
}
