#!/usr/bin/env python3
"""Generate Modulix's local-only Theme Builder app assets from Elementor Pro 4.0.4."""

import argparse
import pathlib
import re
import sys

import lift


LIFT_DATE = '2026-10-24'
REPO_ROOT = pathlib.Path(__file__).resolve().parents[1]
DEFAULT_JS_OUT = REPO_ROOT / 'assets' / 'js' / 'modulix-app.js'
DEFAULT_CSS_OUT = REPO_ROOT / 'assets' / 'css' / 'modulix-app.css'
FACTORIES_START = '/******/ \tvar __webpack_modules__ = ({'
FACTORIES_END = '/******/ \t});'
ENTRY_BANNER = '  !*** ../core/app/assets/js/index.js ***!'
FACTORY_HEADER = re.compile(r'^/\*\*\*/ "(?P<name>[^"]+)"$', re.MULTILINE)
WEBPACK_REQUIRE = re.compile(
    r'(?:__webpack_require__\(|__webpack_require__\.bind\(__webpack_require__,)'
    r'\s*(?:/\*![\s\S]*?\*/\s*)?"(?P<name>[^"]+)"'
)
SOURCE_MAP = re.compile(
    r'\n?(?://# sourceMappingURL=app\.(?:js|css)\.map|/\*# sourceMappingURL=app\.(?:js|css)\.map \*/)\s*$'
)


class BundleShapeError(ValueError):
    pass


def only_once(source, text):
    count = source.count(text)
    if count != 1:
        raise BundleShapeError(f'expected one {text!r} anchor, found {count}')


def factory_ranges(source):
    try:
        object_start = source.index(FACTORIES_START) + len(FACTORIES_START)
        object_end = source.index(FACTORIES_END, object_start)
    except ValueError as error:
        raise BundleShapeError('cannot find webpack module factory object') from error

    block = source[object_start:object_end]
    headers = list(FACTORY_HEADER.finditer(block))
    if not headers:
        raise BundleShapeError('webpack module factory object is empty')

    ranges = {}
    for index, header in enumerate(headers):
        start = object_start + header.start()
        end = object_start + (headers[index + 1].start() if index + 1 < len(headers) else len(block))
        ranges[header.group('name')] = (start, end)
    return ranges


def replace_factory(source, name, transform):
    ranges = factory_ranges(source)
    if name not in ranges:
        raise BundleShapeError(f'missing webpack factory: {name}')
    start, end = ranges[name]
    return source[:start] + transform(source[start:end]) + source[end:]


def trim_entry(source):
    only_once(source, ENTRY_BANNER)
    remove_lines = (
        'var _module = _interopRequireDefault(__webpack_require__(/*! ../../modules/import-export-customization/assets/js/module */ "../core/app/modules/import-export-customization/assets/js/module.js"));\n',
        'new _module.default();\n',
    )
    keep_lines = (
        'var _siteEditor = _interopRequireDefault(__webpack_require__(/*! ../../modules/site-editor/assets/js/site-editor */ "../core/app/modules/site-editor/assets/js/site-editor.js"));\n',
        'new _siteEditor.default();\n',
    )
    for line in (*remove_lines, *keep_lines):
        only_once(source, line)
    for line in remove_lines:
        source = source.replace(line, '')
    return source


def balanced_function_end(source, start):
    brace = source.find('{', start)
    if brace < 0:
        raise BundleShapeError('feature-lock function has no opening brace')
    depth = 0
    for index in range(brace, len(source)):
        if source[index] == '{':
            depth += 1
        elif source[index] == '}':
            depth -= 1
            if depth == 0:
                return index + 1
    raise BundleShapeError('feature-lock function has no closing brace')


def unlock_site_editor(source):
    factory_name = '../core/app/assets/js/hooks/use-feature-lock.js'

    def transform(factory):
        require_fragments = (
            'var _react = _interopRequireDefault(__webpack_require__(/*! react */ "react"));\n',
            'var _connectButton = _interopRequireDefault(__webpack_require__(/*! ../ui/connect-button */ "../core/app/assets/js/ui/connect-button.js"));\n',
            'var _utils = __webpack_require__(/*! ../utils */ "../core/app/assets/js/utils.js");\n',
        )
        for line in require_fragments:
            only_once(factory, line)
            factory = factory.replace(line, '')

        signature = 'function useFeatureLock(featureName) {'
        only_once(factory, signature)
        start = factory.index(signature)
        end = balanced_function_end(factory, start)
        replacement = '''function useFeatureLock(featureName) {
  return {
    isLocked: false,
    ConnectButton: () => null
  };
}'''
        return factory[:start] + replacement + factory[end:]

    return replace_factory(source, factory_name, transform)


def disable_unavailable_screenshots(source):
    factory_name = '../core/app/modules/site-editor/assets/js/hooks/use-templates-screenshot.js'

    def transform(factory):
        screenshot_require = 'var _useScreenshot = _interopRequireWildcard(__webpack_require__(/*! modules/screenshots/app/assets/js/hooks/use-screenshot */ "../modules/screenshots/app/assets/js/hooks/use-screenshot.js"));\n'
        only_once(factory, screenshot_require)
        factory = factory.replace(screenshot_require, '')

        signature = 'function useTemplatesScreenshot(templateType = null) {'
        only_once(factory, signature)
        start = factory.index(signature)
        end = balanced_function_end(factory, start)
        return factory[:start] + 'function useTemplatesScreenshot(templateType = null) {\n  return null;\n}' + factory[end:]

    return replace_factory(source, factory_name, transform)


def remove_elementor_help_links(source):
    links = (
        'https://go.elementor.com/app-theme-builder-template-load-issue',
        'https://go.elementor.com/app-theme-builder-conditions-load-issue',
        'https://go.elementor.com/app-theme-builder-import-issue',
        'https://go.elementor.com/app-theme-builder-load-issue',
    )
    for link in links:
        only_once(source, link)
        source = source.replace(link, '')
    return source


def replace_site_parts_info_dialog(source):
    factory_name = '../core/app/modules/site-editor/assets/js/pages/add-new.js'

    def transform(factory):
        insertion_anchor = 'var _useFeatureLock = _interopRequireDefault(__webpack_require__(/*! elementor-pro-app/hooks/use-feature-lock */ "../core/app/assets/js/hooks/use-feature-lock.js"));\n'
        only_once(factory, insertion_anchor)
        local_site_parts = '''function InfoButtonWithoutDocs(props) {
  const toggleButtonProps = {
    text: __('Info', 'elementor'),
    hideText: true,
    icon: 'eicon-info-circle e-site-part__info-toggle'
  };
  return /*#__PURE__*/_react.default.createElement(_appUi.ModalProvider, {
    toggleButtonProps,
    title: props.title
  }, /*#__PURE__*/_react.default.createElement(_appUi.CssGrid, {
    columns: 2,
    spacing: 60
  }, /*#__PURE__*/_react.default.createElement('section', null, /*#__PURE__*/_react.default.createElement('h3', null, props.type), /*#__PURE__*/_react.default.createElement('p', null, props.content), /*#__PURE__*/_react.default.createElement('div', {
    className: 'eps-modal__tip'
  }, /*#__PURE__*/_react.default.createElement('h3', null, __('Tip', 'elementor')), /*#__PURE__*/_react.default.createElement('p', null, props.tip))), /*#__PURE__*/_react.default.createElement('section', null, /*#__PURE__*/_react.default.createElement('h3', null, __('Watch Video', 'elementor')), /*#__PURE__*/_react.default.createElement('div', {
    className: 'video-wrapper'
  }, /*#__PURE__*/_react.default.createElement('iframe', {
    id: 'ytplayer',
    src: props.video_url,
    frameBorder: '0'
  })))));
}
InfoButtonWithoutDocs.propTypes = {
  content: PropTypes.string.isRequired,
  tip: PropTypes.string.isRequired,
  title: PropTypes.string.isRequired,
  type: PropTypes.string.isRequired,
  video_url: PropTypes.string.isRequired
};
function SitePartsWithoutDocs(props) {
  const { templateTypes } = _react.default.useContext(_siteEditor.TemplateTypesContext);
  return /*#__PURE__*/_react.default.createElement(_appUi.CssGrid, {
    className: 'e-site-editor__site-parts',
    colMinWidth: 200,
    spacing: 25
  }, templateTypes.map(item => /*#__PURE__*/_react.default.createElement(_siteEditor.SitePart, {
    actionButton: /*#__PURE__*/_react.default.createElement(InfoButtonWithoutDocs, {
      content: item.tooltip_data.content,
      tip: item.tooltip_data.tip,
      title: item.tooltip_data.title,
      type: item.title,
      video_url: item.tooltip_data.video_url
    }),
    thumbnail: item.urls.thumbnail,
    title: item.title,
    key: item.type
  }, /*#__PURE__*/_react.default.createElement(props.hoverElement, item))));
}
SitePartsWithoutDocs.propTypes = {
  hoverElement: PropTypes.func.isRequired
};
'''
        factory = factory.replace(insertion_anchor, insertion_anchor + local_site_parts)

        inherited_site_parts = '''_react.default.createElement(_siteEditor.SiteParts, {
    hoverElement: HoverElement
  })'''
        only_once(factory, inherited_site_parts)
        return factory.replace(
            inherited_site_parts,
            '''_react.default.createElement(SitePartsWithoutDocs, {
    hoverElement: HoverElement
  })''',
        )

    return replace_factory(source, factory_name, transform)


def rewrite_translation_domain(source):
    old_domain = "'elementor-pro'"
    count = source.count(old_domain)
    if not count:
        raise BundleShapeError('generated app has no Elementor Pro translation-domain arguments')
    return source.replace(old_domain, "'modulix'")


def shield_core_app_routes(source):
    factory_name = '../core/app/modules/site-editor/assets/js/site-editor.js'

    def transform(factory):
        site_editor_signature = 'function SiteEditor() {'
        only_once(factory, site_editor_signature)
        redirect_component = '''function CoreAppRedirect() {
  _react.default.useEffect(() => {
    _router2.default.appHistory.navigate('/site-editor');
  }, []);
  return null;
}
CoreAppRedirect.modulixRouteShield = true;
'''
        factory = factory.replace(site_editor_signature, redirect_component + site_editor_signature)

        wildcard_route = '''    _router2.default.addRoute({
      path: '/site-editor/*',
      component: SiteEditor
    });'''
        only_once(factory, wildcard_route)
        shielded_routes = '''    [
      '/site-editor/promotion',
      '/import/*',
      '/export/*',
      '/import-customization/*',
      '/export-customization/*',
      '/kit-library/*',
      '/onboarding/*'
    ].forEach(path => _router2.default.addRoute({
      path,
      component: CoreAppRedirect
    }));
'''
        factory = factory.replace(wildcard_route, shielded_routes + wildcard_route)

        inherited_not_found = '_react.default.createElement(_siteEditor.NotFound, {'
        only_once(factory, inherited_not_found)
        return factory.replace(
            inherited_not_found,
            '_react.default.createElement(CoreAppRedirect, {',
        )

    return replace_factory(source, factory_name, transform)


def drop_unreachable_factories(source):
    ranges = factory_ranges(source)
    dependencies = {
        name: {match.group('name') for match in WEBPACK_REQUIRE.finditer(source[start:end])}
        for name, (start, end) in ranges.items()
    }
    object_end = source.index(FACTORIES_END)
    roots = {match.group('name') for match in WEBPACK_REQUIRE.finditer(source[object_end:])}

    reachable = set()
    pending = list(roots)
    while pending:
        name = pending.pop()
        if name in reachable:
            continue
        reachable.add(name)
        pending.extend(dependencies.get(name, ()))

    removed = sorted(set(ranges) - reachable)
    for name in sorted(removed, key=lambda item: ranges[item][0], reverse=True):
        start, end = ranges[name]
        source = source[:start] + source[end:]
    return source, removed


def pro_version(pro_root):
    match = re.fullmatch(r'elementor-pro-(\d+(?:\.\d+)+)', pro_root.name)
    if not match:
        raise BundleShapeError('Pro root directory must be named elementor-pro-<version>')
    return match.group(1)


def generate(pro_root):
    version = pro_version(pro_root)
    js_source = (pro_root / 'assets' / 'js' / 'app.js').read_text()
    js = lift.transform(js_source, 'assets/js/app.js', version, LIFT_DATE)
    js = trim_entry(js)
    js = unlock_site_editor(js)
    js = disable_unavailable_screenshots(js)
    js = remove_elementor_help_links(js)
    js = replace_site_parts_info_dialog(js)
    js = shield_core_app_routes(js)
    js, removed = drop_unreachable_factories(js)
    js = rewrite_translation_domain(js)
    js = SOURCE_MAP.sub('\n', js)

    forbidden = (
        'import-export-customization',
        'Connect & Activate',
        'elementorConnect',
        'kit-library/cloud',
        'modules/screenshots',
        'go.elementor.com',
        "'elementor-pro'",
    )
    leftovers = [value for value in forbidden if value in js]
    if leftovers:
        raise BundleShapeError('server-dependent UI remains: ' + ', '.join(leftovers))
    if '__webpack_require__.e(' in js:
        raise BundleShapeError('generated app unexpectedly contains runtime chunks')

    css_source = (pro_root / 'assets' / 'css' / 'app.css').read_text()
    css = SOURCE_MAP.sub('\n', css_source)
    css = (
        f'/*! Modulix: derived from Elementor Pro {version}, assets/css/app.css. '
        f'Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later. '
        f'Modified by Modulix ({LIFT_DATE}): generated site-editor stylesheet. */\n'
        + css
    )
    return js, css, removed


def main(argv=None):
    parser = argparse.ArgumentParser()
    parser.add_argument('pro_root', type=pathlib.Path)
    parser.add_argument('--js-out', type=pathlib.Path, default=DEFAULT_JS_OUT)
    parser.add_argument('--css-out', type=pathlib.Path, default=DEFAULT_CSS_OUT)
    args = parser.parse_args(argv)

    try:
        js, css, removed = generate(args.pro_root)
    except (OSError, UnicodeError, BundleShapeError) as error:
        print(f'app bundle does not look as expected: {error}', file=sys.stderr)
        return 1

    args.js_out.parent.mkdir(parents=True, exist_ok=True)
    args.css_out.parent.mkdir(parents=True, exist_ok=True)
    args.js_out.write_text(js)
    args.css_out.write_text(css)
    print(f'wrote {args.js_out} and {args.css_out}; removed {len(removed)} unreachable factories')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
