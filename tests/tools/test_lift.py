import importlib.util, pathlib, unittest

spec = importlib.util.spec_from_file_location("lift", pathlib.Path(__file__).parents[2] / "tools" / "lift.py")
lift = importlib.util.module_from_spec(spec); spec.loader.exec_module(lift)

SRC = """<?php
namespace ElementorPro\\Modules\\ThemeBuilder\\Classes;

use ElementorPro\\Plugin;

class X {
\tpublic function a() {
\t\t$c = '\\\\ElementorPro\\\\Modules\\\\ThemeBuilder\\\\Conditions\\\\' . $n;
\t\treturn esc_html__( 'Footer', 'elementor-pro' );
\t}
}
"""

class TransformTest(unittest.TestCase):
    def setUp(self):
        self.out = lift.transform(SRC, "modules/theme-builder/classes/x.php", "4.0.4", "2026-10-23")

    def test_namespace_renamed_everywhere(self):
        self.assertNotIn("ElementorPro\\", self.out)  # the marker names the origin; code references must be gone
        self.assertIn("namespace Modulix\\Modules\\ThemeBuilder\\Classes;", self.out)
        self.assertIn("use Modulix\\Plugin;", self.out)
        self.assertIn("'\\\\Modulix\\\\Modules\\\\ThemeBuilder\\\\Conditions\\\\'", self.out)

    def test_text_domain(self):
        self.assertIn("esc_html__( 'Footer', 'modulix' )", self.out)

    def test_marker_after_namespace(self):
        lines = self.out.splitlines()
        self.assertEqual(lines[1], "namespace Modulix\\Modules\\ThemeBuilder\\Classes;")
        self.assertTrue(lines[2].startswith("// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/classes/x.php."))
        self.assertTrue(lines[3].startswith("// Modified by Modulix (2026-10-23): "))

    def test_marker_without_namespace_goes_after_open_tag(self):
        out = lift.transform("<?php\nuse ElementorPro\\Plugin;\n", "modules/theme-builder/api.php", "4.0.4", "2026-10-23")
        self.assertTrue(out.splitlines()[1].startswith("// Modulix: derived from"))

    def test_non_php_copied_verbatim(self):
        self.assertEqual(lift.transform("GPL text", "license.txt", "4.0.4", "2026-10-23"), "GPL text")

class ConstantsAndAssetsTest(unittest.TestCase):
    def test_pro_constants_map_to_modulix(self):
        src = "<?php\nnamespace ElementorPro\\Modules\\Sticky;\n$a = ELEMENTOR_PRO_URL . 'assets/x'; $b = ELEMENTOR_PRO_ASSETS_URL; $c = ELEMENTOR_PRO_ASSETS_PATH; $d = ELEMENTOR_PRO_PATH; $e = ELEMENTOR_PRO_VERSION;\n"
        out = lift.transform(src, "modules/sticky/module.php", "4.0.4", "2026-10-23")
        self.assertNotIn("ELEMENTOR_PRO_", out)
        for c in ["MODULIX_URL . 'assets/x'", "MODULIX_ASSETS_URL", "MODULIX_ASSETS_PATH", "MODULIX_PATH", "MODULIX_VERSION"]:
            self.assertIn(c, out)

    def test_js_globals_renamed_and_marked(self):
        src = "var a = elementorProFrontend.config; window.ElementorProFrontendConfig; elementorProFrontendConfig;\n"
        out = lift.transform(src, "assets/js/frontend.js", "4.0.4", "2026-10-23")
        self.assertTrue(out.startswith("/*! Modulix: derived from Elementor Pro 4.0.4, assets/js/frontend.js."))
        self.assertIn("modulixFrontend.config", out)
        self.assertIn("window.ModulixFrontendConfig", out)
        body = out.split("\n", 1)[1]  # the marker line names the old globals on purpose
        self.assertNotIn("elementorProFrontend", body)
        self.assertNotIn("ElementorProFrontendConfig", body)

    def test_css_and_maps_copied_verbatim(self):
        self.assertEqual(lift.transform(".a{b:c}", "assets/css/modules/sticky.min.css", "4.0.4", "2026-10-23"), ".a{b:c}")


class MainTest(unittest.TestCase):
    def test_main_transforms_php_and_js_and_copies_the_rest(self):
        import tempfile
        with tempfile.TemporaryDirectory() as d:
            up, dest = pathlib.Path(d, "up"), pathlib.Path(d, "dest")
            (up / "assets/js").mkdir(parents=True); (up / "assets/css").mkdir(parents=True)
            (up / "assets/js/frontend.js").write_text("elementorProFrontend.x;\n")
            (up / "assets/css/a.css").write_text(".a{}")
            (up / "m.php").write_text("<?php\nnamespace ElementorPro\\M;\n")
            manifest = pathlib.Path(d, "manifest.txt"); manifest.write_text("assets/js/*.js\nassets/css/a.css\nm.php\n")
            lift.main(["lift.py", str(up), str(manifest), str(dest), "4.0.4", "2026-10-23"])
            self.assertIn("modulixFrontend.x", (dest / "assets/js/frontend.js").read_text())
            self.assertEqual((dest / "assets/css/a.css").read_text(), ".a{}")
            self.assertIn("namespace Modulix\\M;", (dest / "m.php").read_text())


class GuardTest(unittest.TestCase):
    def _setup(self, d):
        up, dest = pathlib.Path(d, "up"), pathlib.Path(d, "dest")
        up.mkdir(); dest.mkdir()
        (up / "m.php").write_text("<?php\nnamespace ElementorPro\\M;\n")
        manifest = pathlib.Path(d, "manifest.txt"); manifest.write_text("m.php\n")
        return up, dest, manifest

    def test_refuses_to_overwrite_a_modified_file(self):
        import tempfile
        with tempfile.TemporaryDirectory() as d:
            up, dest, manifest = self._setup(d)
            (dest / "m.php").write_text("<?php\n// our trimmed version\n")
            with self.assertRaises(SystemExit):
                lift.main(["lift.py", str(up), str(manifest), str(dest), "4.0.4", "2026-10-23"])
            self.assertEqual((dest / "m.php").read_text(), "<?php\n// our trimmed version\n")

    def test_identical_relift_and_force_are_allowed(self):
        import tempfile
        with tempfile.TemporaryDirectory() as d:
            up, dest, manifest = self._setup(d)
            lift.main(["lift.py", str(up), str(manifest), str(dest), "4.0.4", "2026-10-23"])
            lift.main(["lift.py", str(up), str(manifest), str(dest), "4.0.4", "2026-10-23"])
            (dest / "m.php").write_text("changed")
            lift.main(["lift.py", str(up), str(manifest), str(dest), "4.0.4", "2026-10-23", "--force"])
            self.assertIn("namespace Modulix", (dest / "m.php").read_text())


if __name__ == "__main__":
    unittest.main()
