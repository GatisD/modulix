#!/usr/bin/env python3
"""Lift Elementor Pro files into Modulix, reproducibly.

Usage: tools/lift.py <upstream_root> <manifest> <dest_root> <pro_version> [date]
Manifest: one upstream-relative path or glob per line, '#' comments allowed.
Mechanical changes only (namespace, Pro path/version constants, text domain, GPL s.7 marker;\n.js: Pro global names renamed + marker; other files verbatim). Modulix's own trims are
separate commits on top, so `git diff <lift commit> HEAD -- <file>` lists them.
"""
import datetime, pathlib, re, sys

JS_GLOBALS = [("ElementorProFrontendConfig", "ModulixFrontendConfig"), ("elementorProFrontend", "modulixFrontend")]
TEXT_DOMAIN_FUNCTIONS = (
    "__", "_e", "_x", "_ex", "_n", "_nx", "_n_noop", "_nx_noop",
    "esc_html__", "esc_html_e", "esc_html_x", "esc_attr__", "esc_attr_e", "esc_attr_x",
    "translate", "translate_with_gettext_context", "wp_set_script_translations", "load_plugin_textdomain",
)


def replace_text_domains(src: str) -> str:
    out: list[str] = []
    calls: list[str | None] = []
    i = 0
    in_php = False
    while i < len(src):
        if not in_php:
            start = src.find("<?", i)
            if start < 0:
                out.append(src[i:])
                break
            end = start + (5 if src.startswith("<?php", start) else 3 if src.startswith("<?=", start) else 2)
            out.append(src[i:end])
            i = end
            in_php = True
            continue
        if src.startswith("?>", i):
            out.append("?>")
            i += 2
            in_php = False
            calls.clear()
            continue
        if src.startswith("//", i) or src[i] == "#":
            end = src.find("\n", i)
            end = len(src) if end < 0 else end
            out.append(re.sub(r"'elementor-pro'(\s*\))", r"'modulix'\1", src[i:end]))
            i = end
            continue
        if src.startswith("/*", i):
            end = src.find("*/", i + 2)
            end = len(src) if end < 0 else end + 2
            out.append(re.sub(r"'elementor-pro'(\s*\))", r"'modulix'\1", src[i:end]))
            i = end
            continue
        if src[i] in ("'", '"'):
            quote = src[i]
            end = i + 1
            while end < len(src):
                if src[end] == "\\":
                    end += 2
                    continue
                end += 1
                if src[end - 1] == quote:
                    break
            literal = src[i:end]
            in_domain_call = bool(calls) and calls[-1] in TEXT_DOMAIN_FUNCTIONS
            compared_to_domain = bool(re.match(r"\s*!==?\s*\$domain\b", src[end:])) or bool(
                re.search(r"\$domain\s*!==?\s*$", src[max(0, i - 80):i])
            )
            out.append("'modulix'" if literal == "'elementor-pro'" and (in_domain_call or compared_to_domain) else literal)
            i = end
            continue
        if src[i] == "(":
            before = src[:i].rstrip()
            function = re.search(r"([A-Za-z_]\w*)$", before)
            calls.append(function.group(1).lower() if function else None)
        elif src[i] == ")" and calls:
            calls.pop()
        out.append(src[i])
        i += 1
    return "".join(out)


def transform(src: str, rel: str, version: str, date: str) -> str:
    if rel.endswith(".js"):
        out = src
        for old, new in JS_GLOBALS:
            out = out.replace(old, new)
        return (f"/*! Modulix: derived from Elementor Pro {version}, {rel}. Copyright (C) 2016-2024 Elementor Ltd., "
                f"GPL-3.0-or-later. Modified by Modulix ({date}): global names elementorProFrontend -> modulixFrontend, "
                f"ElementorProFrontendConfig -> ModulixFrontendConfig. */\n" + out)
    if not rel.endswith(".php"):
        return src
    out = re.sub(r"\bElementorPro(?=\\)", "Modulix", src)
    out = re.sub(r"\bELEMENTOR_PRO_(ASSETS_URL|ASSETS_PATH|MODULES_URL|MODULES_PATH|URL|PATH|VERSION)\b", r"MODULIX_\1", out)
    out = replace_text_domains(out)
    marker = (f"// Modulix: derived from Elementor Pro {version}, {rel}. "
              "Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.\n"
              f"// Modified by Modulix ({date}): namespace ElementorPro -> Modulix, text domain -> modulix.\n")
    lines = out.splitlines(keepends=True)
    at = next((i + 1 for i, l in enumerate(lines) if l.startswith("namespace ")), 1)
    return "".join(lines[:at] + [marker] + lines[at:])

def main(argv):
    force = "--force" in argv
    argv = [a for a in argv if a != "--force"]
    upstream, manifest, dest, version = (pathlib.Path(argv[1]), pathlib.Path(argv[2]), pathlib.Path(argv[3]), argv[4])
    date = argv[5] if len(argv) > 5 else datetime.date.today().isoformat()
    count = 0
    jobs = []
    for line in manifest.read_text().splitlines():
        line = line.split("#", 1)[0].strip()
        if not line:
            continue
        matches = sorted(upstream.glob(line))
        if not matches:
            sys.exit(f"manifest entry matches nothing: {line}")
        for f in matches:
            rel = f.relative_to(upstream).as_posix()
            text = rel.endswith((".php", ".js"))
            jobs.append((dest / rel, transform(f.read_text(), rel, version, date) if text else f.read_bytes()))
    # Never overwrite a file Modulix has changed since its mechanical lift (2026-10-23: a partial manifest
    # re-lifted a trimmed module over our version). Identical re-lifts are fine; --force overrides.
    changed = [str(t) for t, out in jobs if t.exists() and
               (t.read_text() if isinstance(out, str) else t.read_bytes()) != out]
    if changed and not force:
        sys.exit("refusing to overwrite files that differ from a fresh lift (Modulix changes?):\n  " + "\n  ".join(changed)
                 + "\nlift into an empty directory, or pass --force")
    for target, out in jobs:
        target.parent.mkdir(parents=True, exist_ok=True)
        if isinstance(out, str):
            target.write_text(out)
        else:
            target.write_bytes(out)
        count += 1
    print(f"lifted {count} files from {upstream}")

if __name__ == "__main__":
    main(sys.argv)
