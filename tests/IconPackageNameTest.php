<?php
use Modulix\Security\IconPackageName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IconPackageNameTest extends TestCase {
    public static function setUpBeforeClass(): void {
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/../');
        }

        require_once __DIR__ . '/../core/utils.php';
        require_once __DIR__ . '/../modules/assets-manager/asset-types/icons/icon-sets/icon-set-base.php';
        require_once __DIR__ . '/../modules/assets-manager/asset-types/icons/icon-sets/fontello.php';
    }

    public static function validNames(): array {
        return [
            'spaces' => ['My Icons'],
            'unicode' => ['Mans ikonu komplekts'],
            'dots without a dot segment' => ['icons.2026'],
        ];
    }

    #[DataProvider('validNames')]
    public function test_accepts_safe_package_names_unchanged(string $name): void {
        $this->assertSame($name, IconPackageName::validate($name));
    }

    public static function invalidNames(): array {
        return [
            'empty' => [''],
            'parent segment' => ['../x'],
            'forward slash' => ['a/b'],
            'backslash traversal' => ['..\\x'],
            'unix absolute path' => ['/var/www/icons'],
            'windows absolute path' => ['C:\\temp\\icons'],
            'nul byte' => ["icons\0outside"],
            'line feed' => ["icons\noutside"],
            'delete control character' => ["icons\x7Foutside"],
            'unicode control character' => ["icons\u{0085}outside"],
        ];
    }

    #[DataProvider('invalidNames')]
    public function test_rejects_unsafe_package_names(string $name): void {
        $this->expectException(InvalidArgumentException::class);

        IconPackageName::validate($name);
    }

    public function test_fontello_rejects_traversal_before_touching_the_source_stylesheet(): void {
        $root = sys_get_temp_dir() . '/modulix-icon-package-' . bin2hex(random_bytes(8));
        $archive = $root . '/archive/';
        $outside = $root . '/outside.css';
        mkdir($archive . 'css', 0777, true);
        file_put_contents($archive . 'config.json', json_encode(['name' => '../../outside']));
        file_put_contents($outside, 'a{margin-left: .2em;}');

        try {
            $iconSet = new Modulix\Modules\AssetsManager\AssetTypes\Icons\IconSets\Fontello($archive);

            try {
                $iconSet->handle_new_icon_set();
                $this->fail('Traversal package names must be rejected.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame('a{margin-left: .2em;}', file_get_contents($outside));
            }
        } finally {
            unlink($archive . 'config.json');
            rmdir($archive . 'css');
            rmdir($archive);
            unlink($outside);
            rmdir($root);
        }
    }
}
