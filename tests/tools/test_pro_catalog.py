import importlib.util, pathlib, tempfile, unittest

spec = importlib.util.spec_from_file_location("pc", pathlib.Path(__file__).parents[2] / "tools" / "pro-catalog.py")
pc = importlib.util.module_from_spec(spec); spec.loader.exec_module(pc)

WIDGET = "<?php\nclass Flip_Box extends Base_Widget {\n\tpublic function get_name() {\n\t\treturn 'flip-box';\n\t}\n}\n"
ABSTRACT = "<?php\nabstract class Base extends Base_Widget {\n\tpublic function get_name() {\n\t\treturn 'base';\n\t}\n}\n"
COMMENTED = ("<?php\nclass Site_Logo extends X {\n\tpublic function get_name() {\n\t\t// prefix avoids a clash\n"
             "\t\treturn 'theme-site-logo';\n\t}\n}\n")
TAG = "<?php\nclass Post_Title extends Tag {\n\tpublic function get_name() {\n\t\treturn 'post-title';\n\t}\n}\n"
POPUP_TAG = "<?php\nclass Tag extends Base {\n\tpublic function get_name() {\n\t\treturn 'popup';\n\t}\n}\n"
ATOMIC_MODULE = """<?php
namespace ElementorPro\\Modules\\AtomicForm;
use ElementorPro\\Modules\\AtomicForm\\Input\\Input;
use ElementorPro\\Modules\\AtomicForm\\Label\\Label;
use ElementorPro\\Modules\\AtomicForm\\Textarea\\Textarea;
use ElementorPro\\Modules\\AtomicForm\\Submit_Button\\Submit_Button;
use ElementorPro\\Modules\\AtomicForm\\Checkbox\\Checkbox;
class Module {
    private function register_widgets( $widgets_manager ) {
        $widgets_manager->register( new Input() );
        $widgets_manager->register( new Label() );
        $widgets_manager->register( new Textarea() );
        $widgets_manager->register( new Submit_Button() );
        $widgets_manager->register( new Checkbox() );
    }
}
"""


def atomic_widget(namespace, class_name, element_type):
    return f"""<?php
namespace ElementorPro\\Modules\\AtomicForm\\{namespace};
class {class_name} {{
    public static function get_element_type(): string {{
        return '{element_type}';
    }}
}}
"""


class ProCatalogTest(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        root = pathlib.Path(self.tmp.name)
        files = {"modules/flip-box/widgets/flip-box.php": WIDGET,
                 "modules/carousel/widgets/base.php": ABSTRACT,
                 "modules/theme-builder/widgets/site-logo.php": COMMENTED,
                 "modules/dynamic-tags/tags/post-title.php": TAG,
                 "modules/popup/tag.php": POPUP_TAG,
                 "modules/atomic-form/module.php": ATOMIC_MODULE,
                 "modules/atomic-form/input/input.php": atomic_widget("Input", "Input", "e-form-input"),
                 "modules/atomic-form/label/label.php": atomic_widget("Label", "Label", "e-form-label"),
                 "modules/atomic-form/textarea/textarea.php": atomic_widget("Textarea", "Textarea", "e-form-textarea"),
                 "modules/atomic-form/submit-button/submit-button.php": atomic_widget(
                     "Submit_Button", "Submit_Button", "e-form-submit-button"
                 ),
                 "modules/atomic-form/checkbox/checkbox.php": atomic_widget("Checkbox", "Checkbox", "e-form-checkbox")}
        for rel, src in files.items():
            p = root / rel
            p.parent.mkdir(parents=True, exist_ok=True)
            p.write_text(src)
        self.out = pc.render(str(root), "9.9.9")

    def tearDown(self):
        self.tmp.cleanup()

    def test_widgets_skip_abstract_classes_and_comments(self):
        self.assertIn("        'flip-box',", self.out)
        self.assertIn("        'theme-site-logo',", self.out)

    def test_widgets_include_directly_registered_atomic_elements_outside_widget_directories(self):
        for name in [
            "e-form-checkbox",
            "e-form-input",
            "e-form-label",
            "e-form-submit-button",
            "e-form-textarea",
        ]:
            self.assertIn(f"        '{name}',", self.out)

    def test_tags_include_module_level_tag_file(self):
        self.assertIn("    public const TAGS = [\n        'popup',\n        'post-title',\n    ];", self.out)

    def test_header_has_namespace_and_version(self):
        self.assertIn("namespace Modulix\\Readiness;", self.out)
        self.assertIn("public const VERSION = '9.9.9';", self.out)

    def test_fixed_catalogs_include_floating_buttons_and_theme_locations(self):
        self.assertIn("        'floating-buttons',", self.out)
        self.assertIn("    public const THEME_LOCATIONS = [", self.out)
        self.assertIn("        'floating_buttons',", self.out)
