<?php
use PHPUnit\Framework\TestCase;

final class PublicWordingTest extends TestCase {
    private static function readNormalized(string $relativePath): string {
        $contents = file_get_contents(dirname(__DIR__) . '/' . $relativePath);
        self::assertNotFalse($contents, $relativePath);

        return preg_replace('/\s+/', ' ', trim($contents));
    }

    /** @dataProvider evidenceDocuments */
    public function test_release_evidence_is_bounded_and_discloses_known_limits(string $relativePath): void {
        $contents = self::readNormalized($relativePath);
        $configuration = 'Testing was recorded on 2026-10-31 in WordPress 7.1.3 with Elementor 4.3.5 and WooCommerce 11.2.1, using Elementor Pro 4.0.4 without a licence as the reference.';
        $limitations = 'The automated run did not cover the Custom Fonts or Custom Icons upload flow, or the WooCommerce settings in Site Settings.';

        $this->assertStringContainsString($configuration, $contents, $relativePath);
        $this->assertStringContainsString($limitations, $contents, $relativePath);
        $this->assertStringContainsString('test-kit limitation rather than a known Modulix defect', $contents, $relativePath);
    }

    public static function evidenceDocuments(): array {
        return [
            ['README.md'],
            ['ROADMAP.md'],
        ];
    }

    /** @dataProvider publicReleaseDocuments */
    public function test_public_release_wording_states_independence_and_avoids_broad_equivalence_claims(string $relativePath): void {
        $this->assertIndependentReleaseWording($relativePath);
    }

    public function test_internal_release_notes_wording_states_independence_and_avoids_broad_equivalence_claims(): void {
        $relativePath = 'docs/release/1.0.0-notes.md';

        if (! is_file(dirname(__DIR__) . '/' . $relativePath)) {
            $this->markTestSkipped('Internal release notes are not part of the public export.');
        }

        $this->assertIndependentReleaseWording($relativePath);
    }

    private function assertIndependentReleaseWording(string $relativePath): void {
        $contents = self::readNormalized($relativePath);

        $this->assertStringContainsString('not affiliated with, endorsed by or sponsored by Elementor', $contents, $relativePath);
        $this->assertStringNotContainsString('drop-in replacement', strtolower($contents), $relativePath);
        $this->assertStringNotContainsString('100% compatible', strtolower($contents), $relativePath);
        $this->assertStringNotContainsString('exactly the same', strtolower($contents), $relativePath);
    }

    public static function publicReleaseDocuments(): array {
        return [
            ['README.md'],
            ['CHANGELOG.md'],
        ];
    }

    public function test_package_description_uses_independent_compatibility_wording(): void {
        $composer = json_decode((string) file_get_contents(dirname(__DIR__) . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Independent open-source plugin for use with Elementor', $composer['description'] ?? null);
    }

    public function test_lifting_introduction_limits_compatibility_to_supported_features(): void {
        $contents = self::readNormalized('docs/LIFTING.md');
        $expected = 'Modulix includes modified GPL-licensed code from Elementor Pro 4.0.4 and preserves stored names and settings for supported features. Check compatibility and test the target site before switching.';

        $this->assertStringContainsString($expected, $contents);
        $this->assertStringNotContainsString('keep working after the switch', $contents);
    }
}
