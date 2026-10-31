# Theme and plugin compatibility

This page records static compatibility checks against specific releases. A static check follows references to
Elementor Pro classes, constants, hooks, plugin basenames, asset handles and JavaScript globals through Modulix's
compatibility layer. It does not replace a WordPress runtime test.

Modulix deliberately does not define `ELEMENTOR_PRO_VERSION`. A theme or plugin that uses only that constant or
the `elementor-pro/elementor-pro.php` plugin basename to enable an integration will take its fallback path.

## Results

| Step | Theme or plugin | Version checked | Result | Notes |
|---|---|---:|---|---|
| X1 | Hello Elementor | 3.5.1 | works with notes | Theme locations use Elementor's public location API and work. Pro-only shortcuts stay hidden and Pro upsells remain visible because the Pro version constant and loader function are absent. |
| X2 | Astra | 4.14.1 | works with notes | Theme Builder integration resolves through the class aliases. CSS and WooCommerce adjustments guarded only by the Pro version constant are not enabled. |
| X3 | Woodmart | unavailable | not checked: paid | No source package was available for a static check. |
| X4 | Rank Math SEO | 1.0.280 | works with notes | The Elementor integration does not require Pro. Rank Math does not automatically recognize Modulix's Table of Contents widget because its check uses the Pro version constant. |
| X5 | WPML | unavailable | not checked: paid | No source package was available for a static check. |
| X6 | Connect Polylang for Elementor | 2.5.5 | works with notes | Theme Builder classes, module access and the Site Editor filter are provided. Two admin-only condition synchronization features stay disabled because their gate uses the Pro version constant. The retired Elementor Library WordPress widget is not provided. |
| X7 | ECS - Ele Custom Skin for Elementor | 4.3.14 | works with notes | Current Loop Builder and custom-layout modules resolve through class aliases. The deprecated legacy module is not supported because it requires the real `elementor-pro.php` plugin basename before registering its Pro hooks. |
| X8 | Essential Addons for Elementor | 6.8.7 | works | Its Theme Builder class checks, module lookup and location methods are provided by Modulix. |
| X9 | Sticky Header Effects for Elementor | 2.2.3 | works | Pro references are limited to marketplace discovery and a telemetry version field. They do not gate the free plugin's frontend behavior. |
| X10 | YITH WooCommerce Dynamic Pricing and Discounts | unavailable | not checked: paid | No source package was available for a static check. |
| X11 | Advanced AJAX Product Filters | 3.3 | not supported | Its Elementor widget and Loop Grid integration file loads only when `ELEMENTOR_PRO_VERSION` is defined, so the integration stays disabled. The plugin's non-Elementor filtering paths are outside this check. |

## Static reference inventory

Paths below are relative to the checked theme or plugin. Translation text domains, comments and CSS class names are
identified separately because they do not call or detect Elementor Pro.

### X1 - Hello Elementor 3.5.1

- `functions.php:169` is a type-only docblock reference to the Theme Builder locations manager. The runtime callback
  receives Modulix's compatible locations manager through Elementor's public hook.
- `includes/customizer-functions.php:49` checks for the global Pro loader function. It remains false, so only the
  Customizer upsell is shown.
- `includes/utils.php:15`, `includes/settings/settings-footer.php:736` and
  `includes/settings/settings-header.php:770` check `ELEMENTOR_PRO_VERSION`. They take their non-Pro UI paths.

### X2 - Astra 4.14.1

- `inc/compatibility/class-astra-elementor-pro.php:13`, `:17` and `:20` use the Theme Builder module class and
  namespace. `ProAliases` maps the module class; Astra defines its own compatibility class under the Pro namespace.
- `inc/extras.php:985` checks and compares `ELEMENTOR_PRO_VERSION`. The helper returns false.
- `inc/compatibility/class-astra-elementor-pro.php:85` and `:156` consume that helper, so the Pro-specific checkout
  editor adjustment and WooCommerce compatibility CSS stay disabled.
- `inc/class-astra-dynamic-css.php:2299`, `:2309` and `:2572` guard Loop Item width and legacy spacing CSS with the
  Pro version constant. Those additions stay disabled.
- `inc/compatibility/class-astra-elementor.php:520` guards Pro mini-cart CSS with the version constant. It stays
  disabled.
- `inc/theme-update/astra-update-functions.php:976` and `:990` store two Pro migration flags as false.
- `functions.php:193` loads Astra's local `class-astra-elementor-pro.php` compatibility file. This is a local filename,
  not an Elementor Pro plugin include.

### X3 - Woodmart

- Not checked because no source package was available.

### X4 - Rank Math SEO 1.0.280

- `includes/admin/metabox/class-post-screen.php:362` treats `ELEMENTOR_PRO_VERSION` as proof that a Table of Contents
  provider exists. The constant stays undefined, so Rank Math's content analysis does not infer a provider from
  Modulix.

### X5 - WPML

- Not checked because no source package was available.

### X6 - Connect Polylang for Elementor 2.5.5

- `includes/functions.php:40` and `:42` implement the Pro-active check with `ELEMENTOR_PRO_VERSION`. It returns false.
- `includes/connect-plugins.php:69` uses that check. The language-term condition-cache refresh and the translated
  template condition columns registered in that branch stay disabled.
- `includes/connect-plugins.php:63` keeps the existing `elementor_pro_theme_builder_conditions` option clean on save.
  Modulix reads those stored Theme Builder conditions.
- `includes/connect-plugins.php:104` uses `elementor-pro/site-editor/data/template`. Modulix applies that filter.
- `includes/connect-plugins.php:284` checks the old
  `ElementorPro\Modules\Library\WP_Widgets\Elementor_Library` class. Modulix does not provide that retired widget, so
  the `is_a()` check safely returns false.
- `includes/connect-plugins.php:401`, `:462` and `:828` call
  `ElementorPro\Plugin::instance()->modules_manager->get_modules( 'theme-builder' )`. `ProPluginShim` and
  `ProModulesManager` return Modulix's Theme Builder module, including the called conditions-manager methods.
- `includes/connect-plugins.php:466` and `:832` use `elementor-pro` only as a translation text domain.

### X7 - ECS - Ele Custom Skin for Elementor 4.3.14

- `modules/loop-custom-layout/class-ecs-loop-custom-layout-module.php:50` checks the Loop Builder module class.
  Modulix provides it through `ProAliases`.
- `modules/container-layout/class-ecs-custom-layout-document.php:27` and `:30` check and extend the Theme Builder
  document class. Modulix provides that class through `ProAliases`.
- `includes/ecs-dependencies.php:10` requires an active plugin whose basename is `elementor-pro.php`. Modulix cannot
  satisfy this check without impersonating the paid plugin.
- `modules/legacy/class-ecs-legacy-module.php:50` registers `elementor_pro/init` only after the basename check passes.
  Consequently the deprecated legacy skin and its Theme Builder files are not loaded with Modulix.
- The gated legacy code references Pro-compatible APIs at `skins/skin-custom.php:2`, `:9`, `:10` and `:521`;
  `theme-builder/documents/loop.php:2`, `:3` and `:4`;
  `theme-builder/documents/custom-grid.php:3` and `:5`;
  `theme-builder/conditions/loop.php:4`, `:5`, `:6` and `:12`;
  `theme-builder/conditions/custom-grid.php:4`, `:5`, `:6` and `:12`;
  `theme-builder/dynamic-tags/tags/post-summary.php:2` and `:6`;
  `theme-builder/dynamic-tags/ele-tags.php:3`; `theme-builder/classes/custom-types-manager.php:2`, `:5`, `:6` and
  `:7`; and `theme-builder/init.php:9`, `:14`, `:15`, `:16`, `:18` and `:55`. The corresponding Modulix aliases and
  retained filters exist, but the plugin's earlier basename gate makes these paths unreachable.
- `includes/ajax-pagination.php:54`, `:55`, `:58` and `:59`; `skins/skin-custom.php:435` and `:458`;
  `theme-builder/documents/loop.php:60` and `:63`; `theme-builder/documents/custom-grid.php:47`; and
  `modules/mobile-menu/class-ecs-mobile-menu-module.php:74`, `:78`, `:79`, `:80`, `:114`, `:118`, `:122`, `:126`,
  `:130`, `:180`, `:184`, `:185`, `:186`, `:187`, `:188`, `:189`, `:190`, `:234`, `:263`, `:291`, `:325`, `:365`,
  `:367`, `:418`, `:423`, `:427`, `:431`, `:455`, `:459`, `:460`, `:477`, `:482`, `:486`, `:490`, `:524`, `:549`,
  `:576` and `:601` use `elementor-pro` only as a translation text domain.

### X8 - Essential Addons for Elementor 6.8.7

- `includes/Classes/Asset_Builder.php:191` and `:199` check and instantiate the Theme Builder module. The alias exists,
  and the called locations-manager methods are present.
- `includes/Traits/Elements.php:397`, `:398` and `:411` check the Theme Builder module and access it through
  `ElementorPro\Plugin`. The shim returns the module and its conditions manager.
- `includes/Elements/Woo_Product_Tabs.php:901` is a source-code comment. The `elementor-product-*` strings in
  `includes/Elements/Woo_Add_To_Cart.php:639` and `:923` are CSS classes, not Elementor Pro APIs.

### X9 - Sticky Header Effects for Elementor 2.2.3

- `includes/posimyth-sdk/class-posimyth-tracker-she.php:278` reports an empty `elementor_pro_version` value when the
  Pro version constant is absent. It does not gate plugin behavior.
- `includes/posimyth-sdk/class-posimyth-tracker-base.php:748`, `:751`, `:773`, `:814`, `:827` and `:828` use
  `elementor-pro` as a product-discovery marker.
- `includes/dashboard/class-she-dashboard-ajax.php:172` and `:174`, and
  `includes/notices/class-she-notice-main.php:130` and `:132`, refer to Elementor Pro marketplace metadata.
- `includes/notices/class-she-pro-launch-notice.php:49` and `includes/notices/class-she-sale-notice.php:50` refer to
  Sticky Header Effects Pro, not Elementor Pro.
- `build/index.js:1` contains the CSS state name `no-elementor-pro`; it does not access an Elementor Pro JavaScript
  global.

### X10 - YITH WooCommerce Dynamic Pricing and Discounts

- Not checked because no source package was available.

### X11 - Advanced AJAX Product Filters 3.3

- `includes/compatibility/include.php:22` gates the Elementor integration on `ELEMENTOR_PRO_VERSION`.
- `includes/compatibility/include.php:23` names the local integration file `elementor-pro.php`. Because the gate is
  false, that file is not included and its controls for Products, Loop Grid and Loop Carousel are unavailable.

No checked release referenced `ElementorProFrontendConfig`, `elementorProFrontend`, `elementorPro` as a JavaScript
global, or an `elementor-pro` script or style handle.
