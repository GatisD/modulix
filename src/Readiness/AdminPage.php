<?php
namespace Modulix\Readiness;

use Modulix\Mcp\AdminSettings;

/** Tools -> Modulix: runs the readiness check on request. Administrators only; nothing is changed or sent anywhere. */
final class AdminPage {
    private const SLUG = 'modulix-check';
    private const NONCE = 'modulix_readiness_check';

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
        echo '<p>' . esc_html('Lists the Elementor Pro widgets, dynamic tags, template types and features your pages and templates use '
            . 'that Modulix does not cover yet, and the themes and plugins that call Elementor Pro. Nothing is changed '
            . 'and nothing leaves this site.') . '</p>';
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
