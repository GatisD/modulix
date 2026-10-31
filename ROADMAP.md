# Modulix roadmap

Goal: Modulix implements the features in milestones 0-7 while preserving the stored Elementor names for supported
widgets, tags and documents. A site should switch only after the readiness and compatibility checks pass.

The work is split into small steps. Each step is one GitHub issue (linked below, grouped in milestones) and one pull
request, can be tested on its own, and names what it depends on. Pick any open step whose dependencies are done; see
[CONTRIBUTING.md](CONTRIBUTING.md).

Size is the Elementor Pro code the step lifts (PHP lines): **S** under 1,000, **M** 1,000-3,000, **L** 3,000-8,000,
**XL** over 8,000. Labels: `good first issue` (a widget without server endpoints), `needs-maintainer` (endpoints,
uploads, outgoing requests, capabilities or money), `spike` (a short investigation that ends in a written decision).

## Done and tested

This section is limited to recorded browser, server and parity results. A registered module is not enough to appear
here.

Testing was recorded on 2026-10-31 in WordPress 7.1.3 with Elementor 4.3.5 and WooCommerce 11.2.1, using Elementor
Pro 4.0.4 without a licence as the reference.

- Frontend checks covered 43 test pages and 21 site URLs at 1366 px and 390 px. Markup, layout, console output and the
  PHP log matched the reference in these checks except for the two licence-dependent reference results: Floating
  Buttons produced no output, and the popup dynamic tag produced `#`.
- The 43 test documents opened in the editor with the same document type, widgets and console output as the
  reference.
- One browser-and-server run passed all checks for forms, file upload, Login, Popups, popup form actions, Custom Code,
  Global Widget, Loop Carousel, the WooCommerce product-to-order flow, WooCommerce archive templates, the Theme
  Builder app, the conditions dialog, Display Conditions, custom attributes, Role Manager, page transitions and
  scroll snap, submissions administration and webhook delivery.
- An automated scan of the admin and editor screens, including Site Settings, found no upgrade prompts.

The automated run did not cover the Custom Fonts or Custom Icons upload flow, or the WooCommerce settings in Site
Settings. The same checks fail against the reference, so this is a test-kit limitation rather than a known Modulix
defect. Test these areas by hand before switching a site that uses them.

## Milestone 0 - Foundation (0.7)

Adds the switch-readiness report, compatibility aliases and contributor tooling used by later milestones.

| Step | What | Size | Depends on | Label |
|---|---|---|---|---|
| [F1](../../issues/1) | **Coverage list**: one file that lists every widget, dynamic tag, document type, theme location and element feature Modulix supports; a test fails when a widget is added without updating it | S | - | good first issue |
| [F2](../../issues/2) | **Readiness scanner**: reads every Elementor document's data and reports what Modulix does not cover yet, per page, with edit links | M | F1 | - |
| [F3](../../issues/3) | **Ecosystem scan**: finds active plugins and the theme that reference Elementor Pro classes or constants and says whether Modulix provides them | S | F5 | - |
| [F4](../../issues/4) | **Check mode**: Modulix can be activated while Elementor Pro is still active; then it only runs the readiness check (Tools -> Modulix, and `wp modulix check`), nothing else | S | F2 | needs-maintainer |
| [F5](../../issues/5) | **Compatibility layer**: `ElementorPro\...` class names resolve to the matching Modulix classes, and `ElementorPro\Plugin::instance()` returns a small object with the module lookups themes and plugins use | M | - | needs-maintainer |
| [F6](../../issues/6) | **Spike: Pro version constant**: measure what Elementor and common themes and plugins do when `ELEMENTOR_PRO_VERSION` is defined; decide whether Modulix offers an opt-in compatibility mode | S | F5 | spike |
| [F7](../../issues/7) | **Spike: editor scripts**: decide how Modulix ships the editor side of upcoming modules (conditions dialog, loop items, forms, popups): lift the Pro editor bundle once, or per module | S | - | spike |
| [F8](../../issues/8) | **CI**: tests on PHP 8.1-8.4, the lift tool tests and a release zip on every tag | S | - | needs-maintainer |
| [F9](../../issues/9) | **Upstream sync**: a tool that lists which lifted files changed in a newer Elementor Pro release, and a documented way to re-lift them | M | - | - |

## Milestone 1 - Site-wide code and assets (0.7)

| Step | What | Size | Depends on | Label |
|---|---|---|---|---|
| [S1](../../issues/10) | **Custom Code**: head, body start and body end snippets with conditions and priority | M | - | needs-maintainer |
| [S2](../../issues/11) | **Custom Attributes** on elements | S | - | good first issue |
| [S3](../../issues/12) | **Custom Fonts, Custom Icons, Adobe Fonts** | L | - | needs-maintainer |
| [S4](../../issues/13) | **Role Manager, Element Manager** | S | - | - |
| [S5](../../issues/14) | **Page Transitions, Scroll Snap** | M | - | good first issue |

## Milestone 2 - Theme Builder complete (1.0)

| Step | What | Size | Depends on | Label |
|---|---|---|---|---|
| [T1](../../issues/15) | **Conditions dialog in the editor**: set where a template shows when publishing it | M | F7 | needs-maintainer |
| [T2](../../issues/16) | **Theme Builder app**: the visual overview of all theme templates, replacing today's list | L | F7 | - |
| [T3](../../issues/17) | **Theme elements**: Post Info, Post Navigation, Author Box, Breadcrumbs, Search Form, Sitemap, Post Comments | L | - | - |
| [T4](../../issues/18) | **Display Conditions** on elements (show or hide an element by rules) | M | F7 | - |
| [T5](../../issues/19) | **Global Widget**: existing global widgets keep rendering and can be edited | S | - | - |
| [T6](../../issues/20) | **Remaining dynamic tags** (request parameter, shortcode, user info, internal URL, lightbox, reload page and others) | M | - | needs-maintainer |

## Milestone 3 - Loops and carousels (1.0)

| Step | What | Size | Depends on | Label |
|---|---|---|---|---|
| [L1](../../issues/21) | **Loop Builder**: loop item templates and the Loop Grid widget | L | F7 | - |
| [L2](../../issues/22) | **Loop Filter** (taxonomy filter for loops) | M | L1 | needs-maintainer |
| [L3](../../issues/23) | **Carousels**: Media Carousel, Testimonial Carousel, Reviews, Loop Carousel | L | L1 for Loop Carousel | - |
| [L4](../../issues/24) | **Nested Carousel** | S | - | good first issue |
| [L5](../../issues/25) | **Slides** | M | - | good first issue |

## Milestone 4 - Forms (1.0)

| Step | What | Size | Depends on | Label |
|---|---|---|---|---|
| [FO1](../../issues/26) | **Form widget** with email, redirect and stored submissions, honeypot and reCAPTCHA | XL | F7 | needs-maintainer |
| [FO2](../../issues/27) | **Submissions** admin page and export | M | FO1 | needs-maintainer |
| [FO3](../../issues/28) | **Integrations**: webhook, Mailchimp, MailerLite, ActiveCampaign, ConvertKit, GetResponse, Drip, Discord, Slack, MailPoet (one pull request each) | S each | FO1 | needs-maintainer |
| [FO4](../../issues/29) | **Login widget** | S | FO1 | needs-maintainer |
| [FO5](../../issues/30) | **File upload field** | S | FO1 | needs-maintainer |

## Milestone 5 - Popups (1.0)

| Step | What | Size | Depends on | Label |
|---|---|---|---|---|
| [P1](../../issues/31) | **Popup templates**: triggers, advanced rules, timing, display conditions | M | T1 | needs-maintainer |
| [P2](../../issues/32) | **Popup tag and form actions** (open or close a popup from a link or after a form) | S | P1, FO1 | - |

## Milestone 6 - Widget pack (any time after F1)

Independent widgets, good places to start. One widget per pull request.

| Step | Widget | Size | Label |
|---|---|---|---|
| [W1](../../issues/33) | Flip Box | M | good first issue |
| [W2](../../issues/34) | Call to Action | M | good first issue |
| [W3](../../issues/35) | Animated Headline | S | good first issue |
| [W4](../../issues/36) | Price Table, Price List | M | good first issue |
| [W5](../../issues/37) | Countdown | S | good first issue |
| [W6](../../issues/38) | Share Buttons | S | good first issue |
| [W7](../../issues/39) | Blockquote | M | good first issue |
| [W8](../../issues/40) | Table of Contents | M | good first issue |
| [W9](../../issues/41) | Lottie | M | good first issue |
| [W10](../../issues/42) | Hotspot | M | good first issue |
| [W11](../../issues/43) | Off-Canvas | S | good first issue |
| [W12](../../issues/44) | Progress Tracker | S | good first issue |
| [W13](../../issues/45) | Code Highlight | S | good first issue |
| [W14](../../issues/46) | Video Playlist | M | - |
| [W15](../../issues/47) | Facebook widgets (Page, Button, Comments, Embed) | M | - |
| [W16](../../issues/48) | Search (live results) | M | needs-maintainer |
| [W17](../../issues/49) | Mega Menu | M | - |
| [W18](../../issues/50) | Link in Bio | M | - |

## Milestone 7 - WooCommerce (1.0)

| Step | What | Size | Depends on | Label |
|---|---|---|---|---|
| [WC1](../../issues/51) | Product template and product widgets (title, images, price, add to cart, rating, stock, meta, descriptions, data tabs, related, upsells), product dynamic tags | L | - | needs-maintainer |
| [WC2](../../issues/52) | Product archive template, Products and Archive Products widgets, categories, product loop items | L | L1 | - |
| [WC3](../../issues/53) | Cart, Checkout, My Account, Purchase Summary, Notices, Menu Cart, Add to Cart | L | WC1 | needs-maintainer |
| [WC4](../../issues/54) | WooCommerce site settings in the kit | S | WC1 | - |

## Milestone 8 - Ecosystem (ongoing)

Themes and plugins that check for Elementor Pro are tested with Modulix, and the result goes to
`docs/compatibility.md`. One pull request per theme or plugin; all depend on F5. Label: `compat`.

| Step | Theme or plugin |
|---|---|
| [X1](../../issues/55) | Hello Elementor |
| [X2](../../issues/56) | Astra |
| [X3](../../issues/57) | Woodmart |
| [X4](../../issues/58) | Rank Math |
| [X5](../../issues/59) | WPML |
| [X6](../../issues/60) | Connect Polylang for Elementor |
| [X7](../../issues/61) | Ele Custom Skin (also needs L1) |
| [X8](../../issues/62) | Essential Addons for Elementor |
| [X9](../../issues/63) | Sticky Header Effects for Elementor |
| [X10](../../issues/64) | YITH WooCommerce Dynamic Pricing and Discounts |
| [X11](../../issues/65) | Advanced AJAX Product Filters |

## Version 1.0.0

Milestones 0-7 are included in 1.0.0. The readiness check covers the supported features listed in the release, and
the recorded validation status and known limitations are stated above.

## Not planned

Licence checks, Elementor Connect, telemetry and anything that needs Elementor's servers (cloud templates, kits from
the cloud, Elementor AI). AI help through Modulix MCP is planned separately (see Later). Payments and Notes may come
later. Elementor 4 atomic widgets follow once the atomic editor is the default in Elementor.

## Later

**Planned: public Modulix MCP support**. The release payload contains an experimental, opt-in MCP integration that is
disabled by default and requires the WordPress MCP Adapter. It can expose 12 separately selectable abilities for site
and document reads, readiness, previews, document and element changes, conditions, kit updates and publishing. Write
abilities are not preselected. This surface is not part of the 1.0.0 feature list while public support remains planned.
