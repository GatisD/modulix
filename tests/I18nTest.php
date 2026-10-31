<?php
use Modulix\I18n;
use PHPUnit\Framework\TestCase;

final class I18nTest extends TestCase {
    public function test_php_and_bundle_domains_are_explicit(): void {
        $this->assertSame('modulix', I18n::PHP_DOMAIN);
        $this->assertSame(['elementor', 'elementor-pro', 'modulix'], I18n::JS_DOMAINS);
        $this->assertSame([
            'modulix-admin-assets' => 'elementor-pro',
            'modulix-app' => 'modulix',
            'modulix-editor' => 'modulix',
            'modulix-editor-pro' => 'elementor-pro',
            'custom-code-metabox' => 'elementor-pro',
            'e-display-conditions' => 'modulix',
            'form-submission-admin' => 'elementor-pro',
        ], I18n::BUNDLE_DOMAINS);
    }

    public function test_plugin_declares_and_loads_its_php_catalog(): void {
        $source = (string) file_get_contents(dirname(__DIR__) . '/modulix.php');

        $this->assertStringContainsString(' * Text Domain: modulix', $source);
        $this->assertStringContainsString(' * Domain Path: /languages', $source);
        $this->assertStringContainsString('load_plugin_textdomain', $source);
        $this->assertStringContainsString("'/languages'", $source);
    }

    public function test_every_runtime_php_translation_uses_an_owned_or_integration_domain(): void {
        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg(dirname(__DIR__) . '/tools/i18n-pot.php')
            . ' --root=' . escapeshellarg(dirname(__DIR__))
            . ' --list-domains';
        exec($command . ' 2>&1', $output, $code);

        $this->assertSame(0, $code, implode("\n", $output));
        $this->assertSame(['mailpoet', 'modulix'], $output);
    }

    public function test_mailpoet_duplicate_subscriber_sentinel_uses_mailpoets_catalog(): void {
        $source = (string) file_get_contents(dirname(__DIR__) . '/modules/forms/actions/mailpoet3.php');

        $this->assertStringContainsString(
            "esc_html__( 'This subscriber already exists.', 'mailpoet' )",
            $source
        );
    }

    public function test_checked_in_catalog_is_current(): void {
        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg(dirname(__DIR__) . '/tools/i18n-pot.php')
            . ' --root=' . escapeshellarg(dirname(__DIR__))
            . ' --output=' . escapeshellarg(dirname(__DIR__) . '/languages/modulix.pot')
            . ' --check';
        exec($command . ' 2>&1', $output, $code);

        $this->assertSame(0, $code, implode("\n", $output));
    }

    public function test_release_build_includes_catalog_and_uninstaller(): void {
        $source = (string) file_get_contents(dirname(__DIR__) . '/tools/build.sh');

        $this->assertMatchesRegularExpression('/cp -R .*uninstall\.php.*languages/', $source);
    }
}
