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
        'animated-headline' => \Modulix\Modules\AnimatedHeadline\Module::class,
        'flip-box' => \Modulix\Modules\FlipBox\Module::class,
        'call-to-action' => \Modulix\Modules\CallToAction\Module::class,
        'pricing' => \Modulix\Modules\Pricing\Module::class,
        'countdown' => \Modulix\Modules\Countdown\Module::class,
        'share-buttons' => \Modulix\Modules\ShareButtons\Module::class,
        'blockquote' => \Modulix\Modules\Blockquote\Module::class,
        'table-of-contents' => \Modulix\Modules\TableOfContents\Module::class,
        'hotspot' => \Modulix\Modules\Hotspot\Module::class,
        'off-canvas' => \Modulix\Modules\OffCanvas\Module::class,
        'progress-tracker' => \Modulix\Modules\ProgressTracker\Module::class,
        'code-highlight' => \Modulix\Modules\CodeHighlight\Module::class,
        'slides' => \Modulix\Modules\Slides\Module::class,
        'video-playlist' => \Modulix\Modules\VideoPlaylist\Module::class,
        'social' => \Modulix\Modules\Social\Module::class,
        'mega-menu' => \Modulix\Modules\MegaMenu\Module::class,
        'lottie' => \Modulix\Modules\Lottie\Module::class,
        'link-in-bio' => \Modulix\Modules\LinkInBio\Module::class,
        'nested-carousel' => \Modulix\Modules\NestedCarousel\Module::class,
        'carousel' => \Modulix\Modules\Carousel\Module::class,
        'page-transitions' => \Modulix\Modules\PageTransitions\Module::class,
        'scroll-snap' => \Modulix\Modules\ScrollSnap\Module::class,
        'custom-attributes' => \Modulix\Modules\CustomAttributes\Module::class,
        'attributes' => \Modulix\Modules\Attributes\Module::class,
        'role-manager' => \Modulix\Modules\RoleManager\Module::class,
        'element-manager' => \Modulix\Modules\ElementManager\Module::class,
        'custom-code' => \Modulix\Modules\CustomCode\Module::class,
        'global-widget' => \Modulix\Modules\GlobalWidget\Module::class,
        'theme-elements' => \Modulix\Modules\ThemeElements\Module::class,
        'loop-builder' => \Modulix\Modules\LoopBuilder\Module::class,
        'loop-filter' => \Modulix\Modules\LoopFilter\Module::class,
        'search' => \Modulix\Modules\Search\Module::class,
        'popup' => \Modulix\Modules\Popup\Module::class,
        'forms' => \Modulix\Modules\Forms\Module::class,
        'wp-cli' => \Modulix\Modules\WpCli\Module::class,
        'assets-manager' => \Modulix\Modules\AssetsManager\Module::class,
        'woocommerce' => \Modulix\Modules\Woocommerce\Module::class,
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
