<?php
use Modulix\Security\PostTypeCapabilities;
use PHPUnit\Framework\TestCase;

final class PostTypeCapabilitiesTest extends TestCase {
    private const PRIMITIVE_CAPABILITIES = [
        'manage_options',
        'edit_posts',
        'read',
        'edit_theme_options',
        'unfiltered_html',
    ];

    /** @return array<string, array<string, string>> */
    private function postTypeCapabilities(): array {
        return [
            'elementor_font' => PostTypeCapabilities::restricted('elementor_font', 'manage_options'),
            'elementor_icons' => PostTypeCapabilities::restricted('elementor_icons', 'manage_options'),
            'elementor_snippet' => PostTypeCapabilities::restricted('elementor_snippet', 'manage_options'),
        ];
    }

    /** @param array<string, string> $postTypeMetaCaps */
    private function simulateWordPressMapMetaCap(string $capability, array $postTypeMetaCaps, ?array $postTypeCaps = null): array {
        if (isset($postTypeMetaCaps[$capability])) {
            if ($postTypeCaps === null) {
                return ['do_not_allow'];
            }

            $capability = $postTypeMetaCaps[$capability];
        }

        if ($postTypeCaps !== null) {
            $primitiveKey = match ($capability) {
                'edit_post' => 'edit_posts',
                'read_post' => 'read',
                'delete_post' => 'delete_posts',
                default => null,
            };

            if ($primitiveKey !== null) {
                return [$postTypeCaps[$primitiveKey]];
            }
        }

        return [$capability];
    }

    public function test_no_registered_meta_capability_is_a_primitive_capability(): void {
        $postTypeMetaCaps = [];

        foreach ($this->postTypeCapabilities() as $postType => $capabilities) {
            foreach (['edit_post', 'read_post', 'delete_post'] as $metaCapability) {
                $registered = $capabilities[$metaCapability];

                $this->assertNotContains($registered, self::PRIMITIVE_CAPABILITIES, $postType . ':' . $metaCapability);
                $this->assertArrayNotHasKey($registered, $postTypeMetaCaps, $postType . ':' . $metaCapability);
                $postTypeMetaCaps[$registered] = $metaCapability;
            }
        }
    }

    public function test_administrator_keeps_manage_options_after_all_post_types_are_registered(): void {
        $postTypeMetaCaps = [];
        $postTypes = $this->postTypeCapabilities();

        foreach ($postTypes as $capabilities) {
            foreach (['edit_post', 'read_post', 'delete_post'] as $metaCapability) {
                $postTypeMetaCaps[$capabilities[$metaCapability]] = $metaCapability;
            }
        }

        $required = $this->simulateWordPressMapMetaCap('manage_options', $postTypeMetaCaps);

        $this->assertSame(['manage_options'], $required);
        $this->assertTrue(count(array_diff($required, ['manage_options'])) === 0);

        foreach ($postTypes as $postType => $capabilities) {
            $editRequired = $this->simulateWordPressMapMetaCap('edit_post', $postTypeMetaCaps, $capabilities);

            $this->assertSame(['manage_options'], $editRequired, $postType);
            $this->assertTrue(count(array_diff($editRequired, ['manage_options'])) === 0, $postType);
        }
    }

    public function test_non_administrator_cannot_edit_fonts_icons_or_snippets(): void {
        $nonAdminCapabilities = ['edit_posts', 'read'];

        foreach ($this->postTypeCapabilities() as $postType => $capabilities) {
            $postTypeMetaCaps = [
                $capabilities['edit_post'] => 'edit_post',
                $capabilities['read_post'] => 'read_post',
                $capabilities['delete_post'] => 'delete_post',
            ];
            $required = $this->simulateWordPressMapMetaCap('edit_post', $postTypeMetaCaps, $capabilities);

            $this->assertSame(['manage_options'], $required, $postType);
            $this->assertFalse(count(array_diff($required, $nonAdminCapabilities)) === 0, $postType);
        }
    }

    public function test_every_plugin_post_type_uses_the_restricted_capability_map(): void {
        $sources = [
            'elementor_font' => __DIR__ . '/../modules/assets-manager/asset-types/fonts-manager.php',
            'elementor_icons' => __DIR__ . '/../modules/assets-manager/asset-types/icons-manager.php',
            'elementor_snippet' => __DIR__ . '/../modules/custom-code/module.php',
        ];

        foreach ($sources as $postType => $path) {
            $source = (string) file_get_contents($path);

            $this->assertStringContainsString(
                "'capabilities' => PostTypeCapabilities::restricted( self::CPT, self::CAPABILITY )",
                $source,
                $postType
            );
            $this->assertStringContainsString("'map_meta_cap' => true", $source, $postType);
        }
    }
}
