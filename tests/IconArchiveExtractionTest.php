<?php
use Modulix\Modules\AssetsManager\AssetTypes\Icons\Custom_Icons;
use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}
if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p(string $path): bool {
        return is_dir($path) || mkdir($path, 0777, true);
    }
}
if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = ''): string {
        return $text;
    }
}
require_once __DIR__ . '/../modules/assets-manager/classes/assets-base.php';
require_once __DIR__ . '/../modules/assets-manager/asset-types/icons/custom-icons.php';

final class IconArchiveExtractionTest extends TestCase {
    public function test_extraction_keeps_parser_assets_and_skips_unneeded_active_content(): void {
        $root = sys_get_temp_dir() . '/modulix-icon-archive-' . bin2hex(random_bytes(8));
        $archive = $root . '/icons.zip';
        $output = $root . '/out';
        mkdir($root, 0777, true);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($archive, ZipArchive::CREATE));
        $zip->addFromString('config.json', '{"name":"icons"}');
        $zip->addFromString('css/icons.css', '.icon:before{content:"x"}');
        $zip->addFromString('font/icons.woff', 'static-font-data');
        $zip->addFromString('demo.html', '<script>demo</script>');
        $zip->addFromString('font/icons.svg', '<svg></svg>');
        $zip->close();

        try {
            $method = new ReflectionMethod(Custom_Icons::class, 'extract_zip');
            $icons = (new ReflectionClass(Custom_Icons::class))->newInstanceWithoutConstructor();
            $result = $method->invoke($icons, $archive, $output);

            $this->assertTrue($result);
            $this->assertFileExists($output . '/config.json');
            $this->assertFileExists($output . '/css/icons.css');
            $this->assertFileExists($output . '/font/icons.woff');
            $this->assertFileDoesNotExist($output . '/demo.html');
            $this->assertFileDoesNotExist($output . '/font/icons.svg');
        } finally {
            $this->removeTree($root);
        }
    }

    private function removeTree(string $path): void {
        if (!is_dir($path)) {
            return;
        }
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
