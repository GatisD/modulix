# How Modulix is built from Elementor Pro

Modulix reuses Elementor Pro's GPL code instead of rewriting it, so existing Pro templates and widget settings keep
working after the switch. Upstream: Elementor Pro 4.0.4.

1. **Mechanical lift.** `tools/lift.py <pro-root> docs/lift-manifest.txt . 4.0.4` copies the listed files and changes
   only the namespace (`ElementorPro` -> `Modulix`), Pro path and version constants (`ELEMENTOR_PRO_*` ->
   `MODULIX_*`), the text domain and, in JavaScript, the global names (`elementorProFrontend` -> `modulixFrontend`,
   `ElementorProFrontendConfig` -> `ModulixFrontendConfig`). Each file gets two marker lines: where it comes from and
   what changed (GPL section 7). The tool refuses to overwrite a file Modulix has changed since its lift.
2. **Trims.** Separate commits remove licence checks, Elementor Connect, telemetry, upsells and anything a module
   does not need. `git diff <lift commit> HEAD -- <file>` shows Modulix's own changes to a file.
3. **References.** `tests/LiftedReferencesTest.php` fails while any `Modulix\` class referenced by lifted code does
   not exist, or while `ElementorPro\`, `ELEMENTOR_PRO_` or the `elementor-pro` text domain is left over.
4. **Endpoints and scan first.** Every lifted AJAX or REST endpoint is read for its permission check and for request
   data that reaches a query, before any parity work; a change scan of the lift runs right after it, not at the end.
5. **Parity.** Each module is compared with Elementor Pro on a test site before release: server markup per
   document, layout at desktop and mobile width, scroll and menu states, console errors, PHP log.

`ELEMENTOR_PRO_VERSION` stays undefined on purpose (roadmap step F6 measures whether an opt-in mode is worth it).
Themes and plugins that call Elementor Pro classes get Modulix's classes through `src/Compat/ProAliases.php`:
`ElementorPro\X` resolves to `Modulix\X` when Modulix has it, and `ElementorPro\Plugin::instance()` answers
`modules_manager->get_modules()`. Names Modulix lacks stay undefined, so `class_exists()` checks keep working.

## Upstream updates

Compare releases before re-lifting:

```
python3 tools/upstream-diff.py <old-pro-root> <new-pro-root>
```

For each changed file, use `tools/lift.py --force` to lift it into a scratch directory first. Compare that output with
the current Modulix file, then port Modulix's trims by hand. Never overwrite a trimmed file blindly.

## Regenerate the editor bundle

With Elementor Pro 4.0.4 available locally, regenerate the entry-trimmed bundle with:

```
python3 tools/editor-bundle.py /path/to/elementor-pro-4.0.4
```

The command writes `assets/js/modulix-editor-pro.js`. Use `--out /path/to/file.js` for a comparison build. The editor
chunks and `assets/css/editor*.css` remain mechanical lifts listed in `docs/lift-manifest.txt`.
