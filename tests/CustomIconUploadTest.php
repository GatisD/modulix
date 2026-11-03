<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CustomIconUploadTest extends TestCase {
    public static function packages(): array {
        return [['flat'], ['wrapped'], ['fontello']];
    }

    #[DataProvider('packages')]
    public function test_extracted_directory_is_accepted_by_the_matching_handler(string $package): void {
        $result = $this->runUpload($package, true);
        $this->assertTrue($result['directory_slash']);
        $this->assertTrue($result['root_slash']);
        $this->assertTrue($result['expected_directory']);
        $this->assertTrue($result['valid']);
        $this->assertTrue($result['complete_set']);
        $this->assertSame([], $result['leftovers']);
        $this->assertFalse($result['archive_exists']);
    }

    public static function providerPackages(): array {
        return [
            ['flat', 'mx-e2e-icon-', ['star'], ['fonts/mx-e2e-icons.woff', 'styles.css']],
            ['wrapped', 'mx-e2e-icon-', ['star'], ['fonts/mx-e2e-icons.woff', 'styles.css']],
            ['fontello-flat', 'test-', ['star'], ['css/test-icons.css', 'font/test-icons.woff']],
            ['fontello-wrapped', 'test-', ['star'], ['css/test-icons.css', 'font/test-icons.woff']],
            ['icomoon-flat', 'test-', ['star'], ['fonts/test-icons.woff', 'style.css']],
            ['icomoon-wrapped', 'test-', ['star'], ['fonts/test-icons.woff', 'style.css']],
        ];
    }

    #[DataProvider('providerPackages')]
    public function test_upload_publishes_icons_and_removes_the_entire_extraction_root(string $package, string $prefix, array $icons, array $assets): void {
        $result = $this->runUpload($package);
        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['count']);
        $this->assertSame($prefix, $result['config']['prefix']);
        $this->assertSame($icons, $result['config']['icons']);
        $assets = array_merge(['.htaccess', 'index.php'], $assets);
        sort($assets);
        $this->assertSame($assets, $result['assets']);
        $this->assertTrue($result['stylesheet_exists']);
        if (str_starts_with($package, 'icomoon-')) {
            $this->assertSame('test-icon', $result['config']['displayPrefix']);
        }
        $this->assertNotEmpty($result['fonts']);
        $this->assertNotEmpty($result['styles']);
        $this->assertSame([], $result['leftovers']);
        $this->assertFalse($result['archive_exists']);
    }

    public static function rejectedPackages(): array {
        return [
            ['unsupported', 'unsupported_zip_format'],
            ['empty', 'incompatible_archive'],
            ['invalid-name', 'unsupported_zip_format'],
            ['move-error', 'Could not move an icon package file.'],
            ['dirlist-error', 'incompatible_archive'],
            ['dirlist-throw', 'Directory listing failed.'],
        ];
    }

    #[DataProvider('rejectedPackages')]
    public function test_failed_upload_removes_the_extraction_root(string $package, string $status): void {
        $result = $this->runUpload($package);
        $this->assertSame($status, $result['status']);
        if ($package === 'dirlist-error') {
            $this->assertTrue($result['wp_error']);
        }
        if (str_starts_with($package, 'dirlist-')) {
            $this->assertTrue($result['listing_fault_injected']);
        }
        $this->assertSame([], $result['leftovers']);
        $this->assertFalse($result['archive_exists']);
    }

    public function test_root_realpath_failure_removes_the_created_directory_and_archive(): void {
        $result = $this->runUpload('flat', false, true);
        $this->assertTrue($result['realpath_fault_injected']);
        $this->assertTrue($result['wp_error']);
        $this->assertSame('archive_write_error', $result['status']);
        $this->assertFalse($result['archive_exists']);
        $this->assertSame([], $result['leftovers']);
    }

    private function runUpload(string $package, bool $extractOnly = false, bool $failRootRealpath = false): array {
        // Separate PHP process: WordPress shims must not affect the other offline tests.
        $script = 'chdir(' . var_export(dirname(__DIR__), true) . ');'
            . '$package = ' . var_export($package, true) . ';'
            . '$extractOnly = ' . var_export($extractOnly, true) . ';'
            . '$failRootRealpath = ' . var_export($failRootRealpath, true) . ';'
            . <<<'PHP'
require 'vendor/autoload.php';
define('ABSPATH', getcwd() . '/');
class WP_Error {
    public function __construct(public string $code, public string $message) {}
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function esc_html__($text, $domain = '') { return $text; }
function trailingslashit($path) { return rtrim($path, '/\\') . '/'; }
function get_temp_dir() { return $GLOBALS['root'] . '/temp/'; }
function wp_generate_uuid4() { return bin2hex(random_bytes(16)); }
function wp_mkdir_p($path) { return is_dir($path) || mkdir($path, 0777, true); }
function wp_handle_upload($file, $overrides) { return ['file' => $file['tmp_name']]; }
function sanitize_file_name($name) { return $name; }
function current_user_can($capability) { return true; }
function apply_filters($hook, $value) { return $value; }
function get_option($name, $default = false) { return []; }
function update_post_meta(...$args) {}
function wp_insert_attachment($attachment) { return 1; }
function wp_upload_dir() {
    return ['basedir' => $GLOBALS['root'] . '/uploads', 'baseurl' => '/uploads'];
}
class UploadFilesystem {
    public function dirlist($path, ...$args) {
        if (str_contains($path, '/modulix-icons-') && in_array($GLOBALS['package'], ['dirlist-error', 'dirlist-throw'], true)) {
            $GLOBALS['listing_fault_injected'] = is_file($path . 'styles.css');
            if ($GLOBALS['package'] === 'dirlist-throw') { throw new RuntimeException('Directory listing failed.'); }
            return false;
        }
        $files = [];
        foreach (scandir($path) as $name) {
            if ($name !== '.' && $name !== '..') { $files[$name] = ['name' => $name]; }
        }
        return $files;
    }
    public function is_dir($path) { return is_dir($path); }
    public function mkdir($path) { return wp_mkdir_p($path); }
    public function move($from, $to) {
        return $GLOBALS['package'] !== 'move-error' && rename($from, $to);
    }
    public function rmdir($path, $recursive = false) {
        if (!is_dir($path)) { return false; }
        foreach (scandir($path) as $name) {
            if ($name === '.' || $name === '..') { continue; }
            $child = trailingslashit($path) . $name;
            is_dir($child) ? $this->rmdir($child, true) : unlink($child);
        }
        return rmdir($path);
    }
}
require 'core/utils.php';
require 'base/editor-one-trait.php';
require 'modules/assets-manager/classes/assets-base.php';
require 'modules/assets-manager/asset-types/icons-manager.php';
if ($failRootRealpath) {
    eval(<<<'FAULT'
namespace Modulix\Modules\AssetsManager\AssetTypes\Icons;
function realpath($path) {
    if (str_contains($path, '/modulix-icons-')) {
        $GLOBALS['realpath_fault_injected'] = \is_dir($path);
        return false;
    }
    return \realpath($path);
}
FAULT);
}
require 'modules/assets-manager/asset-types/icons/custom-icons.php';
require 'modules/assets-manager/asset-types/icons/icon-sets/icon-set-base.php';
foreach (['fontastic', 'fontello', 'icomoon'] as $type) {
    require 'modules/assets-manager/asset-types/icons/icon-sets/' . $type . '.php';
}
$wp_filesystem = new UploadFilesystem();
$root = __DIR__ . '/tests/.icon-upload-' . bin2hex(random_bytes(8));
wp_mkdir_p(get_temp_dir());
$archive = $root . '/icons.zip';
try {
    // The E2E Fontastic archive as a public fixture (same bytes as ICON_ZIP_BASE64 in the internal e2e kit).
    $b64 = @file_get_contents('tests/fixtures/icons/mx-e2e-icons.zip.b64');
    if ($b64 === false || trim($b64) === '') { throw new RuntimeException('Icon fixture tests/fixtures/icons/mx-e2e-icons.zip.b64 not found'); }
    file_put_contents($archive, base64_decode(trim($b64), true));
    $zip = new ZipArchive();
    if ($zip->open($archive) !== true) { throw new RuntimeException('Cannot open fixture'); }
    $files = [];
    for ($i = 0; $i < $zip->numFiles; ++$i) { $files[$zip->getNameIndex($i)] = $zip->getFromIndex($i); }
    $zip->close();
    if ($package === 'fontello' || str_starts_with($package, 'fontello-') || $package === 'invalid-name') {
        $files = [
            'config.json' => json_encode([
                'name' => $package === 'invalid-name' ? '../invalid' : 'test-icons',
                'css_prefix_text' => 'test-', 'glyphs' => [['css' => 'star']],
            ]),
            'css/test-icons.css' => '.test-star:before { content: "x"; }',
            'font/test-icons.woff' => $files['fonts/mx-e2e-icons.woff'],
        ];
    } elseif (str_starts_with($package, 'icomoon-')) {
        $files = [
            'selection.json' => json_encode([
                'metadata' => ['name' => 'test-icons'],
                'preferences' => ['fontPref' => ['prefix' => 'test-', 'classSelector' => '.test-icon']],
                'icons' => [['properties' => ['name' => 'star']]],
            ]),
            'style.css' => '.test-star:before { content: "x"; }',
            'fonts/test-icons.woff' => $files['fonts/mx-e2e-icons.woff'],
        ];
    } elseif ($package === 'unsupported') {
        $files = ['unknown.json' => '{}'];
    } elseif ($package === 'empty') {
        // A valid ZIP whose only file is deliberately not extracted.
        $files = ['demo.html' => '<p>Preview</p>'];
    }
    if (!in_array($package, ['unsupported', 'empty', 'invalid-name'], true)) {
        $files['unused.json'] = '{}';
        $files['preview.css'] = '/* Not a provider stylesheet. */';
    }
    if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Cannot create package');
    }
    $prefix = (in_array($package, ['wrapped', 'fontello', 'invalid-name'], true) || str_ends_with($package, '-wrapped')) ? 'icon-package/' : '';
    foreach ($files as $name => $content) { $zip->addFromString($prefix . $name, $content); }
    $zip->close();
    $_FILES = ['zip_upload' => ['name' => 'icons.zip', 'tmp_name' => $archive]];
    $class = Modulix\Modules\AssetsManager\AssetTypes\Icons\Custom_Icons::class;
    $icons = (new ReflectionClass($class))->newInstanceWithoutConstructor();
    $output = [];
    if ($extractOnly) {
        $result = (new ReflectionMethod($class, 'upload_and_extract_zip'))->invoke($icons);
        if (is_wp_error($result)) { throw new RuntimeException($result->code); }
        $handlerClass = $package === 'fontello'
            ? Modulix\Modules\AssetsManager\AssetTypes\Icons\IconSets\Fontello::class
            : Modulix\Modules\AssetsManager\AssetTypes\Icons\IconSets\Fontastic::class;
        $handler = new $handlerClass($result['directory']);
        $output = [
            'directory_slash' => str_ends_with($result['directory'], '/'),
            'root_slash' => str_ends_with($result['extracted_to'], '/'),
            'expected_directory' => $result['directory'] === trailingslashit($result['extracted_to']) . $prefix,
            'valid' => $handler->is_valid(),
            'complete_set' => $handler->is_icon_set(),
        ];
        $wp_filesystem->rmdir($result['extracted_to'], true);
    } else {
        try {
            $result = $icons->custom_icons_upload_handler(['post_id' => 123]);
            $output['wp_error'] = is_wp_error($result);
            $output['config'] = is_wp_error($result) ? null : $result['config'];
            $output['status'] = is_wp_error($result) ? $result->code : 'success';
            $output['count'] = $result instanceof WP_Error ? null : $result['config']['count'];
        } catch (Throwable $exception) {
            $output['status'] = $exception->getMessage();
        }
        $output['fonts'] = glob($root . '/uploads/elementor/custom-icons/*/{fonts,font}/*.woff', GLOB_BRACE);
        $output['styles'] = glob($root . '/uploads/elementor/custom-icons/*/{style.css,styles.css,css/*.css}', GLOB_BRACE);
    }
    $output['listing_fault_injected'] = $GLOBALS['listing_fault_injected'] ?? false;
    $output['realpath_fault_injected'] = $GLOBALS['realpath_fault_injected'] ?? false;
    if (!$extractOnly) {
        $output['assets'] = [];
        $assetRoot = $root . '/uploads/elementor/custom-icons/' . ($output['config']['name'] ?? 'missing') . '/';
        if (is_dir($assetRoot)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($assetRoot, FilesystemIterator::SKIP_DOTS)) as $file) {
                $relative = substr($file->getPathname(), strlen($assetRoot));
                $output['assets'][] = $relative;
            }
        }
        sort($output['assets']);
        $output['stylesheet_exists'] = isset($output['config']['url']) && is_file($root . $output['config']['url']);
    }
    $output['leftovers'] = glob(get_temp_dir() . 'modulix-icons-*');
    $output['archive_exists'] = file_exists($archive);
    echo json_encode($output, JSON_THROW_ON_ERROR);
} finally {
    $wp_filesystem->rmdir($root, true);
}
PHP;
        exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script) . ' 2>&1', $output, $code);
        $this->assertSame(0, $code, implode("\n", $output));
        return json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR);
    }
}
