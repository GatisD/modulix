# Changelog

This file records public releases. Dates come from the repository release history.

Modulix is an independently maintained, open-source plugin for use with Elementor. It is not affiliated with,
endorsed by or sponsored by Elementor. References to Elementor and Elementor Pro identify upstream software and
compatibility. Their product names and trademarks belong to their respective owners. Modulix does not include an
Elementor subscription, official support or access to paid Elementor services.

## 1.0.0 - 2026-11-01

### Added

- Popup form actions and element Display Conditions.
- Theme Builder app and editor bundles for the local template and conditions workflows.
- Static compatibility results for the themes and plugins listed in `docs/compatibility.md`.
- Translation template generation and uninstall cleanup.
- An experimental, opt-in MCP integration that is disabled by default, requires the WordPress MCP Adapter and
  exposes 12 separately selectable abilities. Write abilities are not preselected.

### Changed

- Added checks that required JavaScript and CSS assets are present and that lifted method and class references
  resolve.
- Replaced supported Theme Builder, editor and admin upgrade paths with local Modulix workflows.
- Suppressed Elementor upgrade prompts on covered admin and editor screens without changing Elementor files or
  vendor account state. An automated scan, including Site Settings, found no upgrade prompts.

### Security

- Added server-side upload type checks, guarded icon archive extraction and sanitized package names.
- Added capability and access checks for unpublished documents, query and taxonomy data, WooCommerce actions and MCP
  abilities, with request limits and an audit log for the opt-in MCP surface.

### Known limitations

- The automated run does not cover the Custom Fonts or Custom Icons upload flow, or the WooCommerce settings in Site
  Settings. The same checks fail against the reference, so this is a test-kit limitation rather than a known Modulix
  defect. Test these areas by hand before switching a site that uses them.

## 0.7.0 - 2026-10-24

- Added a readiness report in Tools -> Modulix and `wp modulix check`.
- Added check mode, which allows Modulix to run its readiness report while Elementor Pro remains active.
- Added aliases for supported Elementor Pro classes and module lookups used by themes and plugins.
- Added the generated Elementor Pro feature catalog and ecosystem reference scan.
- Kept `ELEMENTOR_PRO_VERSION` undefined so unsupported integrations can use their fallback paths.

## 0.6.0 - 2026-10-23

- Added Posts, Portfolio and Archive Posts, including pagination and load more.
- Added archive and search-results documents.
- Added the query control and Contact URL dynamic tag.
- Restricted user and author lookup data returned by the query control and removed deprecated query actions.

## 0.5.0 - 2026-10-23

- Added single, single-post, single-page and 404 documents.
- Added Site Title, Page Title, Post Title, Post Content, Post Excerpt, Post Featured Image and Archive Title widgets.
- Added Template and Gallery widgets.
- Added post, archive, site, author, comments, date, Contact URL and ACF dynamic tags.
- Replaced Theme Builder and Elementor 4 admin promotion entry points with local screens.
- Restricted preview and ACF option access to the required capabilities and published-document paths.

## 0.4.1 - 2026-10-23

- Included header and footer documents, Theme Builder conditions, Nav Menu, Floating Buttons, Sticky, Motion Effects,
  Custom CSS, Site Logo and Site URL.
- Required edit permission before the `theme_template_id` preview parameter can render an unpublished template.
- Escaped the Nav Menu submenu icon class in editor previews.
- Validated remote line counts before shell arithmetic in the parity tooling.
