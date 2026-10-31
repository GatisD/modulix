<?php
namespace Modulix\Mcp;

final class AdminSettings {
    private const NONCE = 'modulix_mcp_settings';

    public static function render(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (isset($_POST['modulix_mcp_save']) && check_admin_referer(self::NONCE)) {
            $raw = isset($_POST['modulix_mcp']) && is_array($_POST['modulix_mcp'])
                ? wp_unslash($_POST['modulix_mcp'])
                : [];
            update_option(Settings::OPTION, Settings::sanitize($raw), false);
            echo '<div class="notice notice-success inline"><p>' . esc_html('MCP settings saved.') . '</p></div>';
        }
        $settings = Settings::load();
        echo '<hr><h2>' . esc_html('MCP abilities') . '</h2>';
        echo '<p>' . esc_html('Expose selected Modulix abilities through the installed WordPress MCP Adapter. Disabled by default; write abilities are never preselected.') . '</p>';
        echo '<form method="post">';
        wp_nonce_field(self::NONCE);
        echo '<p><label><input type="checkbox" name="modulix_mcp[enabled]" value="1" '
            . checked(true, $settings['enabled'], false) . '> ' . esc_html('Enable MCP abilities') . '</label></p>';
        echo '<fieldset><legend class="screen-reader-text">' . esc_html('Exposed abilities') . '</legend>';
        foreach (array_merge(Settings::READ_ABILITIES, Settings::OPTIONAL_READ_ABILITIES, Settings::WRITE_ABILITIES) as $ability) {
            echo '<p><label><input type="checkbox" name="modulix_mcp[abilities][]" value="' . esc_attr($ability) . '" '
                . checked(true, in_array($ability, $settings['abilities'], true), false) . '> '
                . esc_html($ability) . '</label></p>';
        }
        echo '</fieldset>';
        submit_button('Save MCP settings', 'secondary', 'modulix_mcp_save', false);
        echo '</form>';
        self::renderChangeLog();
    }

    private static function renderChangeLog(): void {
        $rows = ChangeLog::wordpress()->recent();
        echo '<h3>' . esc_html('Recent MCP changes') . '</h3>';
        if ($rows === []) {
            echo '<p>' . esc_html('No MCP changes have been recorded.') . '</p>';
            return;
        }
        echo '<table class="widefat striped"><thead><tr>';
        foreach (['Time', 'User', 'Ability', 'Document', 'Status', 'Revision', 'Summary', 'Size'] as $heading) {
            echo '<th scope="col">' . esc_html($heading) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr>';
            foreach (['created_at', 'user_id', 'ability', 'document_id', 'status', 'revision_id', 'summary', 'size'] as $key) {
                echo '<td>' . esc_html((string) ($row[$key] ?? '')) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}
