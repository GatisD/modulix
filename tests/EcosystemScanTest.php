<?php
use Modulix\Readiness\EcosystemScan;
use PHPUnit\Framework\TestCase;

final class EcosystemScanTest extends TestCase {
    public function test_finds_class_names_and_constants(): void {
        $php = <<<'PHP'
<?php
use ElementorPro\Modules\ThemeBuilder\Module as TB;
if (defined('ELEMENTOR_PRO_VERSION') && class_exists('\\ElementorPro\\Plugin')) {
    \ElementorPro\Plugin::instance()->modules_manager->get_modules('theme-builder');
}
PHP;
        $this->assertSame(
            ['ELEMENTOR_PRO_VERSION', 'ElementorPro\\Modules\\ThemeBuilder\\Module', 'ElementorPro\\Plugin'],
            EcosystemScan::references($php)
        );
    }

    public function test_classify(): void {
        $has = fn(string $c): bool => in_array($c, ['Modulix\\Modules\\ThemeBuilder\\Module', \Modulix\Compat\ProPluginShim::class], true);
        $this->assertSame(
            ['provided' => ['ElementorPro\\Modules\\ThemeBuilder\\Module', 'ElementorPro\\Plugin'],
             'missing' => ['ElementorPro\\License\\API'], 'constants' => ['ELEMENTOR_PRO_VERSION']],
            EcosystemScan::classify(['ELEMENTOR_PRO_VERSION', 'ElementorPro\\License\\API',
                'ElementorPro\\Modules\\ThemeBuilder\\Module', 'ElementorPro\\Plugin'], $has)
        );
    }

    public function test_modulix_has_checks_files_without_loading_code(): void {
        $has = EcosystemScan::modulixHas(dirname(__DIR__));
        $this->assertTrue($has('Modulix\\Modules\\ThemeBuilder\\Module'));
        $this->assertTrue($has(\Modulix\Compat\ProPluginShim::class));
        $this->assertFalse($has('Modulix\\License\\API'));
        $this->assertFalse($has('Other\\Thing'));
        $this->assertFalse(class_exists('Modulix\\Modules\\ThemeBuilder\\Module', false));
    }

    public function test_scan_dir_skips_vendor(): void {
        $dir = sys_get_temp_dir() . '/modulix-eco-' . bin2hex(random_bytes(4));
        mkdir("$dir/inc", 0777, true);
        mkdir("$dir/vendor/x", 0777, true);
        file_put_contents("$dir/inc/a.php", '<?php \ElementorPro\Plugin::instance();');
        file_put_contents("$dir/vendor/x/b.php", '<?php \ElementorPro\License\API::x();');
        try {
            $this->assertSame(['ElementorPro\\Plugin'], EcosystemScan::scanDir($dir));
        } finally {
            unlink("$dir/inc/a.php");
            unlink("$dir/vendor/x/b.php");
            rmdir("$dir/vendor/x");
            rmdir("$dir/vendor");
            rmdir("$dir/inc");
            rmdir($dir);
        }
    }
}
