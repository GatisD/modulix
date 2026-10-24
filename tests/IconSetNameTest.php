<?php
use Modulix\Security\IconSetName;
use PHPUnit\Framework\TestCase;

final class IconSetNameTest extends TestCase {
    public function test_keeps_only_directory_safe_characters(): void {
        $this->assertSame('MyIcons', IconSetName::sanitize('My Icons'));
        $this->assertSame('Myicons_2026', IconSetName::sanitize('My icons_2026'));
        $this->assertSame('escaped-set', IconSetName::sanitize('../../escaped-set'));
        $this->assertSame('varwwwicons', IconSetName::sanitize('/var/www/icons'));
        $this->assertSame('Ctempicons', IconSetName::sanitize('C:\\temp\\icons'));
        $this->assertSame('_leading-and-trailing_', IconSetName::sanitize('_leading-and-trailing_'));
    }

    public function test_rejects_a_name_that_becomes_empty(): void {
        $this->expectException(InvalidArgumentException::class);

        IconSetName::sanitize('../');
    }

    public function test_package_name_is_validated_separately_from_target_directory_sanitization(): void {
        $directory = __DIR__ . '/../modules/assets-manager/asset-types/icons/icon-sets';

        foreach (['fontello.php', 'icomoon.php', 'fontastic.php'] as $file) {
            $source = (string) file_get_contents($directory . '/' . $file);
            $this->assertStringNotContainsString('IconSetName::sanitize', $source, $file);
            $this->assertStringContainsString('IconPackageName::validate', $source, $file);
        }

        $base = (string) file_get_contents($directory . '/icon-set-base.php');
        $this->assertStringContainsString('IconSetName::sanitize( (string) $name )', $base);
    }

    public function test_fontello_uses_the_validated_original_package_name_for_its_stylesheet(): void {
        $source = (string) file_get_contents(
            __DIR__ . '/../modules/assets-manager/asset-types/icons/icon-sets/fontello.php'
        );

        $this->assertStringContainsString('$name = IconPackageName::validate( (string) $this->get_name() );', $source);
        $this->assertStringContainsString('$this->remove_fontello_styling( $name );', $source);
        $this->assertStringContainsString("'css/' . \$name . '.css'", $source);
        $this->assertStringNotContainsString('IconSetName::sanitize', $source);
    }

    public function test_icon_upload_rejects_an_empty_sanitized_name(): void {
        $source = (string) file_get_contents(__DIR__ . '/../modules/assets-manager/asset-types/icons/custom-icons.php');

        $this->assertStringContainsString('catch ( \\InvalidArgumentException $exception )', $source);
    }
}
