# Modulix

Open-source drop-in replacement for Elementor Pro features. Switch off Elementor Pro, switch on Modulix, and the
templates and widgets you already built keep working: same widget names, same settings, same Theme Builder
conditions. No licence key, no upsells in the editor.

Modulix is built from Elementor Pro's own GPL code (see [How it is built](#how-it-is-built)), so it renders the
same markup as Pro. It runs in production since October 2026.

## What works today (0.4.x)

| Area | Included |
|---|---|
| Theme Builder | Header and footer templates with all display conditions, Site Logo widget |
| Widgets | Nav Menu (WordPress menu, dropdown and mobile menu), Floating Buttons (contact buttons and floating bars) |
| Effects | Sticky, Motion Effects (scrolling and mouse), Custom CSS on elements and pages |
| Dynamic tags | Site Logo, Site URL |
| Editor | No "Go Pro" cards, locked widgets or upgrade banners |

Next release (0.5, in testing): single, single post, single page and 404 templates; Post Title, Content, Excerpt,
Featured Image and other theme widgets; Template and Gallery widgets; post, archive, site, author and ACF dynamic
tags. After that: Posts, Archive Posts and Loop Grid, Forms, carousels and sliders. WooCommerce widgets and
popups come later.

If a site uses a Pro widget that Modulix does not have yet, that widget stays empty while Modulix is active. Check
the list above before switching.

Planned: **Modulix MCP** - a Model Context Protocol server, so an AI agent can build and edit pages, templates and
design on an Elementor site (structure, widgets, styles, Theme Builder conditions) and check the result.

## Requirements

- WordPress with Elementor (free) 3.34 or newer; tested with 3.34.2 and 4.3.5
- PHP 8.1 or newer

## Install and switch from Elementor Pro

1. Download `modulix-x.y.z.zip` from [Releases](../../releases).
2. Back up the site.
3. Plugins -> Add New -> Upload Plugin, upload the zip. Do not activate yet.
4. Deactivate Elementor Pro, then activate Modulix. Modulix refuses to activate while Elementor Pro is active.
5. Regenerate Elementor's CSS files (in Elementor's Tools) and clear any page cache.

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

Issues and pull requests are welcome. For security problems see [SECURITY.md](SECURITY.md).

## Licence and trademarks

GPL-3.0-or-later. Modulix contains code derived from Elementor Pro, Copyright (C) 2016-2024 Elementor Ltd., licensed
under GPL-3.0-or-later; see [NOTICE.md](NOTICE.md).

Modulix is not affiliated with or endorsed by Elementor Ltd. "Elementor" is a trademark of Elementor Ltd. and is used
here only to describe compatibility.
