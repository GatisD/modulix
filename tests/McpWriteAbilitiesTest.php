<?php
use Modulix\Mcp\AccessGate;
use Modulix\Mcp\AbilityRegistrar;
use Modulix\Mcp\ArraySchemaRegistry;
use Modulix\Mcp\ChangeLogger;
use Modulix\Mcp\ElementTreeValidator;
use Modulix\Mcp\HtmlSanitizer;
use Modulix\Mcp\RequestGate;
use Modulix\Mcp\WritableDocument;
use Modulix\Mcp\WriteAbilityCatalog;
use Modulix\Mcp\WriteSource;
use PHPUnit\Framework\TestCase;

final class McpWriteAbilitiesTest extends TestCase {
    public function test_write_definitions_have_safe_defaults_and_mutating_annotations(): void {
        $definitions = $this->fixture()->catalog->definitions();

        $this->assertSame([
            'modulix/create-document',
            'modulix/update-elements',
            'modulix/set-page-settings',
        ], array_keys($definitions));
        foreach ($definitions as $name => $definition) {
            $this->assertSame(true, $definition['input_schema']['properties']['dry_run']['default']);
            $this->assertFalse($definition['meta']['annotations']['readonly']);
            $this->assertFalse($definition['meta']['annotations']['idempotent']);
            $this->assertSame($name !== 'modulix/create-document', $definition['meta']['annotations']['destructive']);
        }
        $this->assertSame(
            ['modulix/update-elements'],
            array_keys(AbilityRegistrar::select($definitions, ['modulix/update-elements']))
        );
    }

    public function test_create_document_dry_run_writes_nothing_and_real_write_creates_a_draft_revision_and_log(): void {
        $fixture = $this->fixture();
        $definition = $fixture->catalog->definitions()['modulix/create-document'];
        $input = [
            'post_type' => 'page',
            'title' => 'Draft page',
            'elements' => $this->validTree('New'),
            'page_settings' => ['hide_title' => 'yes'],
        ];

        $dry = ($definition['execute_callback'])($input);

        $this->assertTrue($dry['dry_run']);
        $this->assertFalse($dry['saved']);
        $this->assertSame([], $dry['validation_errors']);
        $this->assertSame(['root', 'heading'], $dry['diff']['added']);
        $this->assertSame(0, $fixture->source->createCount);
        $this->assertSame(0, $fixture->document->saveCount);
        $this->assertSame([], $fixture->logger->rows);

        $written = ($definition['execute_callback'])($input + ['dry_run' => false]);

        $this->assertTrue($written['saved']);
        $this->assertFalse($written['dry_run']);
        $this->assertSame(901, $written['revision_id']);
        $this->assertSame(1, $fixture->source->createCount);
        $this->assertSame('draft', $fixture->source->lastCreate['status']);
        $this->assertSame(1, $fixture->document->saveCount);
        $this->assertCount(1, $fixture->logger->rows);
        $this->assertSame(901, $fixture->logger->rows[0]['revision_id']);
        $this->assertSame('modulix/create-document', $fixture->logger->rows[0]['ability']);
    }

    public function test_create_document_validation_and_permission_failures_write_nothing(): void {
        $invalid = $this->fixture();
        $definition = $invalid->catalog->definitions()['modulix/create-document'];
        $result = ($definition['execute_callback'])([
            'post_type' => 'page',
            'elements' => [['id' => 'bad', 'elType' => 'widget', 'widgetType' => 'unknown', 'settings' => []]],
            'dry_run' => false,
        ]);

        $this->assertSame('unknown_widget', $result['validation_errors'][0]['code']);
        $this->assertSame(0, $invalid->source->createCount);
        $this->assertSame(0, $invalid->source->revisionCount);
        $this->assertSame([], $invalid->logger->rows);

        $denied = $this->fixture(create: false);
        $definition = $denied->catalog->definitions()['modulix/create-document'];
        $this->assertFalse(($definition['permission_callback'])(['post_type' => 'page']));
        $result = ($definition['execute_callback'])([
            'post_type' => 'page',
            'elements' => $this->validTree(),
            'dry_run' => false,
        ]);
        $this->assertSame('forbidden', $result['error']['code']);
        $this->assertSame(0, $denied->source->createCount);
        $this->assertSame(0, $denied->source->revisionCount);
        $this->assertSame([], $denied->logger->rows);
    }

    public function test_create_document_validates_page_settings_against_the_document_controls_before_saving(): void {
        $fixture = $this->fixture();
        $definition = $fixture->catalog->definitions()['modulix/create-document'];

        foreach ([
            [['unknown_nested' => ['value' => true]], 'unknown_setting', 'page_settings.unknown_nested'],
            [['hide_title' => ['wrong']], 'wrong_type', 'page_settings.hide_title'],
            [['post_title' => ['wrong']], 'wrong_type', 'page_settings.post_title'],
            [['template' => 'not-offered.php'], 'invalid_option', 'page_settings.template'],
            [['post_featured_image' => ['id' => 777]], 'forbidden_attachment', 'page_settings.post_featured_image.id'],
        ] as [$settings, $code, $path]) {
            $result = ($definition['execute_callback'])([
                'post_type' => 'page',
                'elements' => $this->validTree(),
                'page_settings' => $settings,
                'dry_run' => false,
            ]);

            $this->assertSame($code, $result['validation_errors'][0]['code']);
            $this->assertSame($path, $result['validation_errors'][0]['path']);
        }

        $dry = ($definition['execute_callback'])([
            'post_type' => 'page',
            'elements' => $this->validTree(),
            'page_settings' => ['custom_html' => '<p>Safe</p><script>bad()</script>'],
        ]);

        $this->assertSame([], $dry['validation_errors']);
        $this->assertSame('<p>Safe</p>', $dry['page_settings']['custom_html']);
        $this->assertSame(0, $fixture->source->createCount);
        $this->assertSame(0, $fixture->document->saveCount);
    }

    public function test_create_document_deletes_only_its_draft_when_any_later_boundary_fails(): void {
        foreach ([
            'revision' => ['revisionResult' => 0],
            'log_insert' => ['logBeginResult' => false],
            'save' => ['saveMode' => 'false_after_write'],
            'log_complete' => ['logCompleteResult' => false],
        ] as $boundary => $options) {
            $fixture = $this->fixture(...$options);
            $definition = $fixture->catalog->definitions()['modulix/create-document'];

            $result = ($definition['execute_callback'])([
                'post_type' => 'page',
                'elements' => $this->validTree('Created'),
                'dry_run' => false,
            ]);

            $this->assertFalse($result['saved'] ?? false, $boundary);
            $this->assertSame([42], $fixture->source->deletedDocumentIds, $boundary);
            $this->assertSame([], $fixture->source->otherDocumentIds, $boundary);
        }
    }

    public function test_create_document_deletes_its_draft_when_post_insert_steps_throw(): void {
        foreach (['controls', 'elements', 'settings', 'lock'] as $boundary) {
            $fixture = $this->fixture();
            match ($boundary) {
                'controls' => $fixture->document->throwOnControlsRead = 1,
                'elements' => $fixture->document->throwOnElementsRead = 1,
                'settings' => $fixture->document->throwOnSettingsRead = 1,
                'lock' => $fixture->source->throwOnLock = true,
            };
            $definition = $fixture->catalog->definitions()['modulix/create-document'];

            try {
                ($definition['execute_callback'])([
                    'post_type' => 'page',
                    'elements' => $this->validTree('Created'),
                    'dry_run' => false,
                ]);
                $this->fail('The injected ' . $boundary . ' failure should escape.');
            } catch (RuntimeException $error) {
                $this->assertSame('Injected ' . $boundary . ' failure.', $error->getMessage());
            }

            $this->assertSame([42], $fixture->source->deletedDocumentIds, $boundary);
        }
    }

    public function test_update_elements_is_all_or_nothing_and_reports_unknown_ids_with_operation_index(): void {
        $fixture = $this->fixture();
        $definition = $fixture->catalog->definitions()['modulix/update-elements'];
        $input = [
            'post_id' => 42,
            'operations' => [
                ['action' => 'update', 'element_id' => 'heading', 'settings' => ['title' => 'Changed']],
                ['action' => 'delete', 'element_id' => 'missing'],
            ],
            'dry_run' => false,
        ];

        $result = ($definition['execute_callback'])($input);

        $this->assertSame('unknown_element_id', $result['validation_errors'][0]['code']);
        $this->assertSame(1, $result['validation_errors'][0]['operation_index']);
        $this->assertSame(0, $fixture->source->revisionCount);
        $this->assertSame(0, $fixture->document->saveCount);
        $this->assertSame('Old', $fixture->document->elements()[0]['elements'][0]['settings']['title']);
        $this->assertSame([], $fixture->logger->rows);
    }

    public function test_update_elements_dry_run_writes_nothing_and_real_write_saves_once_and_logs_revision(): void {
        $fixture = $this->fixture();
        $definition = $fixture->catalog->definitions()['modulix/update-elements'];
        $input = [
            'post_id' => 42,
            'operations' => [
                ['action' => 'insert', 'parent_id' => 'root', 'index' => 1, 'element' => [
                    'id' => 'second',
                    'elType' => 'widget',
                    'widgetType' => 'heading',
                    'settings' => ['title' => 'Second'],
                    'elements' => [],
                ]],
                ['action' => 'update', 'element_id' => 'heading', 'settings' => ['title' => 'Changed']],
                ['action' => 'move', 'element_id' => 'heading', 'parent_id' => 'document', 'index' => 1],
            ],
        ];

        $dry = ($definition['execute_callback'])($input);

        $this->assertTrue($dry['dry_run']);
        $this->assertSame(['second'], $dry['diff']['added']);
        $this->assertSame(['heading'], $dry['diff']['moved']);
        $this->assertSame(0, $fixture->source->revisionCount);
        $this->assertSame(0, $fixture->document->saveCount);
        $this->assertSame([], $fixture->logger->rows);

        $written = ($definition['execute_callback'])($input + ['dry_run' => false]);

        $this->assertTrue($written['saved']);
        $this->assertSame(901, $written['revision_id']);
        $this->assertSame(1, $fixture->source->revisionCount);
        $this->assertSame(1, $fixture->document->saveCount);
        $this->assertCount(1, $fixture->logger->rows);
        $this->assertSame(901, $fixture->logger->rows[0]['revision_id']);
        $this->assertSame('modulix/update-elements', $fixture->logger->rows[0]['ability']);
        $this->assertSame(1, $fixture->logger->rows[0]['counts']['added']);
        $this->assertSame(1, $fixture->logger->rows[0]['counts']['updated']);
        $this->assertSame(1, $fixture->logger->rows[0]['counts']['moved']);
        $this->assertSame('done', $fixture->logger->rows[0]['status']);
    }

    public function test_pending_log_insert_failure_prevents_the_document_save(): void {
        $fixture = $this->fixture(logBeginResult: false);
        $definition = $fixture->catalog->definitions()['modulix/update-elements'];

        $result = ($definition['execute_callback'])([
            'post_id' => 42,
            'operations' => [[
                'action' => 'update',
                'element_id' => 'heading',
                'settings' => ['title' => 'Must not be saved'],
            ]],
            'dry_run' => false,
        ]);

        $this->assertSame('change_log_failed', $result['error']['code']);
        $this->assertSame(0, $fixture->document->saveCount);
        $this->assertSame('Old', $fixture->document->elements()[0]['elements'][0]['settings']['title']);
    }

    public function test_mid_save_exception_restores_elements_and_page_settings_from_the_pre_write_revision(): void {
        $fixture = $this->fixture(saveMode: 'throw_after_write');
        $definition = $fixture->catalog->definitions()['modulix/set-page-settings'];

        $result = ($definition['execute_callback'])([
            'post_id' => 42,
            'settings' => ['hide_title' => 'yes'],
            'dry_run' => false,
        ]);

        $this->assertSame('save_failed', $result['error']['code']);
        $this->assertSame([['document_id' => 42, 'revision_id' => 901]], $fixture->source->restoredRevisions);
        $this->assertSame($fixture->document->initialElements, $fixture->document->elements());
        $this->assertSame($fixture->document->initialSettings, $fixture->document->pageSettings());
        $this->assertSame('failed', $fixture->logger->rows[0]['status']);
    }

    public function test_false_save_and_log_completion_failure_both_restore_the_pre_write_revision(): void {
        foreach ([
            'false save' => ['saveMode' => 'false_after_write'],
            'log completion' => ['logCompleteResult' => false],
        ] as $label => $options) {
            $fixture = $this->fixture(...$options);
            $definition = $fixture->catalog->definitions()['modulix/update-elements'];

            $result = ($definition['execute_callback'])([
                'post_id' => 42,
                'operations' => [[
                    'action' => 'update',
                    'element_id' => 'heading',
                    'settings' => ['title' => 'Changed'],
                ]],
                'dry_run' => false,
            ]);

            $this->assertFalse($result['saved'] ?? false, $label);
            $this->assertSame($fixture->document->initialElements, $fixture->document->elements(), $label);
            $this->assertSame($fixture->document->initialSettings, $fixture->document->pageSettings(), $label);
            $this->assertSame('failed', $fixture->logger->rows[0]['status'], $label);
        }
    }

    public function test_post_save_throwables_restore_the_pre_write_revision_and_finalize_the_log(): void {
        foreach ([
            'document reload' => ['ability' => 'modulix/update-elements', 'failure' => 'get', 'code' => 'save_failed'],
            'elements read' => ['ability' => 'modulix/update-elements', 'failure' => 'elements', 'code' => 'save_failed'],
            'settings read' => ['ability' => 'modulix/set-page-settings', 'failure' => 'settings', 'code' => 'save_failed'],
            'log completion' => ['ability' => 'modulix/update-elements', 'failure' => 'complete', 'code' => 'change_log_failed'],
        ] as $boundary => $case) {
            $fixture = $this->fixture();
            match ($case['failure']) {
                'get' => $fixture->source->throwOnGetAfterSave = true,
                'elements' => $fixture->document->throwOnElementsAfterSave = true,
                'settings' => $fixture->document->throwOnSettingsAfterSave = true,
                'complete' => $fixture->logger->throwOnComplete = true,
            };
            $definition = $fixture->catalog->definitions()[$case['ability']];
            $input = $case['ability'] === 'modulix/set-page-settings'
                ? ['post_id' => 42, 'settings' => ['hide_title' => 'yes'], 'dry_run' => false]
                : [
                    'post_id' => 42,
                    'operations' => [[
                        'action' => 'update',
                        'element_id' => 'heading',
                        'settings' => ['title' => 'Changed'],
                    ]],
                    'dry_run' => false,
                ];

            $result = ($definition['execute_callback'])($input);

            $this->assertSame($case['code'], $result['error']['code'], $boundary);
            $this->assertSame(1, $fixture->document->saveCount, $boundary);
            $this->assertSame([['document_id' => 42, 'revision_id' => 901]], $fixture->source->restoredRevisions, $boundary);
            $this->assertSame($fixture->document->initialElements, $fixture->document->elements(), $boundary);
            $this->assertSame($fixture->document->initialSettings, $fixture->document->pageSettings(), $boundary);
            $this->assertSame('failed', $fixture->logger->rows[0]['status'], $boundary);
        }
    }

    public function test_pre_save_throwables_finalize_the_log_without_saving(): void {
        foreach ([
            'revision creation' => ['failure' => 'revision', 'code' => 'revision_failed', 'revision_id' => 0],
            'base snapshot' => ['failure' => 'snapshot', 'code' => 'document_changed', 'revision_id' => 901],
        ] as $boundary => $case) {
            $fixture = $this->fixture();
            if ($case['failure'] === 'revision') {
                $fixture->source->throwOnRevision = true;
            } else {
                $fixture->source->throwOnSnapshotAfterRevision = true;
            }
            $definition = $fixture->catalog->definitions()['modulix/update-elements'];

            $result = ($definition['execute_callback'])([
                'post_id' => 42,
                'operations' => [[
                    'action' => 'update',
                    'element_id' => 'heading',
                    'settings' => ['title' => 'Changed'],
                ]],
                'dry_run' => false,
            ]);

            $this->assertSame($case['code'], $result['error']['code'], $boundary);
            $this->assertSame(0, $fixture->document->saveCount, $boundary);
            $this->assertSame('failed', $fixture->logger->rows[0]['status'], $boundary);
            $this->assertSame($case['revision_id'], $fixture->logger->rows[0]['revision_id'], $boundary);
            $this->assertSame([42], $fixture->source->unlockedDocumentIds, $boundary);
        }
    }

    public function test_throwing_log_failure_after_pre_save_throwable_returns_audit_finalization_failed(): void {
        foreach (['revision', 'snapshot'] as $boundary) {
            $fixture = $this->fixture();
            $fixture->logger->throwOnFail = true;
            if ($boundary === 'revision') {
                $fixture->source->throwOnRevision = true;
            } else {
                $fixture->source->throwOnSnapshotAfterRevision = true;
            }
            $definition = $fixture->catalog->definitions()['modulix/update-elements'];

            $result = ($definition['execute_callback'])([
                'post_id' => 42,
                'operations' => [[
                    'action' => 'update',
                    'element_id' => 'heading',
                    'settings' => ['title' => 'Changed'],
                ]],
                'dry_run' => false,
            ]);

            $this->assertSame('audit_finalization_failed', $result['error']['code'], $boundary);
            $this->assertSame(0, $fixture->document->saveCount, $boundary);
            $this->assertSame('pending', $fixture->logger->rows[0]['status'], $boundary);
            $this->assertSame([42], $fixture->source->unlockedDocumentIds, $boundary);
        }
    }

    public function test_restore_verification_failure_returns_restore_failed_with_the_revision_id(): void {
        $fixture = $this->fixture(saveMode: 'false_after_write', restoreResult: false);
        $definition = $fixture->catalog->definitions()['modulix/update-elements'];

        $result = ($definition['execute_callback'])([
            'post_id' => 42,
            'operations' => [[
                'action' => 'update',
                'element_id' => 'heading',
                'settings' => ['title' => 'Changed'],
            ]],
            'dry_run' => false,
        ]);

        $this->assertSame('restore_failed', $result['error']['code']);
        $this->assertSame(901, $result['error']['data']['revision_id']);
        $this->assertSame('restore_failed', $fixture->logger->rows[0]['status']);
    }

    public function test_audit_finalization_failure_is_distinct_after_each_logged_failure_boundary(): void {
        foreach ([
            'revision' => ['revisionResult' => 0],
            'save' => ['saveMode' => 'false_after_write'],
            'restore' => ['saveMode' => 'false_after_write', 'restoreResult' => false],
            'completion' => ['logCompleteResult' => false],
        ] as $boundary => $options) {
            $fixture = $this->fixture(...($options + ['logFailResult' => false]));
            $definition = $fixture->catalog->definitions()['modulix/update-elements'];

            $result = ($definition['execute_callback'])([
                'post_id' => 42,
                'operations' => [[
                    'action' => 'update',
                    'element_id' => 'heading',
                    'settings' => ['title' => 'Changed'],
                ]],
                'dry_run' => false,
            ]);

            $this->assertSame('audit_finalization_failed', $result['error']['code'], $boundary);
            $this->assertSame('pending', $fixture->logger->rows[0]['status'], $boundary);
        }
    }

    public function test_real_writes_hold_and_release_the_document_lock(): void {
        $fixture = $this->fixture();
        $definition = $fixture->catalog->definitions()['modulix/update-elements'];

        $result = ($definition['execute_callback'])([
            'post_id' => 42,
            'operations' => [[
                'action' => 'update',
                'element_id' => 'heading',
                'settings' => ['title' => 'Changed'],
            ]],
            'dry_run' => false,
        ]);

        $this->assertTrue($result['saved']);
        $this->assertSame([42], $fixture->source->lockedDocumentIds);
        $this->assertSame([42], $fixture->source->unlockedDocumentIds);
    }

    public function test_existing_document_writes_read_and_transform_state_only_after_locking(): void {
        $elementsFixture = $this->fixture();
        $elementsFixture->source->onLock = static function () use ($elementsFixture): void {
            $elementsFixture->document->setHeadingTitle('Concurrent');
        };
        $update = $elementsFixture->catalog->definitions()['modulix/update-elements'];

        $updated = ($update['execute_callback'])([
            'post_id' => 42,
            'operations' => [[
                'action' => 'update',
                'element_id' => 'heading',
                'settings' => ['align' => 'center'],
            ]],
            'dry_run' => false,
        ]);

        $this->assertTrue($updated['saved']);
        $this->assertSame('Concurrent', $elementsFixture->document->elements()[0]['elements'][0]['settings']['title']);
        $this->assertSame('center', $elementsFixture->document->elements()[0]['elements'][0]['settings']['align']);
        $this->assertSame('lock', $elementsFixture->source->events[0]);
        $this->assertSame('get', $elementsFixture->source->events[1]);

        $settingsFixture = $this->fixture();
        $settingsFixture->source->onLock = static function () use ($settingsFixture): void {
            $settingsFixture->document->replaceSettings(['template' => 'canvas.php']);
        };
        $setSettings = $settingsFixture->catalog->definitions()['modulix/set-page-settings'];

        $settingsResult = ($setSettings['execute_callback'])([
            'post_id' => 42,
            'settings' => ['hide_title' => 'yes'],
            'dry_run' => false,
        ]);

        $this->assertTrue($settingsResult['saved']);
        $this->assertSame(['template' => 'canvas.php', 'hide_title' => 'yes'], $settingsFixture->document->pageSettings());
        $this->assertSame('lock', $settingsFixture->source->events[0]);
        $this->assertSame('get', $settingsFixture->source->events[1]);
    }

    public function test_advisory_lock_detects_a_changed_base_before_saving(): void {
        $fixture = $this->fixture();
        $fixture->source->onRevision = static function () use ($fixture): void {
            $fixture->document->setHeadingTitle('Concurrent');
        };
        $definition = $fixture->catalog->definitions()['modulix/update-elements'];

        $result = ($definition['execute_callback'])([
            'post_id' => 42,
            'operations' => [[
                'action' => 'update',
                'element_id' => 'heading',
                'settings' => ['align' => 'center'],
            ]],
            'dry_run' => false,
        ]);

        $this->assertSame('document_changed', $result['error']['code']);
        $this->assertSame(0, $fixture->document->saveCount);
        $this->assertSame('Concurrent', $fixture->document->elements()[0]['elements'][0]['settings']['title']);
        $this->assertSame('failed', $fixture->logger->rows[0]['status']);
    }

    public function test_base_snapshot_is_captured_before_existing_document_state_is_transformed(): void {
        $fixture = $this->fixture();
        $fixture->source->onSnapshot = static function () use ($fixture): void {
            $fixture->document->setHeadingTitle('Concurrent');
        };
        $definition = $fixture->catalog->definitions()['modulix/update-elements'];

        $result = ($definition['execute_callback'])([
            'post_id' => 42,
            'operations' => [[
                'action' => 'update',
                'element_id' => 'heading',
                'settings' => ['align' => 'center'],
            ]],
            'dry_run' => false,
        ]);

        $this->assertTrue($result['saved']);
        $this->assertSame(2, $fixture->source->snapshotCount);
        $this->assertSame('Concurrent', $fixture->document->elements()[0]['elements'][0]['settings']['title']);
        $this->assertSame('center', $fixture->document->elements()[0]['elements'][0]['settings']['align']);
    }

    public function test_update_elements_validation_and_permission_failures_write_nothing(): void {
        $invalid = $this->fixture();
        $definition = $invalid->catalog->definitions()['modulix/update-elements'];
        $result = ($definition['execute_callback'])([
            'post_id' => 42,
            'operations' => [[
                'action' => 'update',
                'element_id' => 'heading',
                'settings' => ['unknown_setting' => 'value'],
            ]],
            'dry_run' => false,
        ]);
        $this->assertSame('unknown_setting', $result['validation_errors'][0]['code']);
        $this->assertSame(0, $invalid->source->revisionCount);
        $this->assertSame(0, $invalid->document->saveCount);
        $this->assertSame([], $invalid->logger->rows);

        $denied = $this->fixture(edit: false);
        $definition = $denied->catalog->definitions()['modulix/update-elements'];
        $this->assertFalse(($definition['permission_callback'])(['post_id' => 42]));
        $result = ($definition['execute_callback'])([
            'post_id' => 42,
            'operations' => [['action' => 'delete', 'element_id' => 'heading']],
            'dry_run' => false,
        ]);
        $this->assertSame('forbidden', $result['error']['code']);
        $this->assertSame(0, $denied->source->getCount);
        $this->assertSame(0, $denied->source->revisionCount);
        $this->assertSame(0, $denied->document->saveCount);
        $this->assertSame([], $denied->logger->rows);
    }

    public function test_set_page_settings_dry_run_writes_nothing_and_real_write_saves_once_and_logs_revision(): void {
        $fixture = $this->fixture();
        $definition = $fixture->catalog->definitions()['modulix/set-page-settings'];
        $input = ['post_id' => 42, 'settings' => ['hide_title' => 'yes']];

        $dry = ($definition['execute_callback'])($input);

        $this->assertTrue($dry['dry_run']);
        $this->assertSame(['hide_title'], $dry['diff']['page_settings_changed']);
        $this->assertSame(0, $fixture->source->revisionCount);
        $this->assertSame(0, $fixture->document->saveCount);
        $this->assertSame([], $fixture->logger->rows);

        $written = ($definition['execute_callback'])($input + ['dry_run' => false]);

        $this->assertTrue($written['saved']);
        $this->assertSame(901, $written['revision_id']);
        $this->assertSame(1, $fixture->source->revisionCount);
        $this->assertSame(1, $fixture->document->saveCount);
        $this->assertCount(1, $fixture->logger->rows);
        $this->assertSame(901, $fixture->logger->rows[0]['revision_id']);
        $this->assertSame('modulix/set-page-settings', $fixture->logger->rows[0]['ability']);
    }

    public function test_set_page_settings_validation_permission_and_status_change_failures_write_nothing(): void {
        $invalid = $this->fixture(elements: [[
            'id' => 'bad',
            'elType' => 'widget',
            'widgetType' => 'unknown',
            'settings' => [],
            'elements' => [],
        ]]);
        $definition = $invalid->catalog->definitions()['modulix/set-page-settings'];
        $result = ($definition['execute_callback'])([
            'post_id' => 42,
            'settings' => ['hide_title' => 'yes'],
            'dry_run' => false,
        ]);
        $this->assertSame('unknown_widget', $result['validation_errors'][0]['code']);
        $this->assertSame(0, $invalid->source->revisionCount);
        $this->assertSame(0, $invalid->document->saveCount);
        $this->assertSame([], $invalid->logger->rows);

        $status = $this->fixture();
        $definition = $status->catalog->definitions()['modulix/set-page-settings'];
        $result = ($definition['execute_callback'])([
            'post_id' => 42,
            'settings' => ['post_status' => 'publish'],
            'dry_run' => false,
        ]);
        $this->assertSame('status_change_not_allowed', $result['validation_errors'][0]['code']);
        $this->assertSame(0, $status->source->revisionCount);
        $this->assertSame(0, $status->document->saveCount);

        $denied = $this->fixture(edit: false);
        $definition = $denied->catalog->definitions()['modulix/set-page-settings'];
        $this->assertFalse(($definition['permission_callback'])(['post_id' => 42]));
        $result = ($definition['execute_callback'])([
            'post_id' => 42,
            'settings' => ['hide_title' => 'yes'],
            'dry_run' => false,
        ]);
        $this->assertSame('forbidden', $result['error']['code']);
        $this->assertSame(0, $denied->source->getCount);
        $this->assertSame(0, $denied->source->revisionCount);
        $this->assertSame(0, $denied->document->saveCount);
        $this->assertSame([], $denied->logger->rows);
    }

    public function test_set_page_settings_validates_the_patch_against_the_document_controls(): void {
        $fixture = $this->fixture();
        $definition = $fixture->catalog->definitions()['modulix/set-page-settings'];

        foreach ([
            [['unknown_nested' => ['value' => true]], 'unknown_setting', 'settings.unknown_nested'],
            [['hide_title' => ['wrong']], 'wrong_type', 'settings.hide_title'],
            [['post_excerpt' => 'Not exposed'], 'unknown_setting', 'settings.post_excerpt'],
            [['template' => 'not-offered.php'], 'invalid_option', 'settings.template'],
            [['post_featured_image' => ['id' => 777]], 'forbidden_attachment', 'settings.post_featured_image.id'],
        ] as [$settings, $code, $path]) {
            $result = ($definition['execute_callback'])([
                'post_id' => 42,
                'settings' => $settings,
                'dry_run' => false,
            ]);

            $this->assertSame($code, $result['validation_errors'][0]['code']);
            $this->assertSame($path, $result['validation_errors'][0]['path']);
        }

        $allowed = ($definition['execute_callback'])([
            'post_id' => 42,
            'settings' => [
                'post_title' => 'Allowed title',
                'template' => 'default',
                'post_featured_image' => ['id' => 501],
                'custom_html' => '<p>Safe</p><script>bad()</script>',
            ],
        ]);

        $this->assertSame([], $allowed['validation_errors']);
        $this->assertSame('<p>Safe</p>', $allowed['page_settings']['custom_html']);

        $cleared = ($definition['execute_callback'])([
            'post_id' => 42,
            'settings' => ['post_featured_image' => ['id' => '', 'url' => '']],
        ]);
        $this->assertSame([], $cleared['validation_errors']);
    }

    public function test_write_limits_run_before_permissions_or_document_work(): void {
        $fixture = $this->fixture(limitError: 'request_too_large');
        $definition = $fixture->catalog->definitions()['modulix/update-elements'];

        $result = ($definition['execute_callback'])([
            'post_id' => 42,
            'operations' => [['action' => 'delete', 'element_id' => 'heading']],
            'dry_run' => false,
        ]);

        $this->assertSame('request_too_large', $result['error']['code']);
        $this->assertSame(1, $fixture->limits->writeChecks);
        $this->assertSame([1], $fixture->limits->batchSizes);
        $this->assertSame(0, $fixture->access->editChecks);
        $this->assertSame(0, $fixture->source->getCount);
    }

    public function test_insert_batch_limit_counts_every_element_in_the_inserted_subtree(): void {
        $fixture = $this->fixture();
        $definition = $fixture->catalog->definitions()['modulix/update-elements'];

        ($definition['execute_callback'])([
            'post_id' => 42,
            'operations' => [[
                'action' => 'insert',
                'parent_id' => 'document',
                'index' => 1,
                'element' => [
                    'id' => 'inserted-container',
                    'elType' => 'container',
                    'settings' => [],
                    'elements' => [[
                        'id' => 'inserted-heading',
                        'elType' => 'widget',
                        'widgetType' => 'heading',
                        'settings' => ['title' => 'Nested'],
                        'elements' => [],
                    ]],
                ],
            ]],
        ]);

        $this->assertSame([2], $fixture->limits->batchSizes);
        $this->assertSame(0, $fixture->document->saveCount);
    }

    private function fixture(
        bool $edit = true,
        bool $create = true,
        ?array $elements = null,
        ?string $limitError = null,
        int $revisionResult = 901,
        string $saveMode = 'success',
        bool $logBeginResult = true,
        bool $logCompleteResult = true,
        bool $logFailResult = true,
        bool $restoreResult = true
    ): McpWriteFixture {
        $document = new FakeMcpWritableDocument(
            42,
            'page',
            $elements ?? $this->validTree(),
            ['template' => 'default'],
            $saveMode
        );
        $source = new FakeMcpWriteSource($document, $revisionResult, $restoreResult);
        $access = new FakeMcpWriteAccessGate($edit, $create);
        $limits = new FakeMcpWriteRequestGate($limitError);
        $logger = new FakeMcpChangeLogger($logBeginResult, $logCompleteResult, $logFailResult);
        $validator = new ElementTreeValidator(new ArraySchemaRegistry([
            'container' => ['name' => 'container', 'type' => 'container', 'controls' => []],
            'heading' => [
                'name' => 'heading',
                'type' => 'widget',
                'controls' => [
                    ['name' => 'title', 'type' => 'text', 'options' => [], 'default' => ''],
                    ['name' => 'align', 'type' => 'select', 'options' => ['left' => 'Left', 'center' => 'Center'], 'default' => 'left'],
                ],
            ],
        ]), new FakeMcpWriteHtmlSanitizer());
        $catalog = new WriteAbilityCatalog($source, $access, $limits, $validator, $logger);
        return new McpWriteFixture($catalog, $source, $document, $access, $limits, $logger);
    }

    private function validTree(string $title = 'Old'): array {
        return [[
            'id' => 'root',
            'elType' => 'container',
            'settings' => [],
            'elements' => [[
                'id' => 'heading',
                'elType' => 'widget',
                'widgetType' => 'heading',
                'settings' => ['title' => $title],
                'elements' => [],
            ]],
        ]];
    }
}

final class McpWriteFixture {
    public function __construct(
        public WriteAbilityCatalog $catalog,
        public FakeMcpWriteSource $source,
        public FakeMcpWritableDocument $document,
        public FakeMcpWriteAccessGate $access,
        public FakeMcpWriteRequestGate $limits,
        public FakeMcpChangeLogger $logger
    ) {}
}

final class FakeMcpWritableDocument implements WritableDocument {
    public int $saveCount = 0;
    public array $savedData = [];
    public array $initialElements;
    public array $initialSettings;
    public int $elementsReads = 0;
    public int $settingsReads = 0;
    public int $controlsReads = 0;
    public ?int $throwOnElementsRead = null;
    public ?int $throwOnSettingsRead = null;
    public ?int $throwOnControlsRead = null;
    public bool $throwOnElementsAfterSave = false;
    public bool $throwOnSettingsAfterSave = false;

    public function __construct(
        private int $documentId,
        private string $postType,
        private array $tree,
        private array $settings,
        private string $saveMode = 'success'
    ) {
        $this->initialElements = $tree;
        $this->initialSettings = $settings;
    }

    public function id(): int { return $this->documentId; }
    public function postType(): string { return $this->postType; }
    public function elements(): array {
        if ($this->saveCount > 0 && $this->throwOnElementsAfterSave) {
            $this->throwOnElementsAfterSave = false;
            throw new RuntimeException('Injected post-save elements failure.');
        }
        if (++$this->elementsReads === $this->throwOnElementsRead) {
            throw new RuntimeException('Injected elements failure.');
        }
        return $this->tree;
    }
    public function pageSettings(): array {
        if ($this->saveCount > 0 && $this->throwOnSettingsAfterSave) {
            $this->throwOnSettingsAfterSave = false;
            throw new RuntimeException('Injected post-save settings failure.');
        }
        if (++$this->settingsReads === $this->throwOnSettingsRead) {
            throw new RuntimeException('Injected settings failure.');
        }
        return $this->settings;
    }
    public function pageSettingsControls(): array {
        if (++$this->controlsReads === $this->throwOnControlsRead) {
            throw new RuntimeException('Injected controls failure.');
        }
        return [
            ['name' => 'hide_title', 'type' => 'switcher', 'options' => [], 'default' => ''],
            ['name' => 'post_title', 'type' => 'text', 'options' => [], 'default' => ''],
            ['name' => 'template', 'type' => 'select', 'options' => ['default' => 'Default', 'canvas.php' => 'Canvas'], 'default' => 'default'],
            ['name' => 'post_featured_image', 'type' => 'media', 'options' => [], 'default' => []],
            ['name' => 'custom_html', 'type' => 'wysiwyg', 'options' => [], 'default' => ''],
        ];
    }
    public function canReadAttachment(int $attachmentId): bool { return $attachmentId === 501; }
    public function save(array $data): bool {
        $this->saveCount++;
        $this->savedData[] = $data;
        if (isset($data['elements'])) {
            $this->tree = $data['elements'];
        }
        if (isset($data['settings'])) {
            $this->settings = $data['settings'];
        }
        if ($this->saveMode === 'throw_after_write') {
            throw new RuntimeException('Injected save failure.');
        }
        if ($this->saveMode === 'false_after_write') {
            return false;
        }
        return true;
    }
    public function restoreInitialState(): void {
        $this->tree = $this->initialElements;
        $this->settings = $this->initialSettings;
    }
    public function setHeadingTitle(string $title): void {
        $this->tree[0]['elements'][0]['settings']['title'] = $title;
    }
    public function replaceSettings(array $settings): void {
        $this->settings = $settings;
    }
}

final class FakeMcpWriteSource implements WriteSource {
    public int $getCount = 0;
    public int $createCount = 0;
    public int $revisionCount = 0;
    public int $snapshotCount = 0;
    public array $lastCreate = [];
    public array $deletedDocumentIds = [];
    public array $otherDocumentIds = [];
    public array $restoredRevisions = [];
    public array $lockedDocumentIds = [];
    public array $unlockedDocumentIds = [];
    public array $events = [];
    public bool $throwOnLock = false;
    public bool $throwOnGetAfterSave = false;
    public bool $throwOnRevision = false;
    public bool $throwOnSnapshotAfterRevision = false;
    public $onLock = null;
    public $onRevision = null;
    public $onSnapshot = null;

    public function __construct(
        private FakeMcpWritableDocument $document,
        private int $revisionResult = 901,
        private bool $restoreResult = true
    ) {}
    public function getDocument(int $postId): ?WritableDocument {
        $this->getCount++;
        $this->events[] = 'get';
        if ($this->document->saveCount > 0 && $this->throwOnGetAfterSave) {
            $this->throwOnGetAfterSave = false;
            throw new RuntimeException('Injected post-save document reload failure.');
        }
        return $postId === $this->document->id() ? $this->document : null;
    }
    public function createDraft(string $postType, string $documentType, string $title): ?WritableDocument {
        $this->createCount++;
        $this->lastCreate = compact('postType', 'documentType', 'title') + ['status' => 'draft'];
        return $this->document;
    }
    public function snapshotDocument(int $documentId): ?array {
        $this->snapshotCount++;
        if ($this->revisionCount > 0 && $this->throwOnSnapshotAfterRevision) {
            throw new RuntimeException('Injected post-log snapshot failure.');
        }
        if ($this->snapshotCount === 1 && is_callable($this->onSnapshot)) {
            ($this->onSnapshot)();
        }
        return [
            'document_id' => $documentId,
            'elements' => $this->document->elements(),
            'settings' => $this->document->pageSettings(),
        ];
    }
    public function createRevision(int $postId): int {
        $this->revisionCount++;
        if ($this->throwOnRevision) {
            throw new RuntimeException('Injected revision failure.');
        }
        if (is_callable($this->onRevision)) {
            ($this->onRevision)();
        }
        return $this->revisionResult;
    }
    public function restoreRevision(int $documentId, int $revisionId, array $snapshot): bool {
        $this->restoredRevisions[] = ['document_id' => $documentId, 'revision_id' => $revisionId];
        if (!$this->restoreResult) {
            return false;
        }
        $this->document->restoreInitialState();
        return true;
    }
    public function deleteDocument(int $documentId): bool {
        $this->deletedDocumentIds[] = $documentId;
        return $documentId === $this->document->id();
    }
    public function getCreateValidationDocument(string $postType, string $documentType): ?WritableDocument {
        return $this->document;
    }
    public function lockDocument(int $documentId): bool {
        $this->lockedDocumentIds[] = $documentId;
        $this->events[] = 'lock';
        if ($this->throwOnLock) {
            throw new RuntimeException('Injected lock failure.');
        }
        if (is_callable($this->onLock)) {
            ($this->onLock)();
        }
        return true;
    }
    public function unlockDocument(int $documentId): void {
        $this->unlockedDocumentIds[] = $documentId;
    }
}

final class FakeMcpWriteAccessGate implements AccessGate {
    public int $editChecks = 0;
    public function __construct(private bool $edit, private bool $create) {}
    public function canReadSite(): bool { return true; }
    public function canReadDocument(int $postId): bool { return $this->edit; }
    public function canEditDocument(int $postId): bool { $this->editChecks++; return $this->edit && $postId === 42; }
    public function canCreateDocument(string $postType): bool { return $this->create; }
    public function userId(): int { return 7; }
}

final class FakeMcpWriteRequestGate implements RequestGate {
    public int $writeChecks = 0;
    public array $batchSizes = [];
    public function __construct(private ?string $error = null) {}
    public function checkRead(int $userId, array $input): ?string { return null; }
    public function checkWrite(int $userId, array $input, int $batchSize): ?string {
        $this->writeChecks++;
        $this->batchSizes[] = $batchSize;
        return $this->error;
    }
}

final class FakeMcpChangeLogger implements ChangeLogger {
    public array $rows = [];
    public bool $throwOnComplete = false;
    public bool $throwOnFail = false;
    public function __construct(
        private bool $beginResult = true,
        private bool $completeResult = true,
        private bool $failResult = true
    ) {}
    public function begin(
        int $userId,
        string $ability,
        int $documentId,
        int $size,
        int $elementCount = 0,
        array $counts = []
    ): int {
        if (!$this->beginResult) {
            return 0;
        }
        $this->rows[] = [
            'user_id' => $userId,
            'ability' => $ability,
            'document_id' => $documentId,
            'revision_id' => 0,
            'size' => $size,
            'element_count' => $elementCount,
            'counts' => $counts,
            'status' => 'pending',
        ];
        return count($this->rows);
    }
    public function complete(int $rowId, int $revisionId): bool {
        if ($this->throwOnComplete) {
            $this->throwOnComplete = false;
            throw new RuntimeException('Injected log completion failure.');
        }
        if (!$this->completeResult || !isset($this->rows[$rowId - 1])) {
            return false;
        }
        $this->rows[$rowId - 1]['revision_id'] = $revisionId;
        $this->rows[$rowId - 1]['status'] = 'done';
        return true;
    }
    public function fail(int $rowId, string $status = 'failed', int $revisionId = 0): bool {
        if ($this->throwOnFail) {
            $this->throwOnFail = false;
            throw new RuntimeException('Injected log failure finalization failure.');
        }
        if (!$this->failResult || !isset($this->rows[$rowId - 1])) {
            return false;
        }
        $this->rows[$rowId - 1]['status'] = $status;
        $this->rows[$rowId - 1]['revision_id'] = $revisionId;
        return true;
    }
}

final class FakeMcpWriteHtmlSanitizer implements HtmlSanitizer {
    public function sanitize(string $html): string { return str_replace('<script>bad()</script>', '', $html); }
}
