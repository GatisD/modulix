import importlib.util, pathlib, tempfile, unittest

spec = importlib.util.spec_from_file_location("pc", pathlib.Path(__file__).parents[2] / "tools" / "pro-catalog.py")
pc = importlib.util.module_from_spec(spec); spec.loader.exec_module(pc)

WIDGET = "<?php\nclass Flip_Box extends Base_Widget {\n\tpublic function get_name() {\n\t\treturn 'flip-box';\n\t}\n}\n"
ABSTRACT = "<?php\nabstract class Base extends Base_Widget {\n\tpublic function get_name() {\n\t\treturn 'base';\n\t}\n}\n"
COMMENTED = ("<?php\nclass Site_Logo extends X {\n\tpublic function get_name() {\n\t\t// prefix avoids a clash\n"
             "\t\treturn 'theme-site-logo';\n\t}\n}\n")
TAG = "<?php\nclass Post_Title extends Tag {\n\tpublic function get_name() {\n\t\treturn 'post-title';\n\t}\n}\n"
POPUP_TAG = "<?php\nclass Tag extends Base {\n\tpublic function get_name() {\n\t\treturn 'popup';\n\t}\n}\n"


class ProCatalogTest(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        root = pathlib.Path(self.tmp.name)
        files = {"modules/flip-box/widgets/flip-box.php": WIDGET,
                 "modules/carousel/widgets/base.php": ABSTRACT,
                 "modules/theme-builder/widgets/site-logo.php": COMMENTED,
                 "modules/dynamic-tags/tags/post-title.php": TAG,
                 "modules/popup/tag.php": POPUP_TAG}
        for rel, src in files.items():
            p = root / rel
            p.parent.mkdir(parents=True, exist_ok=True)
            p.write_text(src)
        self.out = pc.render(str(root), "9.9.9")

    def tearDown(self):
        self.tmp.cleanup()

    def test_widgets_skip_abstract_classes_and_comments(self):
        self.assertIn("    public const WIDGETS = [\n        'flip-box',\n        'theme-site-logo',\n    ];", self.out)

    def test_tags_include_module_level_tag_file(self):
        self.assertIn("    public const TAGS = [\n        'popup',\n        'post-title',\n    ];", self.out)

    def test_header_has_namespace_and_version(self):
        self.assertIn("namespace Modulix\\Readiness;", self.out)
        self.assertIn("public const VERSION = '9.9.9';", self.out)
