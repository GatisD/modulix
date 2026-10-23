# Modulix

Open-source drop-in replacement for Elementor Pro features. Switch off Elementor Pro, switch on Modulix, and the
templates and widgets you already built keep working: same widget names, same settings, same Theme Builder
conditions. No licence key, no upsells in the editor.

Modulix is built from Elementor Pro's own GPL code (see [How it is built](#how-it-is-built)), so it renders the
same markup as Pro. It runs in production since October 2026.

## What works today (0.7.0-beta.1; latest stable release 0.6.0)

| Area | Included |
|---|---|
| Theme Builder | Header, footer, single, single post, single page, archive, search results and 404 templates with all display conditions; the Theme Builder menu opens the theme templates list |
| Theme widgets | Site Logo, Site Title, Page Title, Post Title, Post Content, Post Excerpt, Post Featured Image, Archive Title |
| Widgets | Posts, Portfolio, Archive Posts (with pagination and load more), Nav Menu (dropdown and mobile menu), Template, Gallery, Floating Buttons (contact buttons and floating bars) |
| Effects | Sticky, Motion Effects (scrolling and mouse), Custom CSS on elements and pages |
| Dynamic tags | Post, archive, site, author, comments and date tags, Contact URL, ACF fields (text, image, URL, color, number, file, date) |
| Editor and admin | No "Go Pro" cards, locked widgets, upgrade banners or upgrade buttons |
| Switching (0.7.0-beta.1) | Readiness check next to an active Elementor Pro (Tools -> Modulix, `wp modulix check`); themes and plugins that call Elementor Pro classes get the matching Modulix classes |

Next: Custom Code and carousels (0.7.0); then the full Theme Builder app with its conditions editor, Loop Grid,
Forms, popups and WooCommerce. The step-by-step plan is in [ROADMAP.md](ROADMAP.md).

If a site uses a Pro widget that Modulix does not have yet, that widget stays empty while Modulix is active. The
readiness check (step 4 below, from 0.7.0-beta.1) lists every such widget, dynamic tag, template type and feature before you switch.

Planned: **Modulix MCP** - a Model Context Protocol server, so an AI agent can build and edit pages, templates and
design on an Elementor site (structure, widgets, styles, Theme Builder conditions) and check the result.

## Requirements

- WordPress with Elementor (free) 3.34 or newer; tested with 3.34.2 and 4.3.5
- PHP 8.1 or newer

## Install and switch from Elementor Pro

1. Download `modulix-x.y.z.zip` from [Releases](../../releases).
2. Back up the site.
3. Plugins -> Add New -> Upload Plugin, upload the zip.
4. With 0.7.0-beta.1 or newer, check first: activate Modulix while Elementor Pro is still active. Modulix then runs
   only its readiness check: open Tools -> Modulix and press "Run the check" (or run `wp modulix check`). It lists
   the Pro widgets, dynamic tags, template types and features your pages use that Modulix does not cover yet, with
   links to those pages, and the themes and plugins that call Elementor Pro.
5. Deactivate Elementor Pro. With 0.7.0-beta.1 or newer, Modulix switches to full mode by itself. With 0.6.x,
   activate Modulix now (0.6.x refuses to activate while Elementor Pro is active).
6. Regenerate Elementor's CSS files (in Elementor's Tools) and clear any page cache.

To go back: deactivate Modulix and activate Elementor Pro again. Modulix does not change your templates or settings.

## How it is built

Modulix lifts the needed Elementor Pro files with a script that only renames the namespace, constants and
JavaScript globals, then removes licence checks, telemetry, Elementor Connect and upsells in separate, reviewable
commits. Every derived file keeps Elementor's copyright line and says what Modulix changed. Before a module is
released, it is compared with Elementor Pro on a test site: markup, layout at desktop and mobile width, scroll and
menu states, console and PHP errors. Details: [docs/LIFTING.md](docs/LIFTING.md).

## Development

```
composer install
vendor/bin/phpunit
python3 -m unittest discover -s tests/tools
tools/build.sh        # build/modulix-<version>.zip
```

Issues and pull requests are welcome: pick a step from [ROADMAP.md](ROADMAP.md) and read
[CONTRIBUTING.md](CONTRIBUTING.md). For security problems see [SECURITY.md](SECURITY.md).

## Licence and trademarks

GPL-3.0-or-later. Modulix contains code derived from Elementor Pro, Copyright (C) 2016-2024 Elementor Ltd., licensed
under GPL-3.0-or-later; see [NOTICE.md](NOTICE.md).

Modulix is not affiliated with or endorsed by Elementor Ltd. "Elementor" is a trademark of Elementor Ltd. and is used
here only to describe compatibility.
