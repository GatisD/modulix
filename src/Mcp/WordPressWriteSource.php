<?php
namespace Modulix\Mcp;

final class WordPressWriteSource implements WriteSource {
    private array $previousLocks = [];

    public function __construct(private ?object $plugin = null) {}

    public function getDocument(int $postId): ?WritableDocument {
        $manager = $this->manager();
        if ($postId <= 0 || $manager === null || !method_exists($manager, 'get')) {
            return null;
        }
        $document = $manager->get($postId, false);
        if (!is_object($document) || !method_exists($document, 'is_built_with_elementor')
            || !$document->is_built_with_elementor()) {
            return null;
        }
        $writable = new WordPressWritableDocument($document);
        return in_array($writable->postType(), ['page', 'post', 'elementor_library'], true) ? $writable : null;
    }

    public function getCreateValidationDocument(string $postType, string $documentType): ?WritableDocument {
        if (!in_array($postType, ['page', 'post', 'elementor_library'], true)) {
            return null;
        }
        $manager = $this->manager();
        if ($manager === null || !$this->documentTypeSupportsPostType($manager, $documentType, $postType)) {
            return null;
        }
        $class = $manager->get_document_type($documentType, false);
        try {
            $document = new $class(['post_id' => 0]);
        } catch (\Throwable) {
            return null;
        }
        if (method_exists($document, 'get_post')) {
            $post = $document->get_post();
            if (is_object($post)) {
                $post->ID = 0;
                $post->post_type = $postType;
                $post->post_status = 'draft';
                $post->post_title = '';
                $post->post_excerpt = '';
                $post->menu_order = 0;
                $post->comment_status = 'closed';
            }
        }
        return new WordPressWritableDocument($document);
    }

    public function createDraft(string $postType, string $documentType, string $title): ?WritableDocument {
        if (!in_array($postType, ['page', 'post', 'elementor_library'], true)) {
            return null;
        }
        $manager = $this->manager();
        if ($manager === null) {
            return null;
        }
        if ($postType === 'elementor_library'
            && !$this->documentTypeSupportsPostType($manager, $documentType, $postType)) {
            return null;
        }

        $meta = ['_elementor_edit_mode' => 'builder'];
        if ($postType === 'elementor_library') {
            $meta['_elementor_template_type'] = $documentType;
        }
        $postId = wp_insert_post([
            'post_title' => $title,
            'post_status' => 'draft',
            'post_type' => $postType,
            'meta_input' => $meta,
        ], true);
        if ((function_exists('is_wp_error') && is_wp_error($postId)) || !is_numeric($postId) || (int) $postId <= 0) {
            return null;
        }
        try {
            $document = $this->getDocument((int) $postId);
        } catch (\Throwable $error) {
            $this->deleteDocument((int) $postId);
            throw $error;
        }
        if ($document === null) {
            $this->deleteDocument((int) $postId);
        }
        return $document;
    }

    public function snapshotDocument(int $documentId): ?array {
        try {
            $post = $this->rawPost($documentId);
        } catch (\Throwable) {
            return null;
        }
        if ($post === null) {
            return null;
        }
        $postFields = [];
        foreach ([
            'post_content',
            'post_title',
            'post_excerpt',
            'post_status',
            'comment_status',
            'menu_order',
            'post_modified',
            'post_modified_gmt',
        ] as $field) {
            if (property_exists($post, $field)) {
                $postFields[$field] = $post->{$field};
            }
        }
        $meta = [];
        foreach (array_keys($this->trackedMetadata($this->allMetadata($documentId))) as $key) {
            $meta[$key] = $this->metadataValues($documentId, (string) $key);
        }
        return [
            'post' => $postFields,
            'meta' => $meta,
            'hashes' => [
                'post' => $this->stateHash($postFields),
                '_elementor_data' => $this->stateHash($meta['_elementor_data'] ?? []),
                '_elementor_page_settings' => $this->stateHash($meta['_elementor_page_settings'] ?? []),
                'meta' => $this->stateHash($meta),
            ],
        ];
    }

    public function createRevision(int $postId): int {
        $namespaced = __NAMESPACE__ . '\\wp_save_post_revision';
        if ($postId <= 0 || (!function_exists($namespaced) && !function_exists('wp_save_post_revision'))) {
            return 0;
        }
        $revision = function_exists($namespaced)
            ? wp_save_post_revision($postId)
            : \wp_save_post_revision($postId);
        if ((function_exists('is_wp_error') && is_wp_error($revision)) || !is_numeric($revision)) {
            return 0;
        }
        return max(0, (int) $revision);
    }

    public function restoreRevision(int $documentId, int $revisionId, array $snapshot): bool {
        $namespaced = __NAMESPACE__ . '\\wp_restore_post_revision';
        if ($documentId <= 0 || $revisionId <= 0
            || (!function_exists($namespaced) && !function_exists('wp_restore_post_revision'))) {
            return false;
        }
        try {
            function_exists($namespaced)
                ? wp_restore_post_revision($revisionId)
                : \wp_restore_post_revision($revisionId);
            $postFields = isset($snapshot['post']) && is_array($snapshot['post']) ? $snapshot['post'] : [];
            if (!$this->restorePostFields($documentId, $postFields)) {
                return false;
            }
            $beforeMeta = isset($snapshot['meta']) && is_array($snapshot['meta']) ? $snapshot['meta'] : [];
            $currentMeta = $this->trackedMetadata($this->allMetadata($documentId));
            foreach (array_unique(array_merge(array_keys($beforeMeta), array_keys($currentMeta))) as $key) {
                if (!$this->replaceMetadataValues($documentId, (string) $key, $beforeMeta[$key] ?? [])) {
                    return false;
                }
            }
            $this->clearPostCaches($documentId);
            $verified = $this->snapshotDocument($documentId);
            return $verified !== null
                && isset($snapshot['hashes'], $verified['hashes'])
                && $verified['hashes'] === $snapshot['hashes'];
        } catch (\Throwable) {
            return false;
        }
    }

    public function deleteDocument(int $documentId): bool {
        $namespaced = __NAMESPACE__ . '\\wp_delete_post';
        if ($documentId <= 0 || (!function_exists($namespaced) && !function_exists('wp_delete_post'))) {
            return false;
        }
        $deleted = function_exists($namespaced)
            ? wp_delete_post($documentId, true)
            : \wp_delete_post($documentId, true);
        return $deleted !== false && $deleted !== null;
    }

    public function lockDocument(int $documentId): bool {
        if ($documentId <= 0 || !$this->loadPostLockFunctions()) {
            return false;
        }
        $check = function_exists(__NAMESPACE__ . '\\wp_check_post_lock')
            ? wp_check_post_lock($documentId)
            : \wp_check_post_lock($documentId);
        if ($check) {
            return false;
        }
        $this->previousLocks[$documentId] = $this->metadataValues($documentId, '_edit_lock');
        $lock = function_exists(__NAMESPACE__ . '\\wp_set_post_lock')
            ? wp_set_post_lock($documentId)
            : \wp_set_post_lock($documentId);
        if ($lock === false) {
            unset($this->previousLocks[$documentId]);
            return false;
        }
        return true;
    }

    public function unlockDocument(int $documentId): void {
        if (!array_key_exists($documentId, $this->previousLocks)) {
            return;
        }
        $values = $this->previousLocks[$documentId];
        unset($this->previousLocks[$documentId]);
        $this->replaceMetadataValues($documentId, '_edit_lock', $values);
    }

    private function manager(): ?object {
        $plugin = $this->plugin;
        if ($plugin === null && class_exists('Elementor\\Plugin')) {
            $plugin = \Elementor\Plugin::$instance;
        }
        return is_object($plugin) && isset($plugin->documents) && is_object($plugin->documents)
            ? $plugin->documents
            : null;
    }

    private function rawPost(int $documentId): ?object {
        $manager = $this->manager();
        if ($documentId <= 0 || $manager === null || !method_exists($manager, 'get')) {
            return null;
        }
        $document = $manager->get($documentId, false);
        if (!is_object($document) || !method_exists($document, 'get_post')) {
            return null;
        }
        $post = $document->get_post();
        return is_object($post) ? $post : null;
    }

    private function allMetadata(int $documentId): array {
        if (function_exists(__NAMESPACE__ . '\\get_metadata')) {
            $meta = get_metadata('post', $documentId);
        } elseif (function_exists('get_metadata')) {
            $meta = \get_metadata('post', $documentId);
        } else {
            return [];
        }
        return is_array($meta) ? $meta : [];
    }

    private function trackedMetadata(array $meta): array {
        return array_filter(
            $meta,
            static fn(string $key): bool => str_starts_with($key, '_elementor')
                || in_array($key, ['_thumbnail_id', '_wp_page_template'], true),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function restorePostFields(int $documentId, array $postFields): bool {
        $modified = array_intersect_key($postFields, array_flip(['post_modified', 'post_modified_gmt']));
        $post = ['ID' => $documentId] + array_diff_key($postFields, $modified);
        if (function_exists(__NAMESPACE__ . '\\wp_update_post')) {
            $result = wp_update_post($post, true);
        } elseif (function_exists('wp_update_post')) {
            $result = \wp_update_post($post, true);
        } else {
            return false;
        }
        if ((function_exists('is_wp_error') && is_wp_error($result))
            || !is_numeric($result)
            || (int) $result !== $documentId) {
            return false;
        }
        if ($modified !== []) {
            global $wpdb;
            if (!is_object($wpdb) || !isset($wpdb->posts) || !method_exists($wpdb, 'update')
                || $wpdb->update($wpdb->posts, $modified, ['ID' => $documentId]) === false) {
                return false;
            }
        }
        return true;
    }

    private function clearPostCaches(int $documentId): void {
        if (function_exists(__NAMESPACE__ . '\\clean_post_cache')) {
            clean_post_cache($documentId);
        } elseif (function_exists('clean_post_cache')) {
            \clean_post_cache($documentId);
        }
        if (function_exists(__NAMESPACE__ . '\\wp_cache_delete')) {
            wp_cache_delete($documentId, 'post_meta');
        } elseif (function_exists('wp_cache_delete')) {
            \wp_cache_delete($documentId, 'post_meta');
        }
    }

    private function stateHash(array $state): string {
        return hash('sha256', serialize($state));
    }

    private function documentTypeSupportsPostType(object $manager, string $documentType, string $postType): bool {
        if ($documentType === '' || !method_exists($manager, 'get_document_type')) {
            return false;
        }
        $class = $manager->get_document_type($documentType, false);
        if (!is_string($class) || !class_exists($class) || !method_exists($class, 'get_property')) {
            return false;
        }
        $postTypes = $class::get_property('cpt');
        return $postType !== 'elementor_library'
            || (is_array($postTypes) && in_array($postType, $postTypes, true));
    }

    private function loadPostLockFunctions(): bool {
        if ((function_exists(__NAMESPACE__ . '\\wp_check_post_lock') || function_exists('wp_check_post_lock'))
            && (function_exists(__NAMESPACE__ . '\\wp_set_post_lock') || function_exists('wp_set_post_lock'))) {
            return true;
        }
        if (defined('ABSPATH')) {
            require_once ABSPATH . 'wp-admin/includes/post.php';
        }
        return function_exists('wp_check_post_lock') && function_exists('wp_set_post_lock');
    }

    private function metadataValues(int $documentId, string $key): array {
        if (function_exists(__NAMESPACE__ . '\\get_metadata')) {
            $values = get_metadata('post', $documentId, $key, false);
        } elseif (function_exists('get_metadata')) {
            $values = \get_metadata('post', $documentId, $key, false);
        } else {
            return [];
        }
        return is_array($values) ? $values : [];
    }

    private function replaceMetadataValues(int $documentId, string $key, array $values): bool {
        $deleted = function_exists(__NAMESPACE__ . '\\delete_metadata')
            ? delete_metadata('post', $documentId, $key)
            : (function_exists('delete_metadata') ? \delete_metadata('post', $documentId, $key) : false);
        if ($values === []) {
            return $deleted || $this->metadataValues($documentId, $key) === [];
        }
        foreach ($values as $value) {
            $added = function_exists(__NAMESPACE__ . '\\add_metadata')
                ? add_metadata('post', $documentId, $key, $value, false)
                : (function_exists('add_metadata') ? \add_metadata('post', $documentId, $key, $value, false) : false);
            if ($added === false) {
                return false;
            }
        }
        return $this->metadataValues($documentId, $key) === $values;
    }
}
