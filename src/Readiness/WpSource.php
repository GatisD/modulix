<?php
namespace Modulix\Readiness;

/** Reads Elementor documents and the active themes and plugins from WordPress. Read-only. */
final class WpSource {
    /** @return \Generator<Document> every published, draft, private or scheduled Elementor document and Custom Code snippet */
    public static function documents(int $batch = 200): \Generator {
        global $wpdb;
        $last = 0;
        do {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT p.ID, p.post_title, p.post_type FROM {$wpdb->posts} p
                 WHERE p.ID > %d AND p.post_type <> 'revision'
                   AND p.post_status IN ('publish', 'draft', 'private', 'future')
                   AND (p.post_type = 'elementor_snippet' OR EXISTS (SELECT 1 FROM {$wpdb->postmeta} m
                        WHERE m.post_id = p.ID AND m.meta_key IN ('_elementor_data', '_elementor_page_settings')))
                 ORDER BY p.ID LIMIT %d",
                $last,
                $batch
            ));
            foreach ($rows as $row) {
                $last = (int) $row->ID;
                $data = json_decode((string) get_post_meta($last, '_elementor_data', true), true);
                $settings = get_post_meta($last, '_elementor_page_settings', true);
                $type = $row->post_type === 'elementor_snippet'
                    ? 'code_snippet'
                    : (string) get_post_meta($last, '_elementor_template_type', true);
                $location = (string) get_post_meta($last, '_elementor_location', true);
                yield new Document($last, (string) $row->post_title, $type,
                    is_array($data) ? $data : [], is_array($settings) ? $settings : [], $location);
            }
        } while (count($rows) === $batch);
    }

    /** @return array<string, array{provided: string[], missing: string[], constants: string[]}> label => result */
    public static function ecosystem(): array {
        $dirs = [];
        foreach ((array) get_option('active_plugins', []) as $basename) {
            $slug = dirname((string) $basename);
            if ($slug === '.' || in_array($slug, ['elementor', 'elementor-pro', 'modulix'], true)) {
                continue;
            }
            $dirs['Plugin: ' . $slug] = WP_PLUGIN_DIR . '/' . $slug;
        }
        $dirs['Theme: ' . get_stylesheet()] = get_stylesheet_directory();
        if (get_template() !== get_stylesheet()) {
            $dirs['Theme: ' . get_template()] = get_template_directory();
        }
        $has = EcosystemScan::modulixHas(dirname(__DIR__, 2));
        $out = [];
        foreach ($dirs as $label => $dir) {
            if (is_dir($dir)) {
                $out[$label] = EcosystemScan::classify(EcosystemScan::scanDir($dir), $has);
            }
        }
        return $out;
    }
}
