# Modulix

Modulix is an independently maintained, open-source GPL WordPress plugin for use with Elementor. It supports the
features and template formats listed for each release. For supported features, it preserves existing stored widget
names and settings, so those Elementor documents do not need to be converted before a switch.

This is an independent project. It is not affiliated with, endorsed by or sponsored by Elementor. References to
Elementor and Elementor Pro identify upstream software and compatibility. Their product names and trademarks belong
to their respective owners. The project does not include an Elementor subscription, official support or access to
paid Elementor services.

## What works in 1.0.0

| Area | Included |
|---|---|
| Theme Builder | Template overview, add new, local import and export, and display conditions for header, footer, single post, single page, archive, search results, 404, product and product archive templates |
| Element conditions | Display Conditions based on visitor, page, post, date, archive, URL and dynamic values |
| Popups | Popup documents, display conditions, triggers, timing, dynamic tag and form actions |
| Forms | Form and Login widgets, validation, uploads, stored submissions, email, redirect, webhook and the bundled service integrations |
| Site assets | Custom Fonts, Custom Icons and Adobe Fonts |
| WooCommerce | Product and archive templates, product dynamic tags, Products, Cart, Checkout, My Account, Purchase Summary, Notices, Menu Cart and purchase widgets |
| Theme widgets | Site Logo, Site Title, Page Title, Post Title, Post Content, Post Excerpt, Post Featured Image, Archive Title, Author Box, Breadcrumbs, Post Comments, Post Info, Post Navigation, Search Form and Sitemap |
| Loop content | Loop Item templates, Loop Grid, Loop Carousel, Taxonomy Filter and Search |
| Widgets | Posts, Portfolio, Archive Posts, Nav Menu, Template, Gallery, Global Widget, Animated Headline, Flip Box, Call to Action, Price Table, Price List, Countdown, Share Buttons, Blockquote, Table of Contents, Hotspot, Off-Canvas, Progress Tracker, Code Highlight, Slides, Video Playlist, Facebook widgets, Mega Menu, Lottie, Link in Bio, Nested Carousel, Media Carousel, Testimonial Carousel, Reviews and Floating Buttons |
| Effects and settings | Sticky, Motion Effects, Custom CSS, custom attributes, page transitions, scroll snap, role restrictions and element permissions |
| Custom Code | Snippets in the document head, body start and body end, with conditions and import or export |
| Dynamic tags | Post, archive, site, author, current-user, comments and date tags; request parameters; guarded shortcode output; internal URLs; lightbox and reload actions; Contact URL; ACF fields; WooCommerce product tags |
| Switching | Readiness check while Elementor Pro is active, plus class aliases for themes and plugins that call supported Elementor Pro classes |
| WP-CLI | Readiness check and Theme Builder condition cache reset |

The readiness check reports Pro widgets, dynamic tags, document types, theme locations and modeled features that the
current site uses but Modulix does not support. It does not inspect every setting inside a supported widget. A missing
item is a stop condition for the switch unless that item is known to be unused on the live pages, and the settings used
on the target site still require testing before the switch.

Theme and plugin results are recorded in the [compatibility table](docs/compatibility.md). A static compatibility
result does not replace a test on the target site.

Public Modulix MCP support remains planned and is not part of the 1.0.0 feature list. The release payload contains an
experimental, opt-in MCP integration that is disabled by default and requires the WordPress MCP Adapter. It can expose
12 separately selectable abilities: site overview, document listing and reading, widget schema, preview URLs,
readiness checks, document creation, element updates, page settings, conditions, kit updates and publishing. Write
abilities are not preselected.

## What has been tested

Testing was recorded on 2026-10-31 in WordPress 7.1.3 with Elementor 4.3.5 and WooCommerce 11.2.1, using Elementor
Pro 4.0.4 without a licence as the reference.

- **Frontend:** 43 test pages and 21 site URLs were checked at 1366 px and 390 px. Markup, layout, console output and
  the PHP log matched the reference in these checks except where the reference needed a licence: Floating Buttons
  produced no output in the reference, and the popup dynamic tag produced `#`.
- **Editor:** The 43 test documents opened with the same document type, widgets and console output as the reference.
- **End to end:** One browser-and-server run passed all checks for forms, file upload, Login, Popups, popup form
  actions, Custom Code, Global Widget, Loop Carousel, the WooCommerce product-to-order flow, WooCommerce archive
  templates, the Theme Builder app, the conditions dialog, Display Conditions, custom attributes, Role Manager, page
  transitions and scroll snap, submissions administration and webhook delivery.
- **Upgrade prompts:** An automated scan of the admin and editor screens, including Site Settings, found none.

The automated run did not cover the Custom Fonts or Custom Icons upload flow, or the WooCommerce settings in Site
Settings. The same checks fail against the reference, so this is a test-kit limitation rather than a known Modulix
defect. Test these areas by hand before switching a site that uses them.

The required local test commands are:

```sh
vendor/bin/phpunit
python3 -m unittest discover -s tests/tools
MODULIX_ELEMENTOR_CORE=/path/to/elementor-core-4.3.5 python3 -m unittest discover -s tests/tools
```

## Requirements

- WordPress with Elementor (free) 3.34 or newer. The recorded 1.0.0 checks used WordPress 7.1.3 and Elementor 4.3.5.
- PHP 8.1 or newer.
- WooCommerce for WooCommerce templates and widgets.
- WP-CLI only for the command-line readiness check and maintenance commands.

Development additionally requires Composer and Python 3. Browser tests require Node.js and Playwright.

## Install and switch from Elementor Pro

1. Download the release zip from [Releases](../../releases).
2. Back up the WordPress database and `wp-content` outside the public web root.
3. In WordPress, open Plugins -> Add New -> Upload Plugin, upload the zip and activate Modulix while Elementor Pro
   remains active. Modulix starts in check mode and does not register its feature modules.
4. Open Tools -> Modulix and run the readiness check, or run `wp modulix check`. Resolve every item that the site
   still needs. Review the [compatibility table](docs/compatibility.md) for active themes and plugins.
5. Deactivate Elementor Pro. Do not uninstall it during the first switch. Modulix changes from check mode to full
   mode automatically.
6. Regenerate Elementor CSS and data, clear page and object caches, and test the editor plus the public pages that
   use Pro features.

See [upgrading and rollback](docs/upgrading.md) for earlier Modulix versions and the rollback procedure.

## How it is built

Modulix copies the required Elementor Pro GPL files with a script that changes namespaces, constants, paths, text
domains and JavaScript globals. Separate commits remove licence checks, telemetry, Elementor Connect and promotion
code. Each derived file records its source and modification. See [docs/LIFTING.md](docs/LIFTING.md).

## Development

```sh
composer install
vendor/bin/phpunit
python3 -m unittest discover -s tests/tools
tools/build.sh
```

`tools/build.sh` writes `build/modulix-<version>.zip`. Issues and pull requests should follow
[CONTRIBUTING.md](CONTRIBUTING.md). Report security problems as described in [SECURITY.md](SECURITY.md). Release
changes are listed in [CHANGELOG.md](CHANGELOG.md).

## Licence and trademarks

GPL-3.0-or-later. Modulix includes modified GPL-licensed code from Elementor Pro 4.0.4. Original copyright and
licence notices are retained. Changes are documented in the source and release notes; see [NOTICE.md](NOTICE.md).
