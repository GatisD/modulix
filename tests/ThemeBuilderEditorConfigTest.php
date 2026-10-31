<?php
use PHPUnit\Framework\TestCase;

final class ThemeBuilderEditorConfigTest extends TestCase {
    public function test_theme_template_editor_receives_the_conditions_runtime_config(): void {
        $source = (string) file_get_contents(__DIR__ . '/../modules/theme-builder/module.php');

        $this->assertStringContainsString('public function document_config( $config, $post_id )', $source);
        $this->assertStringContainsString("'elementor/document/config', [ \$this, 'document_config' ]", $source);
        $this->assertStringContainsString("'elementor/editor/init', [ \$this, 'on_elementor_editor_init' ]", $source);
        $this->assertStringContainsString("'theme_builder' => [", $source);
        $this->assertStringContainsString("'template_conditions' => ( new Classes\\Template_Conditions() )->get_config()", $source);
        $this->assertStringContainsString("'conditions' => \$conditions_manager->get_document_conditions( \$document )", $source);
        $this->assertStringContainsString('new Classes\\Conditions_Repeater()', $source);
        $manager = (string) file_get_contents(__DIR__ . '/../modules/theme-builder/classes/conditions-manager.php');
        $this->assertStringContainsString("'elementor/ajax/register_actions', [ \$this, 'register_ajax_actions' ]", $manager);
        $this->assertStringContainsString("'pro_theme_builder_save_conditions'", $manager);
        $this->assertStringContainsString('public function ajax_save_theme_template_conditions( $request )', $manager);
        $this->assertFileExists(__DIR__ . '/../modules/theme-builder/classes/template-conditions.php');
        $this->assertFileExists(__DIR__ . '/../modules/theme-builder/classes/conditions-repeater.php');
    }

    public function test_editor_registers_every_script_template_requested_by_the_real_bundle(): void {
        $root = dirname(__DIR__);
        $bundle = (string) file_get_contents($root . '/assets/js/modulix-editor-pro.js');
        preg_match_all(
            "/(?:template:\\s*|TemplateCache\\.get\\()'(#tmpl-elementor-(?:component-publish|theme-builder-conditions-(?:view|repeater-row)))'/",
            $bundle,
            $matches
        );
        $requested = array_values(array_unique($matches[1]));
        sort($requested);
        $this->assertSame([
            '#tmpl-elementor-component-publish',
            '#tmpl-elementor-theme-builder-conditions-repeater-row',
            '#tmpl-elementor-theme-builder-conditions-view',
        ], $requested);

        $probe = <<<'PHP'
namespace {
    define( 'ABSPATH', __DIR__ );
    define( 'MODULIX_MODULES_URL', 'https://assets.example.invalid/modules/' );
    function esc_html__( $text, $domain = null ) { return $text; }
    function esc_attr__( $text, $domain = null ) { return $text; }
}
namespace Elementor\Core\Base {
    abstract class Module {}
}
namespace Modulix\Base {
    abstract class Module_Base extends \Elementor\Core\Base\Module {}
}
namespace Modulix {
    final class ThemeBuilderTemplateRegistryProbe {
        public array $paths = [];
        public function add_template( string $path ): void { $this->paths[] = $path; }
    }
    final class Plugin {
        public static object $elementor;
        public static function elementor(): object { return self::$elementor; }
    }
}
namespace {
    $common = new \Modulix\ThemeBuilderTemplateRegistryProbe();
    \Modulix\Plugin::$elementor = (object) [ 'common' => $common ];
    require $argv[1];
    $reflection = new \ReflectionClass( \Modulix\Modules\ThemeBuilder\Module::class );
    $module = $reflection->newInstanceWithoutConstructor();
    $module->on_elementor_editor_init();
    ob_start();
    foreach ( $common->paths as $path ) { require $path; }
    echo ob_get_clean();
}
PHP;
        $command = escapeshellarg(PHP_BINARY)
            . ' -r ' . escapeshellarg($probe)
            . ' ' . escapeshellarg($root . '/modules/theme-builder/module.php')
            . ' 2>&1';
        exec($command, $output, $code);
        $this->assertSame(0, $code, implode("\n", $output));
        $rendered = implode("\n", $output);
        preg_match_all('/id="(tmpl-elementor-[^"]+)"/', $rendered, $renderedMatches);
        $registered = array_map(static fn (string $id): string => '#' . $id, $renderedMatches[1]);

        foreach ($requested as $template) {
            $this->assertContains($template, $registered, $template . ' was requested by the bundle but not registered at runtime.');
        }
    }

    public function test_lift_manifest_inventories_the_conditions_runtime_files(): void {
        $manifest = file(__DIR__ . '/../docs/lift-manifest.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ([
            'modules/theme-builder/classes/conditions-repeater.php',
            'modules/theme-builder/classes/template-conditions.php',
            'modules/theme-builder/views/panel-template.php',
        ] as $path) {
            $this->assertSame(1, count(array_keys($manifest, $path, true)), $path . ' must appear exactly once in the lift manifest.');
        }
    }
}
