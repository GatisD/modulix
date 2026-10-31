<?php
namespace Modulix\Readiness;

use Modulix\Mcp\AdminSettings;
use Modulix\NoUpsell;

/** Tools -> Modulix: runs the readiness check on request. Administrators only; nothing is changed or sent anywhere. */
final class AdminPage {
    private const SLUG = 'modulix-check';
    private const NONCE = 'modulix_readiness_check';
    private const AI_NONCE = 'modulix_keep_elementor_ai';

    public static function register(): void {
        add_action('admin_menu', static function (): void {
            add_management_page('Modulix', 'Modulix', 'manage_options', self::SLUG, [self::class, 'render']);
        });
    }

    public static function render(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div class="wrap"><h1>' . esc_html('Modulix readiness check') . '</h1>';
        echo '<p>' . esc_html('Lists the Elementor Pro widgets, dynamic tags, template types, theme locations and features your pages and templates use '
            . 'that Modulix does not cover yet, and the themes and plugins that call Elementor Pro. Nothing is changed '
            . 'and nothing leaves this site.') . '</p>';
        self::saveAiSetting();
        echo '<hr><h2>' . esc_html('Elementor AI') . '</h2>';
        echo '<form method="post">';
        wp_nonce_field(self::AI_NONCE);
        echo '<label><input type="checkbox" name="modulix_keep_elementor_ai" value="1" '
            . checked(NoUpsell::keep_elementor_ai(), true, false) . '> '
            . esc_html('Keep Elementor AI') . '</label>';
        echo '<p class="description">' . esc_html('Leave this off to hide Elementor AI, Angie and the custom widget generator. '
            . 'Turn it on only when this site uses Elementor AI credits and Connect.') . '</p>';
        submit_button('Save AI setting', 'secondary', 'modulix_save_ai', false);
        echo '</form>';
        if (\Modulix\Mcp\Bootstrap::preview()) {
            AdminSettings::render();
        }
        echo '<hr><h2>' . esc_html('Readiness check') . '</h2>';
        echo '<form method="post">';
        wp_nonce_field(self::NONCE);
        submit_button('Run the check', 'primary', 'modulix_run', false);
        echo '</form>';
        if (isset($_POST['modulix_run']) && check_admin_referer(self::NONCE)) {
            self::results();
        }
        echo '</div>';
    }

    private static function saveAiSetting(): void {
        if (!isset($_POST['modulix_save_ai'])) {
            return;
        }
        check_admin_referer(self::AI_NONCE);
        update_option(NoUpsell::KEEP_ELEMENTOR_AI_OPTION, isset($_POST['modulix_keep_elementor_ai']) ? '1' : '0');
        echo '<div class="notice notice-success inline"><p>' . esc_html('Elementor AI setting saved.') . '</p></div>';
    }

    private static function results(): void {
        $titles = [];
        $docs = (static function () use (&$titles): \Generator {
            foreach (WpSource::documents() as $doc) {
                $titles[$doc->id] = $doc->title;
                yield $doc;
            }
        })();
        $missing = Scanner::forModulix()->missing($docs);
        echo '<h2>' . esc_html('Pages and templates') . '</h2>';
        echo Report::html($missing, $titles, static fn(int $id): string => (string) get_edit_post_link($id, 'raw'));
        echo '<h2>' . esc_html('Themes and plugins') . '</h2>';
        echo Report::ecosystemHtml(WpSource::ecosystem());
    }
}
