<?php
namespace Modulix\Mcp;

final class WordPressReadSource implements ReadSource {
    public function __construct(private AccessGate $access) {}

    public function siteOverview(): array {
        $kit = [];
        $plugin = \Elementor\Plugin::$instance ?? null;
        if ($plugin && isset($plugin->kits_manager)) {
            $activeKit = $plugin->kits_manager->get_active_kit();
            if ($activeKit) {
                $kitId = method_exists($activeKit, 'get_id') ? (int) $activeKit->get_id() : 0;
                if ($kitId > 0 && $this->access->canReadDocument($kitId)) {
                    $kit = [
                        'id' => $kitId,
                        'colors' => [
                            'system' => (array) $activeKit->get_settings_for_display('system_colors'),
                            'custom' => (array) $activeKit->get_settings_for_display('custom_colors'),
                        ],
                        'fonts' => [
                            'system' => (array) $activeKit->get_settings('system_typography'),
                            'custom' => (array) $activeKit->get_settings('custom_typography'),
                        ],
                    ];
                }
            }
        }

        return [
            'elementor_version' => defined('ELEMENTOR_VERSION') ? (string) ELEMENTOR_VERSION : '',
            'modulix_version' => defined('MODULIX_VERSION') ? (string) MODULIX_VERSION : '',
            'kit' => $kit === [] ? (object) [] : $kit,
            'theme_templates' => $this->themeTemplates(),
        ];
    }

    public function listDocuments(int $page, int $perPage): array {
        $posts = get_posts([
            'post_type' => ['page', 'post', 'elementor_library'],
            'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
            'posts_per_page' => $perPage,
            'paged' => $page,
            'orderby' => 'modified',
            'order' => 'DESC',
            'meta_key' => '_elementor_edit_mode',
            'meta_value' => 'builder',
        ]);
        $documents = [];
        foreach ((array) $posts as $post) {
            $id = (int) $post->ID;
            if (!$this->access->canReadDocument($id)) {
                continue;
            }
            $documents[] = [
                'id' => $id,
                'title' => (string) get_the_title($post),
                'type' => (string) $post->post_type,
                'status' => (string) $post->post_status,
                'modified' => (string) $post->post_modified_gmt,
            ];
        }
        return $documents;
    }

    public function getDocument(int $id): ?array {
        if (!$this->access->canReadDocument($id) || 'builder' !== get_post_meta($id, '_elementor_edit_mode', true)) {
            return null;
        }
        $post = get_post($id);
        if (!$post) {
            return null;
        }
        $allowedPostTypes = ['page', 'post', 'elementor_library'];
        if (function_exists(__NAMESPACE__ . '\\apply_filters')) {
            $allowedPostTypes = apply_filters('modulix_mcp_document_post_types', $allowedPostTypes);
        } elseif (function_exists('apply_filters')) {
            $allowedPostTypes = \apply_filters('modulix_mcp_document_post_types', $allowedPostTypes);
        }
        $allowedPostTypes = is_array($allowedPostTypes)
            ? array_values(array_filter($allowedPostTypes, 'is_string'))
            : ['page', 'post', 'elementor_library'];
        if (!in_array($post->post_type, $allowedPostTypes, true)) {
            return null;
        }
        $raw = get_post_meta($id, '_elementor_data', true);
        if (is_string($raw)) {
            $elements = json_decode($raw, true);
        } else {
            $elements = $raw;
        }
        if (!is_array($elements)) {
            $elements = [];
        }
        $pageSettings = get_post_meta($id, '_elementor_page_settings', true);
        return [
            'id' => $id,
            'title' => (string) get_the_title($post),
            'type' => (string) $post->post_type,
            'status' => (string) $post->post_status,
            'modified' => (string) $post->post_modified_gmt,
            'elements' => $elements,
            'page_settings' => is_array($pageSettings) ? (object) $pageSettings : (object) [],
        ];
    }

    private function themeTemplates(): array {
        $posts = get_posts([
            'post_type' => 'elementor_library',
            'post_status' => ['publish', 'private'],
            'posts_per_page' => -1,
            'meta_query' => [[
                'key' => '_elementor_conditions',
                'compare' => 'EXISTS',
            ]],
        ]);
        $templates = [];
        foreach ((array) $posts as $post) {
            $id = (int) $post->ID;
            if (!$this->access->canReadDocument($id)) {
                continue;
            }
            $conditions = get_post_meta($id, '_elementor_conditions', true);
            $templates[] = [
                'id' => $id,
                'title' => (string) get_the_title($post),
                'type' => (string) get_post_meta($id, '_elementor_template_type', true),
                'conditions' => is_array($conditions) ? array_values($conditions) : [],
            ];
        }
        return $templates;
    }
}
