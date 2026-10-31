<?php
use Modulix\Security\IconArchive;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IconArchiveSecurityTest extends TestCase {
    public static function unsafeEntries(): array {
        return [
            ['../outside.css'],
            ['/absolute.css'],
            ['folder\\outside.css'],
            ["folder/zero\0.css"],
            ['folder/./file.css'],
            ['folder//file.css'],
            ['demo.html'],
            ['font/icon.svg'],
        ];
    }

    #[DataProvider('unsafeEntries')]
    public function test_rejects_unsafe_or_unneeded_entries(string $name): void {
        $this->assertFalse(IconArchive::entryNameAllowed($name));
    }

    public function test_allows_only_required_static_package_files(): void {
        foreach (['style.css', 'config.json', 'README.txt', 'font/icon.woff2', 'icons-reference.html', 'fonts/'] as $name) {
            $this->assertTrue(IconArchive::entryNameAllowed($name), $name);
        }
    }

    public function test_rejects_large_high_ratio_and_special_entries(): void {
        $this->assertFalse(IconArchive::entryAllowed('font.woff', IconArchive::MAX_FILE_BYTES + 1, 100, 0100000));
        $this->assertFalse(IconArchive::entryAllowed('font.woff', 10001, 100, 0100000));
        $this->assertFalse(IconArchive::entryAllowed('font.woff', 100, 100, 0120000));
        $this->assertTrue(IconArchive::entryAllowed('font.woff', 100, 100, 0100000));
        $this->assertTrue(IconArchive::entryAllowed('demo.html', 100, 100, 0100000));
    }

    public function test_icon_upload_uses_the_temporary_path_and_publishes_only_selected_files(): void {
        $upload = (string) file_get_contents(__DIR__ . '/../modules/assets-manager/asset-types/icons/custom-icons.php');
        $base = (string) file_get_contents(__DIR__ . '/../modules/assets-manager/asset-types/icons/icon-sets/icon-set-base.php');

        $this->assertStringNotContainsString('unlink( $filename )', $upload);
        $this->assertStringContainsString('is_uploaded_file( $file[\'tmp_name\'] )', $upload);
        $this->assertStringContainsString('IconArchive::entryAllowed', $upload);
        $this->assertStringContainsString('wp_generate_uuid4()', $upload);
        $this->assertStringContainsString('mkdir( $to, 0700, true )', $upload);
        $this->assertStringContainsString('$this->files_to_save', $base);
        $this->assertStringNotContainsString('@todo: save only needed files', $base);
    }
}
