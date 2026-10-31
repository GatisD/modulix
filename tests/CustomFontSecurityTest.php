<?php
use Modulix\Security\CssString;
use Modulix\Security\PostTypeCapabilities;
use PHPUnit\Framework\TestCase;

final class CustomFontSecurityTest extends TestCase {
    public function test_custom_font_meta_capabilities_are_unique_and_primitive_capabilities_require_site_management(): void {
        $capabilities = PostTypeCapabilities::restricted('elementor_font', 'manage_options');

        $this->assertSame([
            'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts',
            'delete_posts', 'publish_posts', 'read_private_posts', 'read',
            'delete_private_posts', 'delete_published_posts', 'delete_others_posts',
            'edit_private_posts', 'edit_published_posts', 'create_posts',
        ], array_keys($capabilities));
        $this->assertSame('edit_elementor_font', $capabilities['edit_post']);
        $this->assertSame('read_elementor_font', $capabilities['read_post']);
        $this->assertSame('delete_elementor_font', $capabilities['delete_post']);
        $this->assertSame(
            ['manage_options'],
            array_values(array_unique(array_slice($capabilities, 3)))
        );
    }

    public function test_css_string_encoding_keeps_hostile_font_titles_inside_the_string(): void {
        $encoded = CssString::quote("Safe Font';}body{--probe:1}/*");

        $this->assertSame("'Safe Font\\27 ;}body{--probe:1}/*'", $encoded);
        $this->assertStringNotContainsString("';}body", $encoded);
    }

    public function test_custom_fonts_use_the_capability_map_and_regenerate_stored_css(): void {
        $manager = (string) file_get_contents(__DIR__ . '/../modules/assets-manager/asset-types/fonts-manager.php');
        $fonts = (string) file_get_contents(__DIR__ . '/../modules/assets-manager/asset-types/fonts/custom-fonts.php');

        $this->assertStringContainsString("'capabilities' => PostTypeCapabilities::restricted( self::CPT, self::CAPABILITY )", $manager);
        $this->assertStringContainsString("'map_meta_cap' => true", $manager);
        $this->assertStringContainsString('CssString::quote( (string) $font_family )', $fonts);
        $this->assertGreaterThanOrEqual(3, substr_count($fonts, 'regenerate_font_face'));
        $this->assertStringContainsString('$font_faces = $this->get_font_face_by_font_family( $font_family );', $fonts);
        $this->assertStringNotContainsString("\tfont-family: '" . '" . $font_family', $fonts);
    }
}
