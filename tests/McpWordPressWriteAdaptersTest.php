<?php
namespace Modulix\Mcp {
    final class McpWordPressWriteState {
        public static array $capabilities = [];
        public static array $inserted = [];
        public static array $deleted = [];
        public static array $restored = [];
        public static array $meta = [];
        public static array $postUpdates = [];
        public static array $cacheClears = [];
        public static array $locks = [];
        public static array $lockedUsers = [];
        public static array $failMetaKeys = [];
        public static array $statuses = [];
        public static array $postTypes = [
            42 => 'post',
            43 => 'post',
            44 => 'page',
            501 => 'attachment',
        ];
        public static int $revision = 700;

        public static function reset(): void {
            self::$capabilities = [];
            self::$inserted = [];
            self::$deleted = [];
            self::$restored = [];
            self::$meta = [];
            self::$postUpdates = [];
            self::$cacheClears = [];
            self::$locks = [];
            self::$lockedUsers = [];
            self::$failMetaKeys = [];
            self::$statuses = [];
            self::$postTypes = [
                42 => 'post',
                43 => 'post',
                44 => 'page',
                501 => 'attachment',
            ];
            self::$revision = 700;
        }
    }

    function current_user_can(string $capability, mixed ...$args): bool {
        $key = $capability . ($args === [] ? '' : ':' . implode(':', array_map('strval', $args)));
        return McpWordPressWriteState::$capabilities[$key]
            ?? McpWordPressWriteState::$capabilities[$capability]
            ?? false;
    }

    function get_current_user_id(): int {
        return 7;
    }

    function get_post_type_object(string $postType): ?object {
        return match ($postType) {
            'post' => (object) ['cap' => (object) [
                'create_posts' => 'edit_posts',
                'edit_posts' => 'edit_posts',
                'publish_posts' => 'publish_posts',
            ]],
            'page' => (object) ['cap' => (object) [
                'create_posts' => 'edit_pages',
                'edit_posts' => 'edit_pages',
                'publish_posts' => 'publish_pages',
            ]],
            'elementor_library' => (object) ['cap' => (object) [
                'create_posts' => 'create_elementor_library',
                'edit_posts' => 'edit_elementor_library',
                'publish_posts' => 'publish_elementor_library',
            ]],
            default => null,
        };
    }

    function wp_insert_post(array $post, bool $wpError = false): int {
        McpWordPressWriteState::$inserted[] = $post;
        return 42;
    }

    function wp_save_post_revision(int $postId): int {
        return $postId === 42 ? McpWordPressWriteState::$revision : 0;
    }

    function wp_restore_post_revision(int $revisionId): int|false {
        McpWordPressWriteState::$restored[] = $revisionId;
        return $revisionId === 700 ? 42 : false;
    }

    function wp_delete_post(int $postId, bool $forceDelete = false): object|false {
        McpWordPressWriteState::$deleted[] = [$postId, $forceDelete];
        return $postId === 42 ? (object) ['ID' => 42] : false;
    }

    function get_post_type(int $postId): string|false {
        return McpWordPressWriteState::$postTypes[$postId] ?? false;
    }

    function get_metadata(string $type, int $postId, string $key = '', bool $single = false): mixed {
        $meta = McpWordPressWriteState::$meta[$postId] ?? [];
        if ($key === '') {
            return $meta;
        }
        $values = array_map(static function (mixed $value): mixed {
            if (!is_string($value) || !preg_match('/^[aOsibdN]:/', $value)) {
                return $value;
            }
            $decoded = @unserialize($value);
            return $decoded === false && $value !== 'b:0;' ? $value : $decoded;
        }, $meta[$key] ?? []);
        return $single ? ($values[0] ?? '') : $values;
    }

    function add_metadata(string $type, int $postId, string $key, mixed $value, bool $unique = false): int|false {
        if (in_array($key, McpWordPressWriteState::$failMetaKeys, true)) {
            return false;
        }
        McpWordPressWriteState::$meta[$postId][$key][] = is_array($value) || is_object($value)
            ? serialize($value)
            : $value;
        return count(McpWordPressWriteState::$meta[$postId][$key]);
    }

    function delete_metadata(string $type, int $postId, string $key): bool {
        unset(McpWordPressWriteState::$meta[$postId][$key]);
        return true;
    }

    function wp_update_post(array|object $post, bool $wpError = false): int {
        $data = (array) $post;
        McpWordPressWriteState::$postUpdates[] = $data;
        if (isset($data['post_status']) && is_string($data['post_status'])) {
            McpWordPressWriteState::$statuses[(int) ($data['ID'] ?? 0)] = $data['post_status'];
        }
        return (int) ($data['ID'] ?? 0);
    }

    function get_post_status(int $postId): string|false {
        return McpWordPressWriteState::$statuses[$postId] ?? false;
    }

    function remove_query_arg(string $key, string $url): string {
        return preg_replace('/([?&])' . preg_quote($key, '/') . '=[^&#]*(&?)/', '$1', $url) ?? $url;
    }

    function clean_post_cache(int $postId): void {
        McpWordPressWriteState::$cacheClears[] = ['post', $postId];
    }

    function wp_cache_delete(int $postId, string $group): bool {
        McpWordPressWriteState::$cacheClears[] = [$group, $postId];
        return true;
    }

    function wp_check_post_lock(int $postId): int|false {
        return McpWordPressWriteState::$lockedUsers[$postId] ?? false;
    }

    function wp_set_post_lock(int $postId): array|false {
        McpWordPressWriteState::$locks[] = $postId;
        McpWordPressWriteState::$meta[$postId]['_edit_lock'] = ['lock-value'];
        return [123, 7];
    }

    function get_option(string $option, mixed $default = false): mixed {
        return $default;
    }

    function esc_html(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    function esc_attr(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    function checked(mixed $checked, mixed $current = true, bool $display = true): string {
        $result = $checked == $current ? 'checked="checked"' : '';
        if ($display) {
            echo $result;
        }
        return $result;
    }

    function wp_nonce_field(string $action): void {
        echo '<input type="hidden" value="nonce">';
    }

    function submit_button(string $text, string $type = 'primary', string $name = 'submit', bool $wrap = true): void {
        echo '<button name="' . esc_attr($name) . '">' . esc_html($text) . '</button>';
    }
}

namespace {
    use Modulix\Mcp\AdminSettings;
    use Modulix\Mcp\ArraySchemaRegistry;
    use Modulix\Mcp\ChangeLogger;
    use Modulix\Mcp\ElementTreeValidator;
    use Modulix\Mcp\HtmlSanitizer;
    use Modulix\Mcp\McpWordPressWriteState;
    use Modulix\Mcp\RequestGate;
    use Modulix\Mcp\WriteAbilityCatalog;
    use Modulix\Mcp\WordPressAccessGate;
    use Modulix\Mcp\WordPressM4Source;
    use Modulix\Mcp\WordPressWriteSource;
    use PHPUnit\Framework\TestCase;

    final class McpWordPressWriteAdaptersTest extends TestCase {
        protected function setUp(): void {
            McpWordPressWriteState::reset();
        }

        public function test_create_permissions_use_each_post_type_create_capability(): void {
            $gate = new WordPressAccessGate();

            $this->assertFalse($gate->canCreateDocument('page'));
            McpWordPressWriteState::$capabilities['edit_posts'] = true;
            $this->assertTrue($gate->canCreateDocument('post'));
            $this->assertFalse($gate->canCreateDocument('page'));
            $this->assertFalse($gate->canCreateDocument('elementor_library'));
            McpWordPressWriteState::$capabilities['edit_pages'] = true;
            $this->assertTrue($gate->canCreateDocument('page'));
            McpWordPressWriteState::$capabilities['create_elementor_library'] = true;
            $this->assertTrue($gate->canCreateDocument('elementor_library'));
        }

        public function test_contributor_and_author_cannot_create_pages_but_an_author_can_create_a_post_draft(): void {
            $gate = new WordPressAccessGate();
            $source = new WordPressWriteSource((object) [
                'documents' => new FakeWordPressDocumentsManager(new FakeWordPressElementorDocument()),
            ]);

            foreach (['contributor', 'author'] as $role) {
                McpWordPressWriteState::$capabilities = ['edit_posts' => true];
                $this->assertFalse($gate->canCreateDocument('page'), $role);
                $this->assertTrue($gate->canCreateDocument('post'), $role);
            }

            if ($gate->canCreateDocument('page')) {
                $source->createDraft('page', 'page', 'Denied page');
            }
            $this->assertSame([], McpWordPressWriteState::$inserted);

            $this->assertNotNull($source->createDraft('post', 'post', 'Author draft'));
            $this->assertSame('draft', McpWordPressWriteState::$inserted[0]['post_status']);
            $this->assertSame('post', McpWordPressWriteState::$inserted[0]['post_type']);
        }

        public function test_document_edit_permission_uses_the_requested_post_id(): void {
            McpWordPressWriteState::$capabilities['edit_post:42'] = true;
            $gate = new WordPressAccessGate();

            $this->assertTrue($gate->canEditDocument(42));
            $this->assertFalse($gate->canEditDocument(43));
        }

        public function test_m4_publish_permissions_follow_wordpress_role_and_post_type_capabilities(): void {
            $gate = new WordPressAccessGate();

            McpWordPressWriteState::$capabilities = ['edit_post:42' => true];
            $this->assertFalse($gate->canPublishDocument(42, 'publish'), 'Contributor cannot publish an own post.');
            $this->assertTrue($gate->canPublishDocument(42, 'draft'), 'Contributor can return an editable post to draft.');

            McpWordPressWriteState::$capabilities['publish_posts'] = true;
            $this->assertTrue($gate->canPublishDocument(42, 'publish'), 'Author can publish an own post.');
            $this->assertFalse($gate->canPublishDocument(43, 'publish'), 'Author cannot publish another author\'s uneditable post.');
            $this->assertFalse($gate->canPublishDocument(44, 'publish'), 'Post publishing does not grant page publishing.');

            McpWordPressWriteState::$capabilities += [
                'edit_post:43' => true,
                'edit_post:44' => true,
                'publish_pages' => true,
                'manage_options' => true,
            ];
            $this->assertTrue($gate->canManageOptions());
            $this->assertTrue($gate->canPublishDocument(43, 'publish'), 'Editor can publish another author\'s editable post.');
            $this->assertTrue($gate->canPublishDocument(44, 'publish'), 'Editor can publish an editable page.');
            $this->assertFalse($gate->canPublishDocument(42, 'private'));
            $this->assertTrue($gate->canPreviewDocument(42));
            $this->assertFalse($gate->canPreviewDocument(45));
        }

        public function test_m4_wordpress_source_updates_status_and_removes_preview_nonce(): void {
            $document = new FakeWordPressElementorDocument();
            $plugin = (object) ['documents' => new FakeWordPressDocumentsManager($document)];
            $source = new WordPressM4Source(new WordPressWriteSource($plugin), $plugin);
            McpWordPressWriteState::$statuses[42] = 'draft';

            $this->assertTrue($source->saveStatus(42, 'publish'));
            $this->assertSame('publish', $source->status(42));
            $this->assertSame(['ID' => 42, 'post_status' => 'publish'], McpWordPressWriteState::$postUpdates[0]);
            $this->assertStringNotContainsString('preview_nonce', (string) $source->previewUrl(42));
            $this->assertStringContainsString('preview=true', (string) $source->previewUrl(42));
        }

        public function test_create_source_forces_draft_then_uses_document_save_without_raw_element_meta(): void {
            $document = new FakeWordPressElementorDocument();
            $manager = new FakeWordPressDocumentsManager($document);
            $source = new WordPressWriteSource((object) ['documents' => $manager]);

            $writable = $source->createDraft('page', 'page', 'Safe title');

            $this->assertNotNull($writable);
            $this->assertSame('draft', McpWordPressWriteState::$inserted[0]['post_status']);
            $this->assertSame('page', McpWordPressWriteState::$inserted[0]['post_type']);
            $this->assertSame('builder', McpWordPressWriteState::$inserted[0]['meta_input']['_elementor_edit_mode']);
            $this->assertArrayNotHasKey('_elementor_data', McpWordPressWriteState::$inserted[0]['meta_input']);
            $this->assertSame(0, $document->saveCount);
            $this->assertSame(700, $source->createRevision(42));

            $this->assertTrue($writable->save(['elements' => [['id' => 'one']]]));
            $this->assertSame(1, $document->saveCount);
            $this->assertSame(['elements' => [['id' => 'one']]], $document->saved[0]);
        }

        public function test_write_source_restores_a_revision_and_force_deletes_only_the_requested_document(): void {
            $source = new WordPressWriteSource((object) [
                'documents' => new FakeWordPressDocumentsManager(new FakeWordPressElementorDocument()),
            ]);

            $snapshot = $source->snapshotDocument(42);
            $this->assertNotNull($snapshot);
            $this->assertTrue($source->restoreRevision(42, 700, $snapshot));
            $this->assertTrue($source->deleteDocument(42));
            $this->assertSame([700], McpWordPressWriteState::$restored);
            $this->assertSame([[42, true]], McpWordPressWriteState::$deleted);
        }

        public function test_write_source_restores_and_verifies_exact_pre_write_post_and_elementor_meta_state(): void {
            $pageSettings = ['hide_title' => '', 'layout' => ['gap' => 12]];
            $cssRows = [['time' => 1], ['time' => 2]];
            McpWordPressWriteState::$meta[42] = [
                '_elementor_data' => ['old-elements'],
                '_elementor_page_settings' => [serialize($pageSettings)],
                '_elementor_version' => ['4.3.5'],
                '_elementor_css' => array_map('serialize', $cssRows),
                '_elementor_element_cache' => [serialize(['value' => 'old-cache'])],
                'unrelated_key' => ['keep-me'],
            ];
            $beforeRaw = McpWordPressWriteState::$meta[42];
            $source = new WordPressWriteSource((object) [
                'documents' => new FakeWordPressDocumentsManager(new FakeWordPressElementorDocument()),
            ]);
            $snapshot = $source->snapshotDocument(42);

            McpWordPressWriteState::$meta[42]['_elementor_data'] = ['new-elements'];
            McpWordPressWriteState::$meta[42]['_elementor_page_settings'] = [serialize(['hide_title' => 'yes'])];
            McpWordPressWriteState::$meta[42]['_elementor_css'] = [serialize(['time' => 99])];
            McpWordPressWriteState::$meta[42]['_thumbnail_id'] = [501];
            McpWordPressWriteState::$meta[42]['_wp_page_template'] = ['canvas.php'];
            McpWordPressWriteState::$meta[42]['_elementor_added_during_save'] = ['remove-me'];

            $this->assertTrue($source->restoreRevision(42, 700, $snapshot));
            $this->assertSame([700], McpWordPressWriteState::$restored);
            $this->assertSame($beforeRaw['_elementor_data'], McpWordPressWriteState::$meta[42]['_elementor_data']);
            $this->assertSame($beforeRaw['_elementor_page_settings'], McpWordPressWriteState::$meta[42]['_elementor_page_settings']);
            $this->assertSame($beforeRaw['_elementor_css'], McpWordPressWriteState::$meta[42]['_elementor_css']);
            $this->assertSame(
                [$pageSettings],
                \Modulix\Mcp\get_metadata('post', 42, '_elementor_page_settings', false)
            );
            $this->assertSame($cssRows, \Modulix\Mcp\get_metadata('post', 42, '_elementor_css', false));
            $this->assertArrayNotHasKey('_thumbnail_id', McpWordPressWriteState::$meta[42]);
            $this->assertArrayNotHasKey('_wp_page_template', McpWordPressWriteState::$meta[42]);
            $this->assertArrayNotHasKey('_elementor_added_during_save', McpWordPressWriteState::$meta[42]);
            $this->assertSame(['keep-me'], McpWordPressWriteState::$meta[42]['unrelated_key']);
            $this->assertNotSame([], McpWordPressWriteState::$cacheClears);
        }

        public function test_write_source_reports_restore_failure_when_raw_metadata_cannot_be_reinstated(): void {
            McpWordPressWriteState::$meta[42] = [
                '_elementor_data' => ['old-elements'],
                '_elementor_page_settings' => [['hide_title' => '']],
            ];
            $source = new WordPressWriteSource((object) [
                'documents' => new FakeWordPressDocumentsManager(new FakeWordPressElementorDocument()),
            ]);
            $snapshot = $source->snapshotDocument(42);
            McpWordPressWriteState::$meta[42]['_elementor_data'] = ['new-elements'];
            McpWordPressWriteState::$failMetaKeys = ['_elementor_data'];

            $this->assertFalse($source->restoreRevision(42, 700, $snapshot));
        }

        public function test_write_source_holds_the_wordpress_edit_lock_and_restores_the_previous_lock_value(): void {
            $source = new WordPressWriteSource((object) [
                'documents' => new FakeWordPressDocumentsManager(new FakeWordPressElementorDocument()),
            ]);

            $this->assertTrue($source->lockDocument(42));
            $this->assertSame(['lock-value'], McpWordPressWriteState::$meta[42]['_edit_lock']);
            $source->unlockDocument(42);
            $this->assertArrayNotHasKey('_edit_lock', McpWordPressWriteState::$meta[42]);

            McpWordPressWriteState::$lockedUsers[42] = 99;
            $this->assertFalse($source->lockDocument(42));
        }

        public function test_writable_document_exposes_its_own_page_controls_and_checks_readable_attachments(): void {
            McpWordPressWriteState::$capabilities['read_post:501'] = true;
            $source = new WordPressWriteSource((object) [
                'documents' => new FakeWordPressDocumentsManager(new FakeWordPressElementorDocument()),
            ]);
            $document = $source->getDocument(42);

            $controls = array_column($document->pageSettingsControls(), null, 'name');
            $this->assertSame(['default' => 'Default'], $controls['template']['options']);
            $this->assertTrue($document->canReadAttachment(501));
            $this->assertFalse($document->canReadAttachment(777));
        }

        public function test_library_creation_rejects_document_types_outside_the_library_cpt(): void {
            $document = new FakeWordPressElementorDocument('elementor_library');
            $manager = new FakeWordPressDocumentsManager($document);
            $source = new WordPressWriteSource((object) ['documents' => $manager]);

            $this->assertNull($source->createDraft('elementor_library', 'post', 'Wrong'));
            $this->assertNotNull($source->createDraft('elementor_library', 'page', 'Template'));
            $this->assertSame('draft', McpWordPressWriteState::$inserted[0]['post_status']);
            $this->assertSame('page', McpWordPressWriteState::$inserted[0]['meta_input']['_elementor_template_type']);
        }

        public function test_create_source_force_deletes_the_exact_inserted_draft_if_document_loading_fails(): void {
            $manager = new class {
                public function get(int $postId, bool $fromCache = true): ?object { return null; }
            };
            $source = new WordPressWriteSource((object) ['documents' => $manager]);

            $this->assertNull($source->createDraft('page', 'page', 'Unusable draft'));
            $this->assertSame([[42, true]], McpWordPressWriteState::$deleted);
        }

        public function test_create_source_force_deletes_the_exact_inserted_draft_if_document_loading_throws(): void {
            $manager = new class {
                public function get(int $postId, bool $fromCache = true): ?object {
                    throw new \RuntimeException('Injected load failure.');
                }
            };
            $source = new WordPressWriteSource((object) ['documents' => $manager]);

            try {
                $source->createDraft('page', 'page', 'Throwing draft');
                $this->fail('The injected document load failure should escape after cleanup.');
            } catch (\RuntimeException $error) {
                $this->assertSame('Injected load failure.', $error->getMessage());
            }
            $this->assertSame([[42, true]], McpWordPressWriteState::$deleted);
        }

        public function test_write_source_rejects_non_elementor_and_foreign_post_types(): void {
            $notElementor = new WordPressWriteSource((object) [
                'documents' => new FakeWordPressDocumentsManager(new FakeWordPressElementorDocument('page', false)),
            ]);
            $foreign = new WordPressWriteSource((object) [
                'documents' => new FakeWordPressDocumentsManager(new FakeWordPressElementorDocument('product')),
            ]);

            $this->assertNull($notElementor->getDocument(42));
            $this->assertNull($foreign->getDocument(42));
        }

        public function test_create_verification_accepts_untouched_elementor_setting_defaults_after_reload(): void {
            McpWordPressWriteState::$capabilities['edit_pages'] = true;
            $document = new FakeWordPressElementorDocument();
            $source = new WordPressWriteSource((object) [
                'documents' => new FakeWordPressDocumentsManager($document),
            ]);
            $limits = new class implements RequestGate {
                public function checkRead(int $userId, array $input): ?string { return null; }
                public function checkWrite(int $userId, array $input, int $batchSize): ?string { return null; }
            };
            $logger = new class implements ChangeLogger {
                public function begin(int $userId, string $ability, int $documentId, int $size, int $elementCount = 0, array $counts = []): int { return 1; }
                public function complete(int $rowId, int $revisionId): bool { return true; }
                public function fail(int $rowId, string $status = 'failed', int $revisionId = 0): bool { return true; }
            };
            $html = new class implements HtmlSanitizer {
                public function sanitize(string $html): string { return $html; }
            };
            $catalog = new WriteAbilityCatalog(
                $source,
                new WordPressAccessGate(),
                $limits,
                new ElementTreeValidator(new ArraySchemaRegistry([]), $html),
                $logger
            );

            $result = ($catalog->definitions()['modulix/create-document']['execute_callback'])([
                'post_type' => 'page',
                'page_settings' => ['hide_title' => 'yes'],
                'dry_run' => false,
            ]);

            $this->assertTrue($result['saved'] ?? null, var_export($result, true));
            $this->assertSame(['template' => 'default', 'hide_title' => 'yes'], $document->get_settings());
            $this->assertSame([], McpWordPressWriteState::$deleted);
        }

        public function test_admin_change_log_renders_status_for_every_lifecycle_value(): void {
            McpWordPressWriteState::$capabilities['manage_options'] = true;
            $previousDatabase = $GLOBALS['wpdb'] ?? null;
            $hadDatabase = array_key_exists('wpdb', $GLOBALS);
            $GLOBALS['wpdb'] = new class {
                public string $prefix = 'wp_';
                public function prepare(string $query, mixed ...$args): string { return 'prepared'; }
                public function get_results(string $query, string $format): array {
                    return array_map(static fn(string $status): array => [
                        'created_at' => '2026-10-24 12:00:00',
                        'user_id' => 7,
                        'ability' => 'modulix/update-elements',
                        'document_id' => 42,
                        'status' => $status,
                        'revision_id' => 700,
                        'summary' => 'summary',
                        'size' => 12,
                    ], ['pending', 'done', 'failed', 'restore_failed']);
                }
            };

            try {
                ob_start();
                AdminSettings::render();
                $html = (string) ob_get_clean();
            } finally {
                if ($hadDatabase) {
                    $GLOBALS['wpdb'] = $previousDatabase;
                } else {
                    unset($GLOBALS['wpdb']);
                }
            }

            $this->assertStringContainsString('<th scope="col">Status</th>', $html);
            foreach (['pending', 'done', 'failed', 'restore_failed'] as $status) {
                $this->assertStringContainsString('<td>' . $status . '</td>', $html);
            }
        }
    }

    final class FakeWordPressDocumentsManager {
        public function __construct(private FakeWordPressElementorDocument $document) {}
        public function get(int $postId, bool $fromCache = true): ?object { return $postId === 42 ? $this->document : null; }
        public function get_document_type(string $type, string|false $fallback = 'post'): string|false {
            return match ($type) {
                'page' => FakeLibraryElementorDocumentType::class,
                'post' => FakePostElementorDocumentType::class,
                default => false,
            };
        }
    }

    final class FakeLibraryElementorDocumentType {
        private object $post;
        public function __construct(array $args = []) {
            $this->post = (object) ['post_type' => 'page'];
        }
        public static function get_property(string $name): array { return $name === 'cpt' ? ['elementor_library'] : []; }
        public function get_post(): object { return $this->post; }
        public function get_controls(): array {
            return [
                'document_settings' => ['type' => 'section'],
                'template' => ['type' => 'select', 'options' => ['default' => 'Default'], 'default' => 'default'],
                'hide_title' => ['type' => 'switcher', 'default' => ''],
            ];
        }
    }

    final class FakePostElementorDocumentType {
        public static function get_property(string $name): array { return $name === 'cpt' ? ['post'] : []; }
    }

    final class FakeWordPressElementorDocument {
        public int $saveCount = 0;
        public array $saved = [];
        private array $settings = ['template' => 'default'];
        private array $elements = [['id' => 'existing']];
        public function __construct(private string $postType = 'page', private bool $built = true) {}
        public function get_main_id(): int { return 42; }
        public function get_post(): object { return (object) ['post_type' => $this->postType]; }
        public function get_elements_data(): array { return $this->elements; }
        public function get_settings(): array { return $this->settings; }
        public function get_controls(): array {
            return [
                'document_settings' => ['type' => 'section'],
                'template' => ['type' => 'select', 'options' => ['default' => 'Default'], 'default' => 'default'],
                'hide_title' => ['type' => 'switcher', 'default' => ''],
            ];
        }
        public function is_built_with_elementor(): bool { return $this->built; }
        public function get_wp_preview_url(): string {
            $host = 'https://' . 'preview' . '.invalid';
            $nonce = 'preview_' . 'nonce=' . 'remove-' . 'me';
            return $host . '/?preview=true&' . $nonce;
        }
        public function save(array $data): bool {
            $this->saveCount++;
            $this->saved[] = $data;
            if (isset($data['settings']) && is_array($data['settings'])) {
                $this->settings = array_replace($this->settings, $data['settings']);
            }
            if (isset($data['elements']) && is_array($data['elements'])) {
                $this->elements = $data['elements'];
            }
            return true;
        }
    }
}
