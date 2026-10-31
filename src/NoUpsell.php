<?php
namespace Modulix;

/**
 * Removes the Elementor Pro upsells that Elementor core and the Hello theme show when Pro is not installed
 * (project decision 2026-10-23: sites on Modulix show no Pro nags). ELEMENTOR_PRO_VERSION stays undefined on purpose:
 * Hello and other plugins call Elementor Pro classes when they see it.
 */
final class NoUpsell implements ModuleInterface {
    public const HELLO_BANNER_META = '_hello_elementor_install_notice';
    public const EDITOR_NOTICE_BAR_OPTION = '_elementor_editor_upgrade_notice_dismissed';
    public const KEEP_ELEMENTOR_AI_OPTION = 'modulix_keep_elementor_ai';
    public const AI_CSS = '#elementor-panel-category-custom-widgets,#elementor-panel-elements-widget-creation-area,'
        . '.elementor-panel-custom-widgets__cta,button[aria-label="Angie"]{display:none!important}';
    /** Keeps every template action local without pretending the site is connected to Elementor Cloud. */
    public const TEMPLATE_LIBRARY_JS = <<<'JS'
(() => {
    const dialogSelector = '#elementor-template-library-save-template-form,#elementor-template-library-save-template-variant-b';
    const transferContexts = new Set(['move', 'copy', 'bulkMove', 'bulkCopy']);
    const hide = element => {
        element.hidden = true;
        element.style?.setProperty('display', 'none', 'important');
    };
    const clean = root => {
        const dialogs = root.matches?.(dialogSelector) ? [root] : Array.from(root.querySelectorAll?.(dialogSelector) || []);
        dialogs.forEach(dialog => {
            const form = dialog.matches?.('form') ? dialog : dialog.querySelector('form');
            if (!form) return;
            const view = globalThis.elementor?.templates?.layout?.modalContent?.currentView;
            const context = view?.getOption?.('context') || 'save';
            const source = globalThis.elementor?.templates?.getFilter?.('source');
            const cloud = form.querySelector('input[name="cloud"]');
            const local = form.querySelector('input[name="local"]');
            if (cloud) {
                cloud.checked = false;
                cloud.disabled = true;
                const row = cloud.closest('.source-selections-input.cloud');
                if (row) hide(row);
            }
            form.querySelectorAll('.quota-cta,.upgrade-badge,.connect-badge,.cloud-folder-selection-dropdown')
                .forEach(hide);
            if ('save' === context) {
                if (local && !local.checked) local.click();
                return;
            }
            if (!transferContexts.has(context) || 'local' !== source) return;
            if (local) {
                local.checked = false;
                local.disabled = true;
                const row = local.closest('.source-selections-input.local');
                if (row) hide(row);
            }
            const submit = form.querySelector('#elementor-template-library-save-template-submit');
            if (submit) submit.disabled = true;
            if (!form.dataset?.modulixTransferBlocked) {
                if (form.dataset) form.dataset.modulixTransferBlocked = '1';
                form.addEventListener('submit', event => {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                }, true);
            }
        });
    };
    clean(document);
    new MutationObserver(records => records.forEach(record => record.addedNodes.forEach(node => {
        if (node.nodeType === Node.ELEMENT_NODE) clean(node);
    }))).observe(document.documentElement, { childList: true, subtree: true });
})();
JS;
    /** Core exposes no setting for these legacy/new onboarding promotion-only controls. */
    public const ONBOARDING_PROMOTION_JS = <<<'JS'
(() => {
    if (!globalThis.elementorAppConfig?.onboarding) return;
    const clean = root => {
        const legacySelector = '.e-onboarding__go-pro-cta,.e-onboarding__go-pro,.eps-button__go-pro-btn,'
            + '.e-onboarding__page-chooseFeatures .e-onboarding__footer .e-onboarding__button-action';
        const legacy = root.matches?.(legacySelector)
            ? [root]
            : Array.from(root.querySelectorAll?.(legacySelector) || []);
        legacy.forEach(element => { element.hidden = true; });

        const modernSelector = '[data-module="onboarding"]';
        const modern = root.matches?.(modernSelector)
            ? root
            : root.closest?.(modernSelector) || root.querySelector?.(modernSelector);
        if (!modern) return;
        const upgrade = modern.querySelector('header button');
        if (upgrade) upgrade.hidden = true;
        const featureStep = modern.querySelector('[data-testid="site-features-step"]');
        const firstCard = featureStep?.querySelector('[data-testid^="feature-card-"]');
        const grid = firstCard?.parentElement;
        Array.from(grid?.children || []).forEach(element => {
            if (!element.matches('[data-testid^="feature-card-"]')) element.hidden = true;
        });
    };
    clean(document);
    new MutationObserver(records => records.forEach(record => record.addedNodes.forEach(node => {
        if (node.nodeType === Node.ELEMENT_NODE) clean(node);
    }))).observe(document.documentElement, { childList: true, subtree: true });
})();
JS;
    private const PROMOTIONS_NS = 'Elementor\\Modules\\Promotions\\';
    private const NOTES_NS = 'Elementor\\Modules\\Notes\\';
    private const PRO_INSTALL_NS = 'Elementor\\Modules\\ProInstall\\';
    private const AI_NS = 'Elementor\\Modules\\Ai\\';
    private const WIDGET_CREATION_NS = 'Elementor\\Modules\\WidgetCreation\\';
    private const INFO_TRANSIENT = 'elementor_remote_info_api_data_';
    private const EDITOR_NOTICES_META = 'elementor_dismissed_editor_notices';
    private const ADMIN_NOTICES_META = 'elementor_admin_notices';
    /** Hooks of the core Promotions module that stay: its control type is used by core controls. */
    private const KEEP_HOOKS = ['elementor/controls/register'];
    /** Elementor 4 admin top bar: the upgrade button comes from Elementor One account data, not from a filter. */
    public const ADMIN_CSS = '[data-test="header-upgrade-button"]{display:none!important}';
    /** Elementor 4 admin sidebar: hasPro only hides its "Upgrade plan" button (editor-one-sidebar-navigation.js). */
    public const SIDEBAR_JS = 'if (window.editorOneSidebarConfig) { window.editorOneSidebarConfig.hasPro = true; }';
    /** Last-resort cleanup for promotion chips embedded directly in compiled editor packages with no PHP switch. */
    public const EDITOR_PROMOTION_JS = <<<'JS'
(() => {
    const hide = element => {
        element.hidden = true;
        element.style?.setProperty('display', 'none', 'important');
    };
    const isAuditPromotionAction = element => {
        const button = element.closest?.('button');
        if (!button || button !== element || !button.closest?.('[data-floating-panel="audit-panel"]')) return false;
        const card = button.parentElement;
        const list = card?.parentElement;
        return card?.lastElementChild === button
            && Array.from(card?.children || []).length === 3
            && Array.from(list?.children || []).includes(card)
            && !!list?.previousElementSibling;
    };
    const clean = root => {
        const promotionSelector = 'a[href*="go.elementor.com/go-pro-interactions-"],a[href*="go.elementor.com/go-pro-manager-"],'
            + 'a[href*="go.elementor.com/renew-license-manager-"],a[href*="go.elementor.com/go-pro-components"],'
            + 'a[href*="go.elementor.com/go-pro-dynamic-tags-"],a[href*="go.elementor.com/renew-license-panel-"][href*="-variable"],'
            + 'a[href*="go.elementor.com/go-pro-panel-size-variable"],.e-promotion-react-wrapper';
        const promotions = root.matches?.(promotionSelector) ? [root] : [];
        promotions.push(...Array.from(root.querySelectorAll?.(promotionSelector) || []));
        promotions.forEach(element => {
            hide(element.closest('[role="status"],[role="menuitem"],.MuiAlert-root,.MuiStack-root,button,li') || element);
        });
        const textElements = root.matches?.('a,button,span,div') ? [root] : [];
        textElements.push(...Array.from(root.querySelectorAll?.('a,button,span,div') || []));
        textElements.forEach(element => {
            const own = Array.from(element.childNodes).filter(node => node.nodeType === Node.TEXT_NODE)
                .map(node => node.textContent).join(' ').trim();
            if (isAuditPromotionAction(element) || /^(?:PRO features|PRO effects|PRO triggers)$/i.test(own)) {
                hide(element.closest('[role="menuitem"],button,[class*="MuiChip-root"]') || element);
            }
        });
    };
    clean(document);
    new MutationObserver(records => records.forEach(record => record.addedNodes.forEach(node => {
        if (node.nodeType === Node.ELEMENT_NODE) clean(node.matches?.('a,button,span,div') ? node.parentElement || node : node);
    }))).observe(document.documentElement, { childList: true, subtree: true });
})();
JS;

    /** Must run while plugin files load, before Elementor constructs its AI module. */
    public static function registerEarly(): void {
        add_filter('get_user_option_elementor_enable_ai', [self::class, 'filter_elementor_ai_user_option']);
    }

    public function boot(): void {
        $namespaces = [self::PROMOTIONS_NS, self::NOTES_NS, self::PRO_INSTALL_NS];
        if (!self::keep_elementor_ai()) {
            $namespaces[] = self::AI_NS;
            $namespaces[] = self::WIDGET_CREATION_NS;
        }
        $this->unhook_namespaces($namespaces);

        // Locked "Pro" widget cards and the editor's promotionWidgets list both come from this cached API data.
        $info = self::INFO_TRANSIENT . ELEMENTOR_VERSION;
        add_filter("transient_$info", [self::class, 'strip_pro_widgets']);
        add_filter("pre_set_transient_$info", [self::class, 'strip_pro_widgets']);

        add_action('elementor/widgets/register', [$this, 'disable_widget_upsells'], 1000);
        add_action('elementor/elements/elements_registered', [$this, 'remove_atomic_promotion_elements'], 1000);
        add_action('elementor/editor/after_enqueue_styles', [self::class, 'remove_atomic_promotion_styles'], 1000);
        add_action('elementor/preview/enqueue_styles', [self::class, 'remove_atomic_promotion_styles'], 1000);
        add_action('elementor/element/after_section_end', [$this, 'remove_teasers'], 20, 2);
        add_action('elementor/editor/after_enqueue_styles', static function (): void {
            // Printed by core editor templates behind has_pro(); their filters cannot empty them.
            // .elementor-panel-editor-sticky-promotion: added in Elementor 4.x at the bottom of the element editing panel.
            $css = '#elementor-panel-get-pro-elements,#elementor-panel-get-pro-elements-sticky,'
                . '#elementor-navigator__footer__promotion,.elementor-panel-editor-sticky-promotion,'
                . '#elementor-panel-page-global .elementor-nerd-box{display:none!important}';
            if (!self::keep_elementor_ai()) {
                $css .= self::AI_CSS;
            }
            wp_add_inline_style('elementor-editor', $css);
            wp_add_inline_script('elementor-editor', self::EDITOR_PROMOTION_JS, 'after');
            wp_add_inline_script('elementor-editor', self::TEMPLATE_LIBRARY_JS, 'after');
        });
        add_filter('elementor/editor/panel/get_pro_details', static function (array $details): array {
            $details['show_banner'] = false;
            return $details;
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
        add_action('admin_head', static function (): void {
            echo '<style id="modulix-no-upsell">' . self::ADMIN_CSS . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constant
        });
        add_action('admin_enqueue_scripts', static function (): void {
            wp_add_inline_script('editor-one-sidebar-navigation', self::SIDEBAR_JS, 'before');
            wp_dequeue_script('elementor-mcp-promotion-registrar');
            wp_deregister_script('elementor-mcp-promotion-registrar');
        }, 100);
        // "Install this integration" cards (e.g. Ally) are added to the editor config before this filter runs.
        add_filter('elementor/editor/localize_settings', [self::class, 'without_integration_promos']);
        add_filter('elementor/document/config', [self::class, 'without_document_promotions']);
        add_filter('elementor/core/admin/homescreen', [self::class, 'without_home_promotions']);
        add_filter('elementor/checklist/steps', [self::class, 'without_checklist_promotions']);
        add_action('elementor/init', [self::class, 'sanitize_onboarding_app_settings'], 1000);
        add_action('wp_print_footer_scripts', static function (): void {
            wp_add_inline_script('elementor-app', self::ONBOARDING_PROMOTION_JS, 'after');
        }, 0);

        add_filter('get_user_metadata', [self::class, 'hello_banner_dismissed'], 10, 4);
        add_filter('get_user_metadata', [self::class, 'dismissed_elementor_user_metadata'], 10, 4);
        // Elementor's editor notice bar ("Upgrade Now") stays hidden: its dismissal time always reads as now.
        add_filter('pre_option_' . self::EDITOR_NOTICE_BAR_OPTION, static fn($value) => self::editor_notice_bar_dismissed($value, time()));
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

    /** Removes hooks owned by modules that only expose unavailable services or promotions in this configuration. */
    private function unhook_namespaces(array $namespaces): void {
        global $wp_filter;
        foreach ($wp_filter as $hook => $wp_hook) {
            if (in_array($hook, self::KEEP_HOOKS, true) || !$wp_hook instanceof \WP_Hook) {
                continue;
            }
            foreach ($wp_hook->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $entry) {
                    if (array_filter($namespaces, static fn(string $ns): bool => self::callback_in_namespace($entry['function'], $ns))) {
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

    public function remove_atomic_promotion_elements(\Elementor\Elements_Manager $elements_manager): void {
        foreach (self::atomic_promotion_element_names($elements_manager->get_element_types()) as $name) {
            $elements_manager->unregister_element_type($name);
        }
    }

    /** Runs after the editor and preview hooks where Atomic Widgets enqueues its promotion assets. */
    public static function remove_atomic_promotion_styles(): void {
        foreach (['elementor-atomic-widgets-promotion', 'elementor-atomic-widgets-promotion-fonts'] as $handle) {
            wp_dequeue_style($handle);
            wp_deregister_style($handle);
        }
    }

    /** @param array<string,object> $elements */
    public static function atomic_promotion_element_names(array $elements): array {
        $names = [];
        foreach ($elements as $name => $element) {
            if (method_exists($element, 'get_meta_item') && $element->get_meta_item('is_pro_promotion')) {
                $names[] = (string) $name;
            }
        }
        return $names;
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
        return self::without_editor_promotions($env, self::keep_elementor_ai());
    }

    /** Removes upgrade data after Elementor has assembled the editor document and category configuration. */
    public static function without_document_promotions(array $config): array {
        return self::without_editor_promotions($config, self::keep_elementor_ai());
    }

    public static function without_editor_promotions(array $env, bool $keep_ai): array {
        foreach (['promotion', 'promotions', 'atomicWidgetPromotions', 'v4Promotions', 'editingPanelStickyPromotion',
                  'elementPromotionURL', 'dynamicPromotionURL'] as $key) {
            unset($env[$key]);
        }
        if (isset($env['icons']) && is_array($env['icons'])) {
            unset($env['icons']['goProURL']);
        }
        if (isset($env['initial_document']['panel']['elements_categories'])
            && is_array($env['initial_document']['panel']['elements_categories'])) {
            $categories =& $env['initial_document']['panel']['elements_categories'];
            foreach ($categories as &$category) {
                if (is_array($category)) {
                    unset($category['promotion']);
                }
            }
            unset($category);
        }
        if (!$keep_ai) {
            unset($env['angie'], $env['ai'], $env['widgetCreation']);
        }
        return $env;
    }

    /** Keep Elementor AI is opt-in; when enabled, preserve Elementor's own per-user preference unchanged. */
    public static function elementor_ai_user_option($value, bool $keep) {
        return $keep ? $value : '0';
    }

    public static function filter_elementor_ai_user_option($value) {
        return self::elementor_ai_user_option($value, self::keep_elementor_ai());
    }

    public static function keep_elementor_ai(): bool {
        return function_exists('get_option') && get_option(self::KEEP_ELEMENTOR_AI_OPTION, '0') === '1';
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
        return str_ends_with($name, '_pro')
            || str_contains($name, 'teaser')
            || str_contains($name, 'promotion')
            || in_array($name, ['site_mailer_promo', 'site_mailer_promo_2'], true);
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

    /** pre_option filter value for Elementor's editor notice bar: dismissed at $now, so it is never shown. */
    public static function editor_notice_bar_dismissed($value, int $now): int {
        return $now;
    }

    public static function dismissed_editor_notices($stored): array {
        $notices = is_array($stored) ? $stored : [];
        return array_values(array_unique(array_merge($notices, [
            'image_optimizer_hint',
            'image-optimization-once',
            'image-optimization-once-media-modal',
            'image-optimization-media-modal',
            'ally_heading_notice',
        ])));
    }

    public static function dismissed_admin_notices($stored): array {
        $notices = is_array($stored) ? $stored : [];
        foreach (['plugin_image_optimization', 'role_manager_promote', 'ally_pages_promotion'] as $notice) {
            $notices[$notice] = ['is_viewed' => true, 'meta' => []];
        }
        return $notices;
    }

    /** Makes Image Optimizer cross-sells read as dismissed without writing user metadata. */
    public static function dismissed_elementor_user_metadata($value, $object_id, $meta_key, $single) {
        if ($value !== null || !in_array($meta_key, [self::EDITOR_NOTICES_META, self::ADMIN_NOTICES_META], true)) {
            return $value;
        }
        remove_filter('get_user_metadata', [self::class, __FUNCTION__], 10);
        $stored = get_user_meta((int) $object_id, $meta_key, true);
        add_filter('get_user_metadata', [self::class, __FUNCTION__], 10, 4);
        $dismissed = $meta_key === self::EDITOR_NOTICES_META
            ? self::dismissed_editor_notices($stored)
            : self::dismissed_admin_notices($stored);
        return $single ? $dismissed : [$dismissed];
    }

    /** Removes server-provided home and onboarding entries whose payload itself is an upgrade. */
    public static function without_home_promotions(array $data): array {
        foreach (array_keys($data) as $key) {
            if (in_array((string) $key, ['sidebar_promotion_variants', 'promotion', 'promotion_data', 'upgrade_url'], true)) {
                unset($data[$key]);
                continue;
            }
            if (!is_array($data[$key])) {
                continue;
            }
            $item = $data[$key];
            $haystack = strtolower((string) ($item['title'] ?? '') . ' ' . (string) ($item['url'] ?? '') . ' '
                . (string) ($item['cta_url'] ?? ''));
            if (preg_match('/upgrade|go-pro|get-pro|elementor-pro/', $haystack)) {
                unset($data[$key]);
                continue;
            }
            $data[$key] = self::without_home_promotions($item);
        }
        return array_is_list($data) ? array_values($data) : $data;
    }

    /** Neutralizes only onboarding promotion destinations; useful setup steps and choices stay intact. */
    public static function without_onboarding_promotions(array $settings): array {
        if (isset($settings['urls']) && is_array($settings['urls'])) {
            foreach (['upgrade', 'uploadPro', 'comparePlans', 'upgradeUrl'] as $key) {
                if (array_key_exists($key, $settings['urls'])) {
                    $settings['urls'][$key] = '';
                }
            }
        }
        return $settings;
    }

    /** Runs after either Elementor onboarding module publishes its app settings at priority 12. */
    public static function sanitize_onboarding_app_settings(): void {
        if (!class_exists('Elementor\\Plugin') || !isset(\Elementor\Plugin::$instance->app)) {
            return;
        }
        $app = \Elementor\Plugin::$instance->app;
        $settings = $app->get_settings('onboarding');
        if (is_array($settings)) {
            $app->set_settings('onboarding', self::without_onboarding_promotions($settings));
        }
    }

    /** Clears the checklist header's optional promotion while retaining the useful setup step. */
    public static function without_checklist_promotions(array $steps): array {
        foreach ($steps as $step) {
            if (!is_object($step)) {
                continue;
            }
            $class = new \ReflectionClass($step);
            while ($class && !$class->hasProperty('promotion_data')) {
                $class = $class->getParentClass();
            }
            if ($class) {
                $property = $class->getProperty('promotion_data');
                $property->setValue($step, null);
            }
        }
        return $steps;
    }

    /** get_user_metadata filter: Hello's "Go Pro" banner reads as dismissed for everyone. */
    public static function hello_banner_dismissed($value, $object_id, $meta_key, $single) {
        return $meta_key === self::HELLO_BANNER_META ? [true] : $value;
    }

    /** @param mixed $callback */
    public static function callback_in_namespace($callback, string $ns): bool {
        $owner = null;
        if ($callback instanceof \Closure) {
            $reflection = new \ReflectionFunction($callback);
            $owner = $reflection->getClosureThis();
            if ($owner === null && $reflection->getClosureScopeClass()) {
                return str_starts_with($reflection->getClosureScopeClass()->getName(), $ns);
            }
        } elseif (is_array($callback) && isset($callback[0]) && is_object($callback[0])) {
            $owner = $callback[0];
        }
        return $owner !== null && str_starts_with(get_class($owner), $ns);
    }
}
