<?php
use Modulix\Mcp\ArraySchemaRegistry;
use Modulix\Mcp\ChangeLogger;
use Modulix\Mcp\ElementTreeValidator;
use Modulix\Mcp\HtmlSanitizer;
use Modulix\Mcp\M4AbilityCatalog;
use Modulix\Mcp\M4AccessGate;
use Modulix\Mcp\M4Source;
use Modulix\Mcp\RequestGate;
use Modulix\Mcp\WritableDocument;
use PHPUnit\Framework\TestCase;

final class McpM4AbilitiesTest extends TestCase {
    public function test_definitions_use_the_required_capabilities_and_annotations(): void {
        $fixture = $this->fixture();
        $definitions = $fixture->catalog->definitions();

        $this->assertSame([
            'modulix/set-conditions',
            'modulix/update-kit',
            'modulix/publish',
            'modulix/preview-url',
            'modulix/readiness',
        ], array_keys($definitions));
        $this->assertTrue(($definitions['modulix/set-conditions']['permission_callback'])(['post_id' => 42]));
        $this->assertTrue(($definitions['modulix/update-kit']['permission_callback'])([]));
        $this->assertTrue(($definitions['modulix/publish']['permission_callback'])(['post_id' => 42, 'status' => 'publish']));
        $this->assertTrue(($definitions['modulix/preview-url']['permission_callback'])(['post_id' => 42]));
        $this->assertTrue(($definitions['modulix/readiness']['permission_callback'])([]));
        $this->assertFalse($definitions['modulix/set-conditions']['meta']['annotations']['readonly']);
        $this->assertTrue($definitions['modulix/preview-url']['meta']['annotations']['readonly']);
        $this->assertTrue($definitions['modulix/readiness']['meta']['annotations']['readonly']);
    }

    public function test_conditions_dry_run_validates_and_does_not_write(): void {
        $fixture = $this->fixture();
        $execute = $fixture->catalog->definitions()['modulix/set-conditions']['execute_callback'];

        $result = $execute([
            'post_id' => 42,
            'conditions' => [[
                'type' => 'include',
                'name' => 'singular',
                'sub_name' => 'page',
                'sub_id' => '17',
            ]],
        ]);

        $this->assertTrue($result['dry_run']);
        $this->assertFalse($result['saved']);
        $this->assertSame(['include/singular/page/17'], $result['conditions']);
        $this->assertSame(['include/general'], $result['diff']['removed']);
        $this->assertSame(['include/singular/page/17'], $result['diff']['added']);
        $this->assertSame(0, $fixture->source->conditionSaveCount);
        $this->assertSame(0, $fixture->source->revisionCount);
        $this->assertSame([], $fixture->logger->rows);
    }

    public function test_conditions_reject_unknown_and_unsafe_values(): void {
        $fixture = $this->fixture();
        $execute = $fixture->catalog->definitions()['modulix/set-conditions']['execute_callback'];

        $result = $execute([
            'post_id' => 42,
            'conditions' => [[
                'type' => 'include',
                'name' => 'general',
                'sub_name' => 'missing',
                'sub_id' => '../17',
            ]],
        ]);

        $this->assertSame(['unknown_condition', 'invalid_condition_value'], array_column($result['validation_errors'], 'code'));
        $this->assertSame(0, $fixture->source->conditionSaveCount);
    }

    public function test_conditions_reject_broadening_invalid_targets_and_uneditable_posts(): void {
        $fixture = $this->fixture(editable: [42]);
        $execute = $fixture->catalog->definitions()['modulix/set-conditions']['execute_callback'];

        $result = $execute([
            'post_id' => 42,
            'conditions' => [
                ['type' => 'include', 'name' => 'general', 'sub_name' => 'singular', 'sub_id' => 17],
                ['type' => 'include', 'name' => 'singular', 'sub_name' => 'archive'],
                ['type' => 'include', 'name' => 'singular', 'sub_id' => 17],
                ['type' => 'include', 'name' => 'singular', 'sub_name' => 'page', 'sub_id' => 999],
                ['type' => 'include', 'name' => 'singular', 'sub_name' => 'post', 'sub_id' => 17],
                ['type' => 'include', 'name' => 'singular', 'sub_name' => 'page', 'sub_id' => 17],
            ],
        ]);

        $this->assertSame([
            'invalid_condition_chain',
            'invalid_condition_chain',
            'invalid_condition_chain',
            'condition_target_not_found',
            'condition_target_mismatch',
            'condition_target_forbidden',
        ], array_column($result['validation_errors'], 'code'));
        $this->assertSame(0, $fixture->source->conditionSaveCount);
    }

    public function test_conditions_reject_target_on_name_and_non_direct_sub_conditions(): void {
        $fixture = $this->fixture();
        $execute = $fixture->catalog->definitions()['modulix/set-conditions']['execute_callback'];

        $result = $execute([
            'post_id' => 42,
            'conditions' => [
                ['type' => 'include', 'name' => 'page', 'sub_id' => 17],
                ['type' => 'include', 'name' => 'singular', 'sub_name' => 'post_child'],
            ],
            'dry_run' => false,
        ]);

        $this->assertSame([
            'invalid_condition_chain',
            'invalid_condition_chain',
        ], array_column($result['validation_errors'], 'code'));
        $this->assertSame(['include/general'], $fixture->source->conditions);
        $this->assertSame(0, $fixture->source->conditionSaveCount);
    }

    public function test_conditions_require_edit_access_to_the_template_at_dispatch_and_execution(): void {
        $fixture = $this->fixture(editable: [17]);
        $definition = $fixture->catalog->definitions()['modulix/set-conditions'];
        $input = [
            'post_id' => 42,
            'conditions' => [['type' => 'include', 'name' => 'general']],
            'dry_run' => false,
        ];

        $this->assertFalse(($definition['permission_callback'])($input));
        $result = ($definition['execute_callback'])($input);

        $this->assertSame('forbidden', $result['error']['code']);
        $this->assertSame(0, $fixture->source->conditionSaveCount);
    }

    public function test_real_conditions_write_creates_revision_and_finishes_log(): void {
        $fixture = $this->fixture();
        $execute = $fixture->catalog->definitions()['modulix/set-conditions']['execute_callback'];

        $result = $execute([
            'post_id' => 42,
            'conditions' => [['type' => 'exclude', 'name' => 'general']],
            'dry_run' => false,
        ]);

        $this->assertTrue($result['saved']);
        $this->assertSame(701, $result['revision_id']);
        $this->assertSame(['exclude/general'], $fixture->source->conditions);
        $this->assertSame([42], $fixture->source->locked);
        $this->assertSame([42], $fixture->source->unlocked);
        $this->assertSame('done', $fixture->logger->rows[0]['status']);
        $this->assertSame('modulix/set-conditions', $fixture->logger->rows[0]['ability']);
    }

    public function test_log_completion_failure_restores_conditions_and_finalizes_failure(): void {
        $fixture = $this->fixture(logComplete: false);
        $execute = $fixture->catalog->definitions()['modulix/set-conditions']['execute_callback'];

        $result = $execute([
            'post_id' => 42,
            'conditions' => [['type' => 'exclude', 'name' => 'general']],
            'dry_run' => false,
        ]);

        $this->assertSame('change_log_failed', $result['error']['code']);
        $this->assertSame(['include/general'], $fixture->source->conditions);
        $this->assertSame([['document_id' => 42, 'revision_id' => 701]], $fixture->source->restores);
        $this->assertSame('failed', $fixture->logger->rows[0]['status']);
    }

    public function test_update_kit_rejects_unknown_keys_and_css_injection(): void {
        $fixture = $this->fixture();
        $execute = $fixture->catalog->definitions()['modulix/update-kit']['execute_callback'];

        $result = $execute([
            'settings' => [
                'custom_css' => 'body{}',
                'system_colors' => [[
                    '_id' => 'primary',
                    'title' => 'Primary',
                    'color' => '</style><script>x()</script>',
                ]],
            ],
        ]);

        $this->assertSame(['unknown_kit_setting', 'unsafe_kit_value'], array_column($result['validation_errors'], 'code'));
        $this->assertSame(0, $fixture->kit->saveCount);
    }

    public function test_update_kit_rejects_adversarial_nesting_without_recursive_failure(): void {
        $fixture = $this->fixture();
        $execute = $fixture->catalog->definitions()['modulix/update-kit']['execute_callback'];
        $nested = 'leaf';
        for ($depth = 0; $depth < 45; $depth++) {
            $nested = ['nested' => $nested];
        }

        $result = $execute([
            'settings' => [
                'system_colors' => [[
                    '_id' => 'primary',
                    'title' => 'Primary',
                    'color' => '#123456',
                    'nested' => $nested,
                ]],
            ],
        ]);

        $this->assertContains('kit_too_deep', array_column($result['validation_errors'], 'code'));
        $this->assertSame(0, $fixture->kit->saveCount);
    }

    public function test_update_kit_dry_run_and_real_write_use_the_active_kit_revision(): void {
        $fixture = $this->fixture();
        $execute = $fixture->catalog->definitions()['modulix/update-kit']['execute_callback'];
        $input = [
            'settings' => [
                'system_colors' => [[
                    '_id' => 'primary',
                    'title' => 'Primary',
                    'color' => '#123456',
                ]],
            ],
        ];

        $dry = $execute($input);
        $saved = $execute($input + ['dry_run' => false]);

        $this->assertTrue($dry['dry_run']);
        $this->assertFalse($dry['saved']);
        $this->assertTrue($saved['saved']);
        $this->assertSame(99, $saved['document_id']);
        $this->assertSame(['system_colors'], $saved['diff']['changed_keys']);
        $this->assertSame(1, $fixture->kit->saveCount);
        $this->assertSame('done', $fixture->logger->rows[0]['status']);
    }

    public function test_update_kit_accepts_a_normal_global_font_family(): void {
        $fixture = $this->fixture();
        $execute = $fixture->catalog->definitions()['modulix/update-kit']['execute_callback'];

        $result = $execute([
            'settings' => [
                'system_typography' => [[
                    '_id' => 'primary',
                    'title' => 'Primary',
                    'typography_typography' => 'custom',
                    'typography_font_family' => 'Open Sans',
                ]],
            ],
        ]);

        $this->assertSame([], $result['validation_errors']);
        $this->assertSame(['system_typography'], $result['diff']['changed_keys']);
    }

    public function test_publish_defaults_to_dry_run_and_rejects_status_outside_contract(): void {
        $fixture = $this->fixture();
        $execute = $fixture->catalog->definitions()['modulix/publish']['execute_callback'];

        $invalid = $execute(['post_id' => 42, 'status' => 'private']);
        $dry = $execute(['post_id' => 42, 'status' => 'publish']);

        $this->assertSame('invalid_status', $invalid['validation_errors'][0]['code']);
        $this->assertTrue($dry['dry_run']);
        $this->assertSame('publish', $dry['status']);
        $this->assertSame('draft', $fixture->source->status);
        $this->assertSame(0, $fixture->source->statusSaveCount);
    }

    public function test_publish_requires_edit_and_post_type_publish_capabilities_even_if_dispatch_is_bypassed(): void {
        $fixture = $this->fixture(publish: false);
        $definition = $fixture->catalog->definitions()['modulix/publish'];

        $this->assertFalse(($definition['permission_callback'])(['post_id' => 42, 'status' => 'publish']));
        $result = ($definition['execute_callback'])(['post_id' => 42, 'status' => 'publish', 'dry_run' => false]);

        $this->assertSame('forbidden', $result['error']['code']);
        $this->assertSame(0, $fixture->source->statusSaveCount);
    }

    public function test_preview_url_drops_nonce_and_readiness_uses_read_limit(): void {
        $fixture = $this->fixture();
        $definitions = $fixture->catalog->definitions();

        $preview = ($definitions['modulix/preview-url']['execute_callback'])(['post_id' => 42]);
        $readiness = ($definitions['modulix/readiness']['execute_callback'])([]);

        $this->assertStringNotContainsString('preview_nonce', $preview['url']);
        $this->assertStringContainsString('preview=true', $preview['url']);
        $this->assertFalse($readiness['ready']);
        $this->assertSame(2, $fixture->limits->readChecks);
        $this->assertSame(0, $fixture->limits->writeChecks);
    }

    public function test_all_m4_abilities_fail_closed_on_limits(): void {
        $fixture = $this->fixture(limitError: 'request_too_large');
        $definitions = $fixture->catalog->definitions();

        $write = ($definitions['modulix/publish']['execute_callback'])(['post_id' => 42, 'status' => 'publish']);
        $read = ($definitions['modulix/readiness']['execute_callback'])([]);

        $this->assertSame('request_too_large', $write['error']['code']);
        $this->assertSame('request_too_large', $read['error']['code']);
    }

    private function fixture(
        bool $manage = true,
        bool $publish = true,
        bool $preview = true,
        ?string $limitError = null,
        bool $logComplete = true,
        array $editable = [42, 17, 18]
    ): McpM4Fixture {
        $document = new FakeM4Document(42, 'elementor_library', [], [], []);
        $kit = new FakeM4Document(99, 'elementor_library', [], [
            'system_colors' => [[
                '_id' => 'primary',
                'title' => 'Primary',
                'color' => '#000000',
            ]],
        ], $this->kitControls());
        $source = new FakeM4Source($document, $kit);
        $access = new FakeM4AccessGate($manage, $publish, $preview, $editable);
        $limits = new FakeM4RequestGate($limitError);
        $logger = new FakeM4Logger($logComplete);
        $validator = new ElementTreeValidator(new ArraySchemaRegistry([]), new FakeM4HtmlSanitizer());
        $catalog = new M4AbilityCatalog($source, $access, $limits, $validator, $logger);
        return new McpM4Fixture($catalog, $source, $document, $kit, $access, $limits, $logger);
    }

    private function kitControls(): array {
        $colorFields = [
            ['name' => '_id', 'type' => 'text', 'options' => [], 'default' => ''],
            ['name' => 'title', 'type' => 'text', 'options' => [], 'default' => ''],
            ['name' => 'color', 'type' => 'color', 'options' => [], 'default' => ''],
        ];
        $fontFields = [
            ['name' => '_id', 'type' => 'text', 'options' => [], 'default' => ''],
            ['name' => 'title', 'type' => 'text', 'options' => [], 'default' => ''],
            ['name' => 'typography_typography', 'type' => 'popover_toggle', 'options' => [], 'default' => 'custom'],
            ['name' => 'typography_font_family', 'type' => 'font', 'options' => [], 'default' => ''],
        ];
        return [
            ['name' => 'system_colors', 'type' => 'repeater', 'fields' => $colorFields, 'options' => [], 'default' => []],
            ['name' => 'custom_colors', 'type' => 'repeater', 'fields' => $colorFields, 'options' => [], 'default' => []],
            ['name' => 'system_typography', 'type' => 'repeater', 'fields' => $fontFields, 'options' => [], 'default' => []],
            ['name' => 'custom_typography', 'type' => 'repeater', 'fields' => $fontFields, 'options' => [], 'default' => []],
        ];
    }
}

final class McpM4Fixture {
    public function __construct(
        public M4AbilityCatalog $catalog,
        public FakeM4Source $source,
        public FakeM4Document $document,
        public FakeM4Document $kit,
        public FakeM4AccessGate $access,
        public FakeM4RequestGate $limits,
        public FakeM4Logger $logger
    ) {}
}

final class FakeM4Document implements WritableDocument {
    public int $saveCount = 0;

    public function __construct(
        private int $documentId,
        private string $type,
        private array $elements,
        private array $settings,
        private array $controls
    ) {}

    public function id(): int { return $this->documentId; }
    public function postType(): string { return $this->type; }
    public function elements(): array { return $this->elements; }
    public function pageSettings(): array { return $this->settings; }
    public function pageSettingsControls(): array { return $this->controls; }
    public function canReadAttachment(int $attachmentId): bool { return false; }
    public function save(array $data): bool {
        $this->saveCount++;
        if (isset($data['settings']) && is_array($data['settings'])) {
            $this->settings = $data['settings'];
        }
        return true;
    }
    public function replaceSettings(array $settings): void { $this->settings = $settings; }
}

final class FakeM4Source implements M4Source {
    public array $conditions = ['include/general'];
    public string $status = 'draft';
    public int $conditionSaveCount = 0;
    public int $statusSaveCount = 0;
    public int $revisionCount = 0;
    public array $locked = [];
    public array $unlocked = [];
    public array $restores = [];

    public function __construct(private FakeM4Document $document, private FakeM4Document $kit) {}
    public function getDocument(int $postId): ?WritableDocument { return $postId === 42 ? $this->document : null; }
    public function activeKit(): ?WritableDocument { return $this->kit; }
    public function conditions(int $postId): ?array { return $postId === 42 ? $this->conditions : null; }
    public function conditionRegistry(): array {
        return [
            'general' => ['sub_conditions' => ['singular', 'archive'], 'controls' => []],
            'singular' => ['sub_conditions' => ['post', 'page'], 'controls' => []],
            'archive' => ['sub_conditions' => [], 'controls' => []],
            'post' => ['sub_conditions' => ['post_child'], 'controls' => [
                'post_id' => ['autocomplete' => ['object' => 'post', 'query' => ['post_type' => 'post']]],
            ]],
            'post_child' => ['sub_conditions' => [], 'controls' => []],
            'page' => ['sub_conditions' => [], 'controls' => [
                'post_id' => ['autocomplete' => ['object' => 'post', 'query' => ['post_type' => 'page']]],
            ]],
        ];
    }
    public function conditionTarget(string $object, int $targetId): ?array {
        return match ($targetId) {
            17 => ['object' => 'post', 'id' => 17, 'post_type' => 'page'],
            18 => ['object' => 'post', 'id' => 18, 'post_type' => 'post'],
            default => null,
        };
    }
    public function saveConditions(int $postId, array $conditions): bool {
        $this->conditionSaveCount++;
        $this->conditions = $conditions;
        return true;
    }
    public function status(int $postId): ?string { return $postId === 42 ? $this->status : null; }
    public function saveStatus(int $postId, string $status): bool {
        $this->statusSaveCount++;
        $this->status = $status;
        return true;
    }
    public function previewUrl(int $postId): ?string {
        $host = 'https://' . 'example' . '.invalid';
        $nonce = 'preview_' . 'nonce=' . 'remove-' . 'me';
        return $postId === 42 ? $host . '/?preview=true&' . $nonce : null;
    }
    public function readiness(): array {
        return [
            'ready' => false,
            'missing' => [['item' => 'widget:missing', 'label' => 'Missing', 'document_ids' => [42], 'documents' => 1]],
            'ecosystem' => [],
        ];
    }
    public function snapshotDocument(int $documentId): ?array {
        $state = [
            'conditions' => $documentId === 42 ? $this->conditions : [],
            'status' => $documentId === 42 ? $this->status : '',
            'settings' => $documentId === 99 ? $this->kit->pageSettings() : [],
        ];
        return $state + ['hashes' => ['state' => hash('sha256', serialize($state))]];
    }
    public function createRevision(int $postId): int { $this->revisionCount++; return 701; }
    public function restoreRevision(int $documentId, int $revisionId, array $snapshot): bool {
        $this->restores[] = ['document_id' => $documentId, 'revision_id' => $revisionId];
        if ($documentId === 42) {
            $this->conditions = $snapshot['conditions'];
            $this->status = $snapshot['status'];
        } elseif ($documentId === 99) {
            $this->kit->replaceSettings($snapshot['settings']);
        }
        return true;
    }
    public function lockDocument(int $documentId): bool { $this->locked[] = $documentId; return true; }
    public function unlockDocument(int $documentId): void { $this->unlocked[] = $documentId; }
}

final class FakeM4AccessGate implements M4AccessGate {
    public function __construct(
        private bool $manage,
        private bool $publish,
        private bool $preview,
        private array $editable
    ) {}
    public function canManageOptions(): bool { return $this->manage; }
    public function canPublishDocument(int $postId, string $status): bool {
        return $this->canEditDocument($postId) && ($status === 'draft' || ($status === 'publish' && $this->publish));
    }
    public function canPreviewDocument(int $postId): bool { return $this->preview && $postId === 42; }
    public function canEditDocument(int $postId): bool { return in_array($postId, $this->editable, true); }
    public function userId(): int { return 8; }
}

final class FakeM4RequestGate implements RequestGate {
    public int $readChecks = 0;
    public int $writeChecks = 0;
    public function __construct(private ?string $error) {}
    public function checkRead(int $userId, array $input): ?string { $this->readChecks++; return $this->error; }
    public function checkWrite(int $userId, array $input, int $batchSize): ?string { $this->writeChecks++; return $this->error; }
}

final class FakeM4Logger implements ChangeLogger {
    public array $rows = [];
    public function __construct(private bool $completeResult) {}
    public function begin(int $userId, string $ability, int $documentId, int $size, int $elementCount = 0, array $counts = []): int {
        $this->rows[] = compact('userId', 'ability', 'documentId', 'size', 'elementCount', 'counts') + [
            'status' => 'pending',
            'revision_id' => 0,
        ];
        return count($this->rows);
    }
    public function complete(int $rowId, int $revisionId): bool {
        if (!$this->completeResult) {
            return false;
        }
        $this->rows[$rowId - 1]['status'] = 'done';
        $this->rows[$rowId - 1]['revision_id'] = $revisionId;
        return true;
    }
    public function fail(int $rowId, string $status = 'failed', int $revisionId = 0): bool {
        $this->rows[$rowId - 1]['status'] = $status;
        $this->rows[$rowId - 1]['revision_id'] = $revisionId;
        return true;
    }
}

final class FakeM4HtmlSanitizer implements HtmlSanitizer {
    public function sanitize(string $html): string { return strip_tags($html); }
}
