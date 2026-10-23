# Modulix roadmap

Goal: Modulix covers most of what sites use from Elementor Pro, on its own: an existing Pro site switches without
losing widgets, templates or the plugins and themes that integrate with Pro, and a new site can be built from scratch.

The work is split into small steps. Each step is one GitHub issue and one pull request, can be tested on its own, and
names what it depends on. Pick any open step whose dependencies are done; see [CONTRIBUTING.md](CONTRIBUTING.md).

Size is the Elementor Pro code the step lifts (PHP lines): **S** under 1,000, **M** 1,000-3,000, **L** 3,000-8,000,
**XL** over 8,000. Labels: `good first issue` (a widget without server endpoints), `needs-maintainer` (endpoints,
uploads, outgoing requests, capabilities or money), `spike` (a short investigation that ends in a written decision).

## Done (0.6)

Theme Builder documents (header, footer, single, single post, single page, archive, search results, 404) with their
display conditions; theme widgets (site logo and title, page and post title, post content, excerpt and featured image,
archive title); Posts, Portfolio, Archive Posts; Nav Menu; Template; Gallery; Floating Buttons; Sticky; Motion
Effects; Custom CSS; dynamic tags incl. ACF; no upsells in the editor and admin.

## Milestone 0 - Foundation (0.7)

Makes the rest possible for more people: a check that tells a site owner whether they can switch, support for plugins
and themes that look for Elementor Pro, and the tooling contributors need.

| Step | What | Size | Depends on | Label |
|---|---|---|---|---|
| F1 | **Coverage list**: one file that lists every widget, dynamic tag, document type, theme location and element feature Modulix supports; a test fails when a widget is added without updating it | S | - | good first issue |
| F2 | **Readiness scanner**: reads every Elementor document's data and reports what Modulix does not cover yet, per page, with edit links | M | F1 | - |
| F3 | **Ecosystem scan**: finds active plugins and the theme that reference Elementor Pro classes or constants and says whether Modulix provides them | S | F5 | - |
| F4 | **Check mode**: Modulix can be activated while Elementor Pro is still active; then it only runs the readiness check (Tools -> Modulix, and `wp modulix check`), nothing else | S | F2 | needs-maintainer |
| F5 | **Compatibility layer**: `ElementorPro\...` class names resolve to the matching Modulix classes, and `ElementorPro\Plugin::instance()` returns a small object with the module lookups themes and plugins use | M | - | needs-maintainer |
| F6 | **Spike: Pro version constant**: measure what Elementor and common themes and plugins do when `ELEMENTOR_PRO_VERSION` is defined; decide whether Modulix offers an opt-in compatibility mode | S | F5 | spike |
| F7 | **Spike: editor scripts**: decide how Modulix ships the editor side of upcoming modules (conditions dialog, loop items, forms, popups): lift the Pro editor bundle once, or per module | S | - | spike |
| F8 | **CI**: tests on PHP 8.1-8.4, the lift tool tests and a release zip on every tag | S | - | needs-maintainer |
| F9 | **Upstream sync**: a tool that lists which lifted files changed in a newer Elementor Pro release, and a documented way to re-lift them | M | - | - |

## Milestone 1 - Site-wide code and assets (0.7)

| Step | What | Size | Depends on | Label |
|---|---|---|---|---|
| S1 | **Custom Code**: head, body start and body end snippets with conditions and priority | M | - | needs-maintainer |
| S2 | **Custom Attributes** on elements | S | - | good first issue |
| S3 | **Custom Fonts, Custom Icons, Adobe Fonts** | L | - | needs-maintainer |
| S4 | **Role Manager, Element Manager** | S | - | - |
| S5 | **Page Transitions, Scroll Snap** | M | - | good first issue |

## Milestone 2 - Theme Builder complete (0.8)

| Step | What | Size | Depends on | Label |
|---|---|---|---|---|
| T1 | **Conditions dialog in the editor**: set where a template shows when publishing it | M | F7 | needs-maintainer |
| T2 | **Theme Builder app**: the visual overview of all theme templates, replacing today's list | L | F7 | - |
| T3 | **Theme elements**: Post Info, Post Navigation, Author Box, Breadcrumbs, Search Form, Sitemap, Post Comments | L | - | - |
| T4 | **Display Conditions** on elements (show or hide an element by rules) | M | F7 | - |
| T5 | **Global Widget**: existing global widgets keep rendering and can be edited | S | - | - |
| T6 | **Remaining dynamic tags** (request parameter, shortcode, user info, internal URL, lightbox, reload page and others) | M | - | needs-maintainer |

## Milestone 3 - Loops and carousels (0.9)

| Step | What | Size | Depends on | Label |
|---|---|---|---|---|
| L1 | **Loop Builder**: loop item templates and the Loop Grid widget | L | F7 | - |
| L2 | **Loop Filter** (taxonomy filter for loops) | M | L1 | needs-maintainer |
| L3 | **Carousels**: Media Carousel, Testimonial Carousel, Reviews, Loop Carousel | L | L1 for Loop Carousel | - |
| L4 | **Nested Carousel** | S | - | good first issue |
| L5 | **Slides** | M | - | good first issue |

## Milestone 4 - Forms (0.10)

| Step | What | Size | Depends on | Label |
|---|---|---|---|---|
| FO1 | **Form widget** with email, redirect and stored submissions, honeypot and reCAPTCHA | XL | F7 | needs-maintainer |
| FO2 | **Submissions** admin page and export | M | FO1 | needs-maintainer |
| FO3 | **Integrations**: webhook, Mailchimp, MailerLite, ActiveCampaign, ConvertKit, GetResponse, Drip, Discord, Slack (one pull request each) | S each | FO1 | needs-maintainer |
| FO4 | **Login widget** | S | FO1 | needs-maintainer |
| FO5 | **File upload field** | S | FO1 | needs-maintainer |

## Milestone 5 - Popups (0.11)

| Step | What | Size | Depends on | Label |
|---|---|---|---|---|
| P1 | **Popup templates**: triggers, advanced rules, timing, display conditions | M | T1 | needs-maintainer |
| P2 | **Popup tag and form actions** (open or close a popup from a link or after a form) | S | P1, FO1 | - |

## Milestone 6 - Widget pack (any time after F1)

Independent widgets, good places to start. One widget per pull request.

| Step | Widget | Size | Label |
|---|---|---|---|
| W1 | Flip Box | M | good first issue |
| W2 | Call to Action | M | good first issue |
| W3 | Animated Headline | S | good first issue |
| W4 | Price Table, Price List | M | good first issue |
| W5 | Countdown | S | good first issue |
| W6 | Share Buttons | S | good first issue |
| W7 | Blockquote | M | good first issue |
| W8 | Table of Contents | M | good first issue |
| W9 | Lottie | M | good first issue |
| W10 | Hotspot | M | good first issue |
| W11 | Off-Canvas | S | good first issue |
| W12 | Progress Tracker | S | good first issue |
| W13 | Code Highlight | S | good first issue |
| W14 | Video Playlist | M | - |
| W15 | Facebook widgets (Page, Button, Comments, Embed) | M | - |
| W16 | Search (live results) | M | needs-maintainer |
| W17 | Mega Menu | M | - |
| W18 | Link in Bio | M | - |

## Milestone 7 - WooCommerce (1.0)

| Step | What | Size | Depends on | Label |
|---|---|---|---|---|
| WC1 | Product template and product widgets (title, images, price, add to cart, rating, stock, meta, descriptions, data tabs, related, upsells), product dynamic tags | L | - | needs-maintainer |
| WC2 | Product archive template, Products and Archive Products widgets, categories, product loop items | L | L1 | - |
| WC3 | Cart, Checkout, My Account, Purchase Summary, Notices, Menu Cart, Add to Cart | L | WC1 | needs-maintainer |
| WC4 | WooCommerce site settings in the kit | S | WC1 | - |

## Milestone 8 - Ecosystem (ongoing)

Themes and plugins that check for Elementor Pro are tested with Modulix and listed in `docs/compatibility.md` with the
result: Hello Elementor, Astra, Woodmart, Rank Math, WPML, Polylang connector for Elementor, Ele Custom Skin,
Essential Addons, Sticky Header Effects, YITH Dynamic Pricing, WooCommerce AJAX filters. One pull request per theme or
plugin; depends on F5.

## Version 1.0

Milestones 0-7 are done, the readiness check covers every supported feature, and the ecosystem list has a result for
each entry.

## Not planned

Licence checks, Elementor Connect, telemetry and anything that needs Elementor's servers (cloud templates, kits from
the cloud, AI). Payments and Notes may come later. Elementor 4 atomic widgets follow once the atomic editor is the
default in Elementor.

## Later

**Modulix MCP**: a Model Context Protocol server, so an AI agent can build and edit pages, templates and design on an
Elementor site and check the result.
