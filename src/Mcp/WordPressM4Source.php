<?php
namespace Modulix\Mcp;

use Modulix\Readiness\Report;
use Modulix\Readiness\Scanner;
use Modulix\Readiness\WpSource;

final class WordPressM4Source implements M4Source {
    private WordPressWriteSource $writes;

    public function __construct(?WordPressWriteSource $writes = null, private ?object $plugin = null) {
        $this->writes = $writes ?? new WordPressWriteSource($plugin);
    }

    public function getDocument(int $postId): ?WritableDocument {
        return $this->writes->getDocument($postId);
    }

    public function activeKit(): ?WritableDocument {
        $plugin = $this->elementor();
        if ($plugin === null || !isset($plugin->kits_manager) || !is_object($plugin->kits_manager)
            || !method_exists($plugin->kits_manager, 'get_active_kit')) {
            return null;
        }
        $kit = $plugin->kits_manager->get_active_kit();
        if (!is_object($kit)) {
            return null;
        }
        $kitId = method_exists($kit, 'get_main_id')
            ? (int) $kit->get_main_id()
            : (method_exists($kit, 'get_id') ? (int) $kit->get_id() : 0);
        if ($kitId > 0 && isset($plugin->documents) && is_object($plugin->documents)
            && method_exists($plugin->documents, 'get')) {
            $fresh = $plugin->documents->get($kitId, false);
            if (is_object($fresh)) {
                $kit = $fresh;
            }
        }
        $document = new WordPressWritableDocument($kit);
        return $document->id() > 0 ? $document : null;
    }

    public function conditions(int $postId): ?array {
        if ($this->themeDocument($postId) === null) {
            return null;
        }
        if (function_exists(__NAMESPACE__ . '\\get_post_meta')) {
            $conditions = get_post_meta($postId, '_elementor_conditions', true);
        } elseif (function_exists('get_post_meta')) {
            $conditions = \get_post_meta($postId, '_elementor_conditions', true);
        } else {
            return null;
        }
        if ($conditions === '' || $conditions === null || $conditions === false) {
            return [];
        }
        if (!is_array($conditions)) {
            return null;
        }
        $clean = array_values(array_filter($conditions, 'is_string'));
        return count($clean) === count($conditions)
            ? $clean
            : null;
    }

    public function conditionRegistry(): array {
        $manager = $this->conditionsManager();
        if ($manager === null || !method_exists($manager, 'get_conditions_config')) {
            return [];
        }
        $config = $manager->get_conditions_config();
        if ((!is_array($config) || $config === []) && method_exists($manager, 'register_conditions')) {
            $manager->register_conditions();
            $config = $manager->get_conditions_config();
        }
        if (!is_array($config)) {
            return [];
        }
        $registry = [];
        foreach ($config as $name => $condition) {
            if (is_string($name) && $name !== '' && is_array($condition)) {
                $registry[$name] = $condition;
            }
        }
        return $registry;
    }

    public function conditionTarget(string $object, int $targetId): ?array {
        if ($targetId <= 0) {
            return null;
        }
        if ($object === 'post') {
            $post = get_post($targetId);
            $postType = get_post_type($targetId);
            return is_object($post) && is_string($postType) && $postType !== ''
                ? ['object' => 'post', 'id' => $targetId, 'post_type' => $postType]
                : null;
        }
        if ($object === 'tax') {
            $term = get_term($targetId);
            return is_object($term) && isset($term->taxonomy) && is_string($term->taxonomy)
                ? ['object' => 'tax', 'id' => $targetId, 'taxonomy' => $term->taxonomy]
                : null;
        }
        if ($object === 'author') {
            $user = get_user_by('id', $targetId);
            return is_object($user)
                ? ['object' => 'author', 'id' => $targetId]
                : null;
        }
        return null;
    }

    public function saveConditions(int $postId, array $conditions): bool {
        $manager = $this->conditionsManager();
        if ($manager === null || $this->themeDocument($postId) === null
            || !method_exists($manager, 'save_conditions')) {
            return false;
        }
        $parsed = [];
        foreach ($conditions as $condition) {
            if (!is_string($condition)) {
                return false;
            }
            [$type, $name, $subName, $subId] = array_pad(explode('/', $condition, 4), 4, '');
            $parsed[] = [
                'type' => $type,
                'name' => $name,
                'sub_name' => $subName,
                'sub_id' => $subId,
            ];
        }
        return false !== $manager->save_conditions($postId, $parsed)
            && $this->conditions($postId) === array_values($conditions);
    }

    public function status(int $postId): ?string {
        if ($postId <= 0 || $this->getDocument($postId) === null) {
            return null;
        }
        if (function_exists(__NAMESPACE__ . '\\get_post_status')) {
            $status = get_post_status($postId);
        } elseif (function_exists('get_post_status')) {
            $status = \get_post_status($postId);
        } else {
            return null;
        }
        return is_string($status) && $status !== '' ? $status : null;
    }

    public function saveStatus(int $postId, string $status): bool {
        if ($postId <= 0 || !in_array($status, ['publish', 'draft'], true)) {
            return false;
        }
        $post = ['ID' => $postId, 'post_status' => $status];
        if (function_exists(__NAMESPACE__ . '\\wp_update_post')) {
            $result = wp_update_post($post, true);
        } elseif (function_exists('wp_update_post')) {
            $result = \wp_update_post($post, true);
        } else {
            return false;
        }
        return !(function_exists('is_wp_error') && is_wp_error($result))
            && is_numeric($result)
            && (int) $result === $postId
            && $this->status($postId) === $status;
    }

    public function previewUrl(int $postId): ?string {
        $plugin = $this->elementor();
        if ($postId <= 0 || $plugin === null || !isset($plugin->documents) || !is_object($plugin->documents)
            || !method_exists($plugin->documents, 'get')) {
            return null;
        }
        $document = $plugin->documents->get($postId, false);
        if (!is_object($document) || !method_exists($document, 'get_wp_preview_url')) {
            return null;
        }
        $url = $document->get_wp_preview_url();
        if (!is_string($url) || $url === '') {
            return null;
        }
        if (function_exists(__NAMESPACE__ . '\\remove_query_arg')) {
            $url = remove_query_arg('preview_nonce', $url);
        } elseif (function_exists('remove_query_arg')) {
            $url = \remove_query_arg('preview_nonce', $url);
        }
        return is_string($url) ? $url : null;
    }

    public function readiness(): array {
        $titles = [];
        $documents = (static function () use (&$titles): \Generator {
            foreach (WpSource::documents() as $document) {
                $titles[$document->id] = $document->title;
                yield $document;
            }
        })();
        $missing = Scanner::forModulix()->missing($documents);
        $rows = [];
        foreach ($missing as $item => $ids) {
            $documentTitles = [];
            foreach ($ids as $id) {
                $documentTitles[(string) $id] = $titles[$id] ?? '';
            }
            $rows[] = [
                'item' => $item,
                'label' => Report::label($item),
                'documents' => count($ids),
                'document_ids' => array_values($ids),
                'document_titles' => $documentTitles,
            ];
        }
        $ecosystem = WpSource::ecosystem();
        $ready = $rows === [];
        foreach ($ecosystem as $result) {
            if (($result['missing'] ?? []) !== [] || ($result['constants'] ?? []) !== []) {
                $ready = false;
                break;
            }
        }
        return ['ready' => $ready, 'missing' => $rows, 'ecosystem' => $ecosystem];
    }

    public function snapshotDocument(int $documentId): ?array {
        return $this->writes->snapshotDocument($documentId);
    }

    public function createRevision(int $postId): int {
        return $this->writes->createRevision($postId);
    }

    public function restoreRevision(int $documentId, int $revisionId, array $snapshot): bool {
        if (!$this->writes->restoreRevision($documentId, $revisionId, $snapshot)) {
            return false;
        }
        try {
            $manager = $this->conditionsManager();
            if ($manager !== null) {
                if (method_exists($manager, 'get_cache')) {
                    $cache = $manager->get_cache();
                    if (is_object($cache) && method_exists($cache, 'regenerate')) {
                        $cache->regenerate();
                    }
                }
                if (method_exists($manager, 'clear_location_cache')) {
                    $manager->clear_location_cache();
                }
            }
            $plugin = $this->elementor();
            if ($plugin !== null && isset($plugin->files_manager) && is_object($plugin->files_manager)
                && method_exists($plugin->files_manager, 'clear_cache')) {
                $plugin->files_manager->clear_cache();
            }
        } catch (\Throwable) {
            return false;
        }
        return true;
    }

    public function lockDocument(int $documentId): bool {
        return $this->writes->lockDocument($documentId);
    }

    public function unlockDocument(int $documentId): void {
        $this->writes->unlockDocument($documentId);
    }

    private function elementor(): ?object {
        if ($this->plugin !== null) {
            return $this->plugin;
        }
        if (!class_exists('Elementor\\Plugin')) {
            return null;
        }
        $plugin = \Elementor\Plugin::$instance ?? null;
        return is_object($plugin) ? $plugin : null;
    }

    private function themeDocument(int $postId): ?object {
        $writable = $this->getDocument($postId);
        $plugin = $this->elementor();
        if ($writable === null || $writable->postType() !== 'elementor_library'
            || $plugin === null || !isset($plugin->documents) || !is_object($plugin->documents)
            || !method_exists($plugin->documents, 'get')) {
            return null;
        }
        $document = $plugin->documents->get($postId, false);
        return is_object($document)
            && method_exists($document, 'get_location')
            && method_exists($document, 'get_main_meta')
                ? $document
                : null;
    }

    private function conditionsManager(): ?object {
        if (!class_exists('Modulix\\Modules\\ThemeBuilder\\Module')) {
            return null;
        }
        $module = \Modulix\Modules\ThemeBuilder\Module::instance();
        if (!is_object($module) || !method_exists($module, 'get_conditions_manager')) {
            return null;
        }
        $manager = $module->get_conditions_manager();
        return is_object($manager) ? $manager : null;
    }
}
