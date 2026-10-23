# Modulix - origin and modifications

Modulix contains code derived from Elementor Pro, Copyright (C) 2016-2024 Elementor Ltd.,
licensed under the GNU General Public License v3 or later. This is a modified version and is
not the original program.

Per the additional terms under GPL section 7 in Elementor Pro's license.txt:
- We do not claim to have written the original program.
- Every file derived from Elementor Pro keeps its original copyright line and carries the line
  `Modified by Modulix (<date>): <what changed>`.

Removed from derived code: licence checks, Elementor Connect, telemetry, upsells, cloud
template library, AI features, update checks against Elementor servers.

Modulix is not affiliated with or endorsed by Elementor Ltd. "Elementor" is a trademark of
Elementor Ltd.; it is used here only to describe compatibility.

Upstream version used and how files are lifted: see docs/LIFTING.md.

Bundled third-party code: SmartMenus jQuery plugin 1.2.1 (`assets/lib/smartmenus/`), Copyright Vasil Dinkov,
Vadikom Web Ltd., MIT licence (as shipped with Elementor Pro).

## Lifted files (phase 1, Elementor Pro 4.0.4)

The list is `docs/lift-manifest.txt`. Each file carries two marker lines: the mechanical change
(namespace `ElementorPro` -> `Modulix`, text domain -> `modulix`) and Modulix's own changes.
`modules/query-control/module.php` holds only constant names and values from Pro.
Removed in phase 1 beyond the list above: editor conditions UI and AJAX, Theme Builder admin
app and menus, import/export hooks, feature locks, pagination fixes for single/archive
templates, GeneratePress and safe-mode theme support, the Pro logo placeholder image,
Custom CSS upgrade promotions and the promo site-settings tab.

## Plan 3 (2026-10-23)

Also lifted: header document, sticky, motion effects, nav menu, `base/base-widget.php`, the
smartmenus and sticky jQuery libraries, their CSS and responsive templates, and Pro's front-end
runtime (`assets/js`: webpack runtime, frontend, elements handlers, nav-menu chunk). Lifted
JavaScript renames Pro's globals (`elementorProFrontend` -> `modulixFrontend`,
`ElementorProFrontendConfig` -> `ModulixFrontendConfig`) and starts with a Modulix marker comment.
`src/Frontend.php` is derived from Pro's `plugin.php` front-end methods. Removed: the licence and
query-control import traits of `Base_Widget`, Pro's preview script.

## Plan 4 (2026-10-23)

Also lifted: floating buttons module (trimmed to widget registration), its base classes, renderers and
11 widgets, and their front-end chunks. Removed: Pro's floating-buttons document type, theme location and
printing (Elementor core prints include/general floating buttons when Pro is absent).
