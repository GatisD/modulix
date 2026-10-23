<?php
namespace Modulix;

/**
 * Removes the Elementor Pro upsells that Elementor core and the Hello theme show when Pro is not installed
 * (project decision 2026-10-23: sites on Modulix show no Pro nags). ELEMENTOR_PRO_VERSION stays undefined on purpose:
 * Hello and other plugins call Elementor Pro classes when they see it.
 */
final class NoUpsell implements ModuleInterface {
    public const HELLO_BANNER_META = '_hello_elementor_install_notice';
    private const PROMOTIONS_NS = 'Elementor\\Modules\\Promotions\\';
    private const INFO_TRANSIENT = 'elementor_remote_info_api_data_';
    /** Hooks of the core Promotions module that stay: its control type is used by core controls. */
    private const KEEP_HOOKS = ['elementor/controls/register'];

    public function boot(): void {
        $this->unhook_core_promotions();

        // Locked "Pro" widget cards and the editor's promotionWidgets list both come from this cached API data.
        $info = self::INFO_TRANSIENT . ELEMENTOR_VERSION;
        add_filter("transient_$info", [self::class, 'strip_pro_widgets']);
        add_filter("pre_set_transient_$info", [self::class, 'strip_pro_widgets']);

        add_action('elementor/widgets/register', [$this, 'disable_widget_upsells'], 1000);
        add_action('elementor/element/after_section_end', [$this, 'remove_teasers'], 20, 2);
        add_action('elementor/editor/after_enqueue_styles', static function (): void {
            // Printed by core editor templates behind has_pro(); their filters cannot empty them.
            // .elementor-panel-editor-sticky-promotion: added in Elementor 4.x at the bottom of the element editing panel.
            wp_add_inline_style('elementor-editor', '#elementor-panel-get-pro-elements,#elementor-panel-get-pro-elements-sticky,'
                . '#elementor-navigator__footer__promotion,.elementor-panel-editor-sticky-promotion{display:none!important}');
        });

        add_filter('elementor/admin/dashboard_overview_widget/footer_actions', static function (array $actions): array {
            unset($actions['go-pro']);
            return $actions;
        });
        add_filter('plugin_action_links_' . ELEMENTOR_PLUGIN_BASE, static function (array $links): array {
            unset($links['go_pro']);
            return $links;
        });
        // Added by Elementor's wp-one package at admin_menu PHP_INT_MAX under a configurable parent;
        // the menu is printed after admin_head.
        add_action('admin_head', static function (): void {
            global $submenu;
            if (is_array($submenu)) {
                $submenu = self::remove_submenu_slug($submenu, 'elementor-one-upgrade');
            }
        });
        // "Install this integration" cards (e.g. Ally) are added to the editor config before this filter runs.
        add_filter('elementor/editor/localize_settings', [self::class, 'without_integration_promos']);

        add_filter('get_user_metadata', [self::class, 'hello_banner_dismissed'], 10, 4);
        add_filter('hello-plus-theme/rest/admin-config', [self::class, 'hello_admin_config']);
        add_filter('rest_post_dispatch', static function ($response, $server, $request) {
            if ($response instanceof \WP_REST_Response && str_ends_with((string) $request->get_route(), '/promotions')
                && str_contains((string) $request->get_route(), 'hello')) {
                $data = $response->get_data();
                if (is_array($data) && isset($data['links']) && is_array($data['links'])) {
                    $data['links'] = self::without_upsell_links($data['links']);
                    $response->set_data($data);
                }
            }
            return $response;
        }, 10, 3);
    }

    /** Removes every hook of the core Promotions module (menus, promo widgets, editor promo data, seasonal pointers). */
    private function unhook_core_promotions(): void {
        global $wp_filter;
        foreach ($wp_filter as $hook => $wp_hook) {
            if (in_array($hook, self::KEEP_HOOKS, true) || !$wp_hook instanceof \WP_Hook) {
                continue;
            }
            foreach ($wp_hook->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $entry) {
                    if (self::callback_in_namespace($entry['function'], self::PROMOTIONS_NS)) {
                        remove_filter($hook, $entry['function'], $priority);
                    }
                }
            }
        }
    }

    public function disable_widget_upsells(\Elementor\Widgets_Manager $widgets_manager): void {
        foreach (array_keys($widgets_manager->get_widget_types()) as $name) {
            add_filter("elementor/widgets/$name/custom_promotion", static function (array $data): array {
                $data['condition'] = false;
                return $data;
            });
        }
    }

    /** Runs after every section; removes Pro teaser sections and teaser controls core added to it. */
    public function remove_teasers(\Elementor\Controls_Stack $stack, string $section_id): void {
        $manager = \Elementor\Plugin::$instance->controls_manager;
        $controls = $manager->get_stacks($stack->get_unique_name())['controls'] ?? [];
        $remove = [];
        foreach ($controls as $name => $control) {
            $in_section = ($control['section'] ?? null) === $section_id || $name === $section_id;
            if (!$in_section) {
                continue;
            }
            if (self::is_teaser_control($control + ['name' => $name])) {
                $remove[] = $name;
            }
        }
        if (isset($controls[$section_id]) && in_array($section_id, $remove, true)) {
            foreach ($controls as $name => $control) {
                if (($control['section'] ?? null) === $section_id) {
                    $remove[] = $name;
                }
            }
        }
        if ($remove) {
            $manager->remove_control_from_stack($stack->get_unique_name(), array_values(array_unique($remove)));
        }
    }

    public static function without_integration_promos(array $env): array {
        if (isset($env['integrationWidgets'])) {
            $env['integrationWidgets'] = [];
        }
        return $env;
    }

    /** @param array<string,array<int,array<int,string>>> $submenu WordPress $submenu global */
    public static function remove_submenu_slug(array $submenu, string $slug): array {
        foreach ($submenu as $parent => $items) {
            $submenu[$parent] = array_values(array_filter($items, static fn(array $item): bool => ($item[2] ?? null) !== $slug));
        }
        return $submenu;
    }

    /** @param mixed $info */
    public static function strip_pro_widgets($info) {
        if (is_array($info) && isset($info['pro_widgets'])) {
            $info['pro_widgets'] = [];
        }
        return $info;
    }

    /** @param array{name:string,type?:string} $control */
    public static function is_teaser_control(array $control): bool {
        $name = $control['name'];
        $type = $control['type'] ?? '';
        if ($type === 'section') {
            return str_ends_with($name, '_pro');
        }
        if (!in_array($type, ['raw_html', 'promotion_control', 'divider'], true)) {
            return false;
        }
        return str_ends_with($name, '_pro') || str_contains($name, 'promotion');
    }

    /** @param array<int,array<string,mixed>> $links */
    public static function without_upsell_links(array $links): array {
        return array_values(array_filter($links, static fn(array $l): bool => ($l['type'] ?? '') !== 'go-pro' && empty($l['upgrade'])));
    }

    public static function hello_admin_config(array $config): array {
        foreach ($config['welcome']['buttons'] ?? [] as $button) {
            if (str_contains((string) ($button['link'] ?? ''), 'go.elementor.com')) {
                unset($config['welcome']);
                break;
            }
        }
        return $config;
    }

    /** get_user_metadata filter: Hello's "Go Pro" banner reads as dismissed for everyone. */
    public static function hello_banner_dismissed($value, $object_id, $meta_key, $single) {
        return $meta_key === self::HELLO_BANNER_META ? [true] : $value;
    }

    /** @param mixed $callback */
    public static function callback_in_namespace($callback, string $ns): bool {
        $owner = null;
        if ($callback instanceof \Closure) {
            $owner = (new \ReflectionFunction($callback))->getClosureThis();
        } elseif (is_array($callback) && isset($callback[0]) && is_object($callback[0])) {
            $owner = $callback[0];
        }
        return $owner !== null && str_starts_with(get_class($owner), $ns);
    }
}
