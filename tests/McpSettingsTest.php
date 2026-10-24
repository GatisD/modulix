<?php
use Modulix\Mcp\Bootstrap;
use Modulix\Mcp\Settings;
use PHPUnit\Framework\TestCase;

final class McpSettingsTest extends TestCase {
    public function test_mcp_is_disabled_by_default_with_read_abilities_preselected(): void {
        $this->assertSame([
            'enabled' => false,
            'abilities' => [
                'modulix/site-overview',
                'modulix/list-documents',
                'modulix/get-document',
                'modulix/widget-schema',
            ],
        ], Settings::defaults());
    }

    public function test_settings_accept_only_known_abilities(): void {
        $this->assertSame([
            'enabled' => true,
            'abilities' => ['modulix/get-document'],
        ], Settings::sanitize([
            'enabled' => '1',
            'abilities' => ['modulix/get-document', 'modulix/publish', '../secret', ['nested']],
        ]));
    }

    public function test_nothing_registers_when_disabled_or_abilities_api_is_missing(): void {
        $this->assertFalse(Bootstrap::shouldRegister(Settings::defaults(), true, true));
        $this->assertFalse(Bootstrap::shouldRegister(['enabled' => true, 'abilities' => Settings::READ_ABILITIES], false, true));
        $this->assertTrue(Bootstrap::shouldRegister(['enabled' => true, 'abilities' => Settings::READ_ABILITIES], true, true));
    }

    public function test_nothing_registers_without_the_preview_constant_even_when_enabled(): void {
        $this->assertFalse(Bootstrap::shouldRegister(['enabled' => true, 'abilities' => Settings::READ_ABILITIES], true, false));
    }

    public function test_preview_is_off_unless_the_constant_is_exactly_true(): void {
        $this->assertFalse(Bootstrap::previewFrom(null));
        $this->assertFalse(Bootstrap::previewFrom('1'));
        $this->assertFalse(Bootstrap::previewFrom(1));
        $this->assertTrue(Bootstrap::previewFrom(true));
    }
}
