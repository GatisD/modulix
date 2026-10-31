import subprocess
import sys
import tempfile
import unittest
import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]
TOOL = ROOT / 'tools' / 'app-bundle.py'
PRO_ROOT = Path.home() / 'modulix-upstream' / 'elementor-pro-4.0.4'
CORE_ROOT = Path.home() / 'modulix-upstream' / 'elementor-core-4.3.5'
SHIPPED_JS = ROOT / 'assets' / 'js' / 'modulix-app.js'
SHIPPED_CSS = ROOT / 'assets' / 'css' / 'modulix-app.css'
CORE_APP_REDIRECT_ROUTES = (
    '/site-editor/promotion',
    '/import/*',
    '/export/*',
    '/import-customization/*',
    '/export-customization/*',
    '/kit-library/*',
    '/onboarding/*',
)


class AppBundleTest(unittest.TestCase):
    @staticmethod
    def webpack_factory(source, name):
        start = source.index(f'/***/ "{name}"')
        end = source.find('/***/ "', start + 1)
        return source[start:end if end >= 0 else len(source)]

    @staticmethod
    def route_score(route):
        score = 0
        for segment in route.strip('/').split('/'):
            score += 4
            if segment.startswith(':'):
                score += 2
            elif segment.startswith('*'):
                score -= 5
            else:
                score += 3
        return score

    @staticmethod
    def route_matches(route, path):
        route_parts = route.strip('/').split('/')
        path_parts = path.strip('/').split('/')
        for index, segment in enumerate(route_parts):
            if segment.startswith('*'):
                return True
            if index >= len(path_parts):
                return False
            if not segment.startswith(':') and segment != path_parts[index]:
                return False
        return len(route_parts) == len(path_parts)

    def generate(self):
        temporary = tempfile.TemporaryDirectory()
        self.addCleanup(temporary.cleanup)
        js = Path(temporary.name) / 'modulix-app.js'
        css = Path(temporary.name) / 'modulix-app.css'
        result = subprocess.run(
            [sys.executable, str(TOOL), str(PRO_ROOT), '--js-out', str(js), '--css-out', str(css)],
            text=True,
            capture_output=True,
            check=False,
        )
        return result, js, css

    @unittest.skipUnless(PRO_ROOT.is_dir(), 'Elementor Pro 4.0.4 source is not available')
    def test_generator_keeps_local_site_editor_routes_and_removes_server_ui(self):
        result, js, _ = self.generate()

        self.assertEqual(0, result.returncode, result.stderr)
        source = js.read_text()
        for route in (
            "basePath + '/templates'",
            "basePath + '/templates/:type/*id'",
            "basePath + '/add-new'",
            "basePath + '/conditions/:id'",
            "basePath + '/import'",
        ):
            self.assertIn(route, source)
        for route in CORE_APP_REDIRECT_ROUTES:
            self.assertIn(f"'{route}'", source)
        self.assertEqual(2, source.count('.addRoute({'))
        self.assertNotIn("path: '/kit-library", source)
        self.assertNotIn("path: '/onboarding", source)
        self.assertNotIn("path: '/import-export", source)
        self.assertNotIn('import-export-customization', source)
        self.assertNotIn('Connect & Activate', source)
        self.assertNotIn('elementorConnect', source)
        self.assertNotIn('kit-library/cloud', source)
        self.assertNotIn('modules/screenshots', source)
        self.assertNotIn('go.elementor.com', source)
        self.assertNotIn('sourceMappingURL', source)

    @unittest.skipUnless(
        PRO_ROOT.is_dir() and CORE_ROOT.is_dir(),
        'Elementor Pro 4.0.4 and Core 4.3.5 sources are not available',
    )
    def test_modulix_routes_win_against_combined_core_app_route_table(self):
        result, js, _ = self.generate()

        self.assertEqual(0, result.returncode, result.stderr)
        modulix_source = js.read_text()
        core_packages = (CORE_ROOT / 'assets/js/app-packages.js').read_text()
        core_app = (CORE_ROOT / 'assets/js/app.js').read_text()

        self.assertLess(
            modulix_source.index("'/site-editor/promotion'"),
            modulix_source.index("path: '/site-editor/*'"),
        )
        self.assertIn('CoreAppRedirect.modulixRouteShield = true;', modulix_source)
        self.assertIn('component: CoreAppRedirect', modulix_source)
        redirect = modulix_source.split('function CoreAppRedirect()', 1)[1].split('CoreAppRedirect.modulixRouteShield', 1)[0]
        self.assertIn("_router2.default.appHistory.navigate('/site-editor')", redirect)
        self.assertNotIn('_router.Redirect', redirect)
        self.assertNotIn('_react.default.createElement(_siteEditor.NotFound', modulix_source)
        self.assertIn('path: "/site-editor/promotion"', core_packages)

        core_routes = re.findall(r'path:\s*["\'](/[^"\']+)["\']', core_packages + core_app)
        combined_routes = [
            *((route, 'CoreAppRedirect') for route in CORE_APP_REDIRECT_ROUTES),
            ('/site-editor/*', 'SiteEditor'),
            *((route, 'Core') for route in core_routes),
        ]
        paths = {
            '/site-editor/promotion': '/site-editor/promotion',
            '/import/*': '/import/process',
            '/export/*': '/export/process',
            '/import-customization/*': '/import-customization/content',
            '/export-customization/*': '/export-customization/process',
            '/kit-library/*': '/kit-library/cloud',
            '/onboarding/*': '/onboarding/siteName',
        }
        for route, path in paths.items():
            self.assertIn(route, core_routes)
            candidates = [
                (self.route_score(pattern), index, component)
                for index, (pattern, component) in enumerate(combined_routes)
                if self.route_matches(pattern, path)
            ]
            winner = sorted(candidates, key=lambda item: (-item[0], item[1]))[0]
            self.assertEqual('CoreAppRedirect', winner[2], f'{path} resolved to {winner}')

    @unittest.skipUnless(PRO_ROOT.is_dir(), 'Elementor Pro 4.0.4 source is not available')
    def test_core_app_redirect_runs_navigation_through_the_shared_history(self):
        result, js, _ = self.generate()

        self.assertEqual(0, result.returncode, result.stderr)
        source = js.read_text()
        component = source.split('function CoreAppRedirect()', 1)[1].split('function SiteEditor()', 1)[0]
        component = 'function CoreAppRedirect()' + component
        probe = f'''
const effects = [];
const calls = [];
const _react = {{ default: {{ useEffect: (callback) => effects.push(callback) }} }};
const sharedHistory = {{ navigate: (path) => calls.push(path) }};
const _router2 = {{ default: {{ appHistory: sharedHistory }} }};
{component}
const rendered = CoreAppRedirect();
if (rendered !== null || calls.length !== 0 || effects.length !== 1) process.exit(2);
effects[0]();
if (JSON.stringify(calls) !== JSON.stringify(['/site-editor'])) process.exit(3);
'''
        runtime = subprocess.run(
            ['node', '--input-type=module', '--eval', probe],
            capture_output=True,
            text=True,
        )
        self.assertEqual(0, runtime.returncode, runtime.stderr)

    @unittest.skipUnless(PRO_ROOT.is_dir(), 'Elementor Pro 4.0.4 source is not available')
    def test_bundle_translation_domain_matches_registered_catalog(self):
        result, js, _ = self.generate()

        self.assertEqual(0, result.returncode, result.stderr)
        source = js.read_text()
        self.assertGreater(source.count("'modulix'"), 0)
        self.assertNotIn("'elementor-pro'", source)

    @unittest.skipUnless(PRO_ROOT.is_dir(), 'Elementor Pro 4.0.4 source is not available')
    def test_add_new_info_dialog_omits_documentation_control_but_keeps_content(self):
        result, js, _ = self.generate()

        self.assertEqual(0, result.returncode, result.stderr)
        factory = self.webpack_factory(
            js.read_text(),
            '../core/app/modules/site-editor/assets/js/pages/add-new.js',
        )
        self.assertIn('function InfoButtonWithoutDocs', factory)
        self.assertIn('function SitePartsWithoutDocs', factory)
        self.assertIn('_appUi.ModalProvider', factory)
        self.assertIn('props.content', factory)
        self.assertIn('props.tip', factory)
        self.assertIn('props.video_url', factory)
        self.assertNotIn('_siteEditor.SiteParts', factory)
        self.assertNotIn('Learn More', factory)
        self.assertNotIn('props.docs', factory)

    @unittest.skipUnless(PRO_ROOT.is_dir(), 'Elementor Pro 4.0.4 source is not available')
    def test_generator_has_no_runtime_chunks_and_only_shipped_local_asset_reference(self):
        result, js, _ = self.generate()

        self.assertEqual(0, result.returncode, result.stderr)
        source = js.read_text()
        self.assertNotIn('__webpack_require__.e(', source)
        self.assertIn('/modules/theme-builder/assets/images/conditions-tab.svg', source)
        self.assertTrue((ROOT / 'modules/theme-builder/assets/images/conditions-tab.svg').is_file())

    @unittest.skipUnless(PRO_ROOT.is_dir(), 'Elementor Pro 4.0.4 source is not available')
    def test_shipped_outputs_are_byte_identical_to_fresh_generation(self):
        result, js, css = self.generate()

        self.assertEqual(0, result.returncode, result.stderr)
        self.assertTrue(SHIPPED_JS.is_file())
        self.assertTrue(SHIPPED_CSS.is_file())
        self.assertEqual(SHIPPED_JS.read_bytes(), js.read_bytes())
        self.assertEqual(SHIPPED_CSS.read_bytes(), css.read_bytes())


if __name__ == '__main__':
    unittest.main()
