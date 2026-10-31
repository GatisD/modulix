<?php
namespace Modulix\Mcp;

final class WriteAbilityCatalog {
    private ElementTreeOperations $operations;

    public function __construct(
        private WriteSource $source,
        private AccessGate $access,
        private RequestGate $limits,
        private ElementTreeValidator $validator,
        private ChangeLogger $logger,
        ?ElementTreeOperations $operations = null
    ) {
        $this->operations = $operations ?? new ElementTreeOperations();
    }

    public function definitions(): array {
        return [
            'modulix/create-document' => $this->definition(
                'Create Elementor document',
                'Validates and creates a draft Elementor page, post, or template. Dry run defaults to true.',
                $this->createInputSchema(),
                fn($input = []): bool => $this->canCreate((array) $input),
                fn($input = []): mixed => $this->create((array) $input),
                false
            ),
            'modulix/update-elements' => $this->definition(
                'Update Elementor elements',
                'Applies one all-or-nothing batch of insert, update, move, and delete operations. Dry run defaults to true.',
                $this->updateInputSchema(),
                fn($input = []): bool => $this->access->canEditDocument((int) (((array) $input)['post_id'] ?? 0)),
                fn($input = []): mixed => $this->update((array) $input),
                true
            ),
            'modulix/set-page-settings' => $this->definition(
                'Set Elementor page settings',
                'Updates Elementor page settings without changing post status. Dry run defaults to true.',
                $this->settingsInputSchema(),
                fn($input = []): bool => $this->access->canEditDocument((int) (((array) $input)['post_id'] ?? 0)),
                fn($input = []): mixed => $this->setPageSettings((array) $input),
                true
            ),
        ];
    }

    private function create(array $input): mixed {
        $elements = isset($input['elements']) && is_array($input['elements']) ? $input['elements'] : [];
        $limitError = $this->limits->checkWrite($this->access->userId(), $input, $this->countElements($elements));
        if ($limitError !== null) {
            return $this->error($limitError, 'The MCP write limit was exceeded.');
        }

        $postType = isset($input['post_type']) && is_string($input['post_type']) ? $input['post_type'] : 'page';
        if (!$this->canCreate($input)) {
            return $this->error('forbidden', 'The current user cannot create this document type.');
        }
        $pageSettings = isset($input['page_settings']) && is_array($input['page_settings']) ? $input['page_settings'] : [];
        $validation = $this->validator->validate($elements);
        $errors = $validation['errors'];
        $statusError = $this->statusError($pageSettings, 'page_settings');
        if ($statusError !== null) {
            $errors[] = $statusError;
        }
        if ($errors !== []) {
            $diff = $this->diff([], $validation['elements'], [], $pageSettings);
            return $this->validationResponse(
                $this->isDryRun($input),
                0,
                [],
                $validation['elements'],
                [],
                $pageSettings,
                $errors,
                $diff
            );
        }
        $documentType = isset($input['document_type']) && is_string($input['document_type']) && $input['document_type'] !== ''
            ? $input['document_type']
            : ($postType === 'elementor_library' ? 'page' : $postType);
        if ($pageSettings !== []) {
            $validationDocument = $this->source->getCreateValidationDocument($postType, $documentType);
            if ($validationDocument === null) {
                return $this->error('create_failed', 'The Elementor document type could not be loaded for validation.');
            }
            $settingsValidation = $this->validateSettings(
                $pageSettings,
                $validationDocument,
                'page_settings'
            );
            $pageSettings = $settingsValidation['settings'];
            $errors = $settingsValidation['errors'];
        }
        $diff = $this->diff([], $validation['elements'], [], $pageSettings);
        if ($errors !== []) {
            return $this->validationResponse($this->isDryRun($input), 0, [], $validation['elements'], [], $pageSettings, $errors, $diff);
        }

        $dryRun = $this->isDryRun($input);
        if ($dryRun) {
            return $this->successResponse(true, false, 0, 0, $validation['elements'], $pageSettings, $diff);
        }

        $title = isset($input['title']) && is_string($input['title']) ? $input['title'] : '';
        $document = $this->source->createDraft($postType, $documentType, $title);
        if ($document === null) {
            return $this->error('create_failed', 'The Elementor draft could not be created.');
        }
        $documentId = $document->id();
        $completed = false;
        $deleted = true;
        try {
            $createdSettingsValidation = $this->validateSettings($pageSettings, $document, 'page_settings');
            if ($createdSettingsValidation['errors'] !== []) {
                $result = $this->validationResponse(
                    false,
                    0,
                    [],
                    $validation['elements'],
                    [],
                    $pageSettings,
                    $createdSettingsValidation['errors'],
                    $diff
                );
            } else {
                $pageSettings = $createdSettingsValidation['settings'];
                $result = $this->persist(
                    'modulix/create-document',
                    $document,
                    ['elements' => $validation['elements'], 'settings' => $pageSettings],
                    $input,
                    $validation['elements'],
                    $pageSettings,
                    $diff,
                    true
                );
            }
            $completed = ($result['saved'] ?? false) === true;
        } finally {
            if (!$completed) {
                $deleted = $this->source->deleteDocument($documentId);
            }
        }
        if (!$completed && !$deleted) {
            return $this->error('rollback_failed', 'The failed Elementor draft could not be deleted.');
        }
        return $result;
    }

    private function update(array $input): mixed {
        $operations = isset($input['operations']) && is_array($input['operations']) ? $input['operations'] : [];
        $limitError = $this->limits->checkWrite(
            $this->access->userId(),
            $input,
            $this->operationBatchSize($operations)
        );
        if ($limitError !== null) {
            return $this->error($limitError, 'The MCP write limit was exceeded.');
        }
        if ($operations === []) {
            return $this->validationResponse(
                $this->isDryRun($input),
                (int) ($input['post_id'] ?? 0),
                [],
                [],
                [],
                [],
                [[
                    'code' => 'empty_batch',
                    'path' => 'operations',
                    'message' => 'At least one update operation is required.',
                ]]
            );
        }

        $postId = (int) ($input['post_id'] ?? 0);
        if (!$this->access->canEditDocument($postId)) {
            return $this->error('forbidden', 'The current user cannot edit this document.');
        }
        if (!$this->isDryRun($input)) {
            return $this->withLockedDocument(
                $postId,
                fn(WritableDocument $document, array $snapshot): mixed => $this->updateDocument(
                    $input,
                    $operations,
                    $document,
                    false,
                    $snapshot
                )
            );
        }
        $document = $this->source->getDocument($postId);
        if ($document === null) {
            return $this->error('document_not_found', 'The Elementor document was not found.');
        }
        return $this->updateDocument($input, $operations, $document, true);
    }

    private function updateDocument(
        array $input,
        array $operations,
        WritableDocument $document,
        bool $dryRun,
        array $snapshot = []
    ): mixed {
        $postId = $document->id();
        $before = $document->elements();
        $settings = $document->pageSettings();
        $beforeValidation = $this->validator->validate($before);
        if (!$beforeValidation['valid']) {
            return $this->validationResponse($dryRun, $postId, $before, $before, $settings, $settings, $beforeValidation['errors']);
        }
        $operationResult = $this->operations->apply($beforeValidation['elements'], $operations);
        if (!$operationResult['valid']) {
            return $this->validationResponse($dryRun, $postId, $before, $before, $settings, $settings, $operationResult['errors']);
        }
        $validation = $this->validator->validate($operationResult['elements']);
        $diff = $this->diff($before, $validation['elements'], $settings, $settings);
        if (!$validation['valid']) {
            return $this->validationResponse($dryRun, $postId, $before, $validation['elements'], $settings, $settings, $validation['errors'], $diff);
        }
        if ($dryRun) {
            return $this->successResponse(true, false, $postId, 0, $validation['elements'], $settings, $diff);
        }
        return $this->persistLocked(
            'modulix/update-elements',
            $document,
            ['elements' => $validation['elements']],
            $input,
            $validation['elements'],
            $settings,
            $diff,
            false,
            $before,
            $settings,
            $snapshot
        );
    }

    private function setPageSettings(array $input): mixed {
        $limitError = $this->limits->checkWrite($this->access->userId(), $input, 0);
        if ($limitError !== null) {
            return $this->error($limitError, 'The MCP write limit was exceeded.');
        }

        $postId = (int) ($input['post_id'] ?? 0);
        if (!$this->access->canEditDocument($postId)) {
            return $this->error('forbidden', 'The current user cannot edit this document.');
        }
        if (!$this->isDryRun($input)) {
            return $this->withLockedDocument(
                $postId,
                fn(WritableDocument $document, array $snapshot): mixed => $this->setPageSettingsDocument(
                    $input,
                    $document,
                    false,
                    $snapshot
                )
            );
        }
        $document = $this->source->getDocument($postId);
        if ($document === null) {
            return $this->error('document_not_found', 'The Elementor document was not found.');
        }
        return $this->setPageSettingsDocument($input, $document, true);
    }

    private function setPageSettingsDocument(
        array $input,
        WritableDocument $document,
        bool $dryRun,
        array $snapshot = []
    ): mixed {
        $postId = $document->id();
        $elements = $document->elements();
        $validation = $this->validator->validate($elements);
        $beforeSettings = $document->pageSettings();
        if (!$validation['valid']) {
            return $this->validationResponse($dryRun, $postId, $elements, $elements, $beforeSettings, $beforeSettings, $validation['errors']);
        }
        $patch = isset($input['settings']) && is_array($input['settings']) ? $input['settings'] : [];
        $statusError = $this->statusError($patch, 'settings');
        if ($statusError !== null) {
            return $this->validationResponse($dryRun, $postId, $elements, $elements, $beforeSettings, $beforeSettings, [$statusError]);
        }
        $patchValidation = $this->validator->validateSettingsPatch(
            $patch,
            $document->pageSettingsControls(),
            'settings'
        );
        $patchErrors = $patchValidation['errors'];
        $featuredImageError = $this->featuredImageError(
            $patchValidation['settings'],
            $document,
            'settings'
        );
        if ($featuredImageError !== null) {
            $patchErrors[] = $featuredImageError;
        }
        if ($patchErrors !== []) {
            return $this->validationResponse(
                $dryRun,
                $postId,
                $elements,
                $elements,
                $beforeSettings,
                $beforeSettings,
                $patchErrors
            );
        }
        $settings = array_replace($beforeSettings, $patchValidation['settings']);
        $diff = $this->diff($elements, $validation['elements'], $beforeSettings, $settings);
        if ($dryRun) {
            return $this->successResponse(true, false, $postId, 0, $validation['elements'], $settings, $diff);
        }
        return $this->persistLocked(
            'modulix/set-page-settings',
            $document,
            ['settings' => $settings],
            $input,
            $validation['elements'],
            $settings,
            $diff,
            false,
            $elements,
            $beforeSettings,
            $snapshot
        );
    }

    private function withLockedDocument(int $documentId, callable $callback): mixed {
        if (!$this->source->lockDocument($documentId)) {
            return $this->error('document_locked', 'The Elementor document is locked by another editor.');
        }
        try {
            $document = $this->source->getDocument($documentId);
            if ($document === null) {
                return $this->error('document_not_found', 'The Elementor document was not found.');
            }
            $snapshot = $this->source->snapshotDocument($documentId);
            if ($snapshot === null) {
                return $this->error('snapshot_failed', 'The pre-write document state could not be captured.');
            }
            return $callback($document, $snapshot);
        } finally {
            $this->source->unlockDocument($documentId);
        }
    }

    private function persist(
        string $ability,
        WritableDocument $document,
        array $saveData,
        array $input,
        array $elements,
        array $settings,
        array $diff,
        bool $created = false
    ): mixed {
        if (!$this->source->lockDocument($document->id())) {
            return $this->error('document_locked', 'The Elementor document is locked by another editor.');
        }
        try {
            $beforeElements = $document->elements();
            $beforeSettings = $document->pageSettings();
            $snapshot = $created ? [] : $this->source->snapshotDocument($document->id());
            if (!$created && $snapshot === null) {
                return $this->error('snapshot_failed', 'The pre-write document state could not be captured.');
            }
            return $this->persistLocked(
                $ability,
                $document,
                $saveData,
                $input,
                $elements,
                $settings,
                $diff,
                $created,
                $beforeElements,
                $beforeSettings,
                $snapshot
            );
        } finally {
            $this->source->unlockDocument($document->id());
        }
    }

    private function persistLocked(
        string $ability,
        WritableDocument $document,
        array $saveData,
        array $input,
        array $elements,
        array $settings,
        array $diff,
        bool $created,
        array $beforeElements,
        array $beforeSettings,
        array $snapshot
    ): mixed {
        $encoded = json_encode($input);
        $size = $encoded === false ? 0 : strlen($encoded);
        $logId = $this->logger->begin(
            $this->access->userId(),
            $ability,
            $document->id(),
            $size,
            $this->countElements($elements),
            $this->logCounts($diff)
        );
        if ($logId <= 0) {
            return $this->error('change_log_failed', 'The pending MCP change log row could not be recorded.');
        }

        $revisionId = 0;
        $preSaveCode = 'revision_failed';
        $preSaveMessage = 'A WordPress revision could not be created before saving.';
        try {
            $revisionId = $this->source->createRevision($document->id());
            if ($revisionId <= 0) {
                return $this->preSaveFailure($preSaveCode, $preSaveMessage, $logId, $revisionId);
            }

            if (!$created) {
                $preSaveCode = 'document_changed';
                $preSaveMessage = 'The document changed after the edit lock was acquired.';
                $currentSnapshot = $this->source->snapshotDocument($document->id());
                if ($currentSnapshot === null || $currentSnapshot !== $snapshot) {
                    return $this->preSaveFailure($preSaveCode, $preSaveMessage, $logId, $revisionId);
                }
            }
        } catch (\Throwable) {
            return $this->preSaveFailure($preSaveCode, $preSaveMessage, $logId, $revisionId);
        }

        $failureCode = 'save_failed';
        $failureMessage = 'Elementor threw while saving the document.';
        try {
            $saved = $document->save($saveData);
            $failureMessage = 'Elementor could not save and verify the complete document.';
            if (!$saved || !$this->savedStateMatches($document->id(), $saveData)) {
                return $this->rollbackFailure(
                    $failureCode,
                    $failureMessage,
                    $document->id(),
                    $revisionId,
                    $logId,
                    $created,
                    $beforeElements,
                    $beforeSettings,
                    $snapshot
                );
            }
            $failureCode = 'change_log_failed';
            $failureMessage = 'The MCP change log row could not be completed.';
            if ($this->logger->complete($logId, $revisionId)) {
                return $this->successResponse(false, true, $document->id(), $revisionId, $elements, $settings, $diff);
            }
            return $this->rollbackFailure(
                $failureCode,
                $failureMessage,
                $document->id(),
                $revisionId,
                $logId,
                $created,
                $beforeElements,
                $beforeSettings,
                $snapshot
            );
        } catch (\Throwable) {
            return $this->rollbackFailure(
                $failureCode,
                $failureMessage,
                $document->id(),
                $revisionId,
                $logId,
                $created,
                $beforeElements,
                $beforeSettings,
                $snapshot
            );
        }
    }

    private function preSaveFailure(
        string $code,
        string $message,
        int $logId,
        int $revisionId
    ): array {
        try {
            $finalized = $this->logger->fail($logId, 'failed', $revisionId);
        } catch (\Throwable) {
            $finalized = false;
        }
        if (!$finalized) {
            return $this->error(
                'audit_finalization_failed',
                'The failed write could not be finalized in the MCP change log.',
                ['revision_id' => $revisionId, 'write_error' => $code]
            );
        }
        $data = $revisionId > 0 ? ['revision_id' => $revisionId] : [];
        return $this->error($code, $message, $data);
    }

    private function rollbackFailure(
        string $code,
        string $message,
        int $documentId,
        int $revisionId,
        int $logId,
        bool $created,
        array $beforeElements,
        array $beforeSettings,
        array $snapshot
    ): array {
        try {
            $restored = $created
                ? true
                : $this->restoreAndVerify($documentId, $revisionId, $beforeElements, $beforeSettings, $snapshot);
        } catch (\Throwable) {
            $restored = false;
        }
        $status = $restored ? 'failed' : 'restore_failed';
        try {
            $finalized = $this->logger->fail($logId, $status, $revisionId);
        } catch (\Throwable) {
            $finalized = false;
        }
        if (!$finalized) {
            return $this->error(
                'audit_finalization_failed',
                'The failed write could not be finalized in the MCP change log.',
                ['revision_id' => $revisionId, 'write_error' => $restored ? $code : 'restore_failed']
            );
        }
        return $restored
            ? $this->error($code, $message, ['revision_id' => $revisionId])
            : $this->error(
                'restore_failed',
                'The write failed and the pre-write document state could not be verified.',
                ['revision_id' => $revisionId]
            );
    }

    private function restoreAndVerify(
        int $documentId,
        int $revisionId,
        array $beforeElements,
        array $beforeSettings,
        array $snapshot
    ): bool {
        if (!$this->source->restoreRevision($documentId, $revisionId, $snapshot)) {
            return false;
        }
        $restored = $this->source->getDocument($documentId);
        return $restored !== null
            && $restored->elements() === $beforeElements
            && $restored->pageSettings() === $beforeSettings;
    }

    private function savedStateMatches(int $documentId, array $saveData): bool {
        $saved = $this->source->getDocument($documentId);
        if ($saved === null) {
            return false;
        }
        if (array_key_exists('elements', $saveData) && $saved->elements() !== $saveData['elements']) {
            return false;
        }
        if (!array_key_exists('settings', $saveData)) {
            return true;
        }
        $savedSettings = $saved->pageSettings();
        foreach ($saveData['settings'] as $key => $value) {
            if (!array_key_exists($key, $savedSettings) || $savedSettings[$key] !== $value) {
                return false;
            }
        }
        return true;
    }

    private function definition(
        string $label,
        string $description,
        array $inputSchema,
        callable $permission,
        callable $execute,
        bool $destructive
    ): array {
        return [
            'label' => $label,
            'description' => $description,
            'category' => 'modulix',
            'input_schema' => $inputSchema,
            'output_schema' => $this->outputSchema(),
            'permission_callback' => $permission,
            'execute_callback' => $execute,
            'meta' => [
                'show_in_rest' => false,
                'mcp' => ['public' => true],
                'annotations' => ['readonly' => false, 'idempotent' => false, 'destructive' => $destructive],
            ],
        ];
    }

    private function createInputSchema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'post_type' => ['type' => 'string', 'enum' => ['page', 'post', 'elementor_library'], 'default' => 'page'],
                'document_type' => ['type' => 'string', 'minLength' => 1, 'default' => 'page'],
                'title' => ['type' => 'string', 'default' => ''],
                'elements' => ['type' => 'array', 'items' => ['type' => 'object']],
                'page_settings' => ['type' => 'object', 'additionalProperties' => true],
                'dry_run' => ['type' => 'boolean', 'default' => true],
            ],
            'required' => ['elements'],
            'additionalProperties' => false,
        ];
    }

    private function updateInputSchema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'post_id' => ['type' => 'integer', 'minimum' => 1],
                'operations' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'action' => ['type' => 'string', 'enum' => ['insert', 'update', 'move', 'delete']],
                            'element_id' => ['type' => 'string'],
                            'parent_id' => ['type' => 'string'],
                            'index' => ['type' => 'integer', 'minimum' => 0],
                            'element' => ['type' => 'object', 'additionalProperties' => true],
                            'settings' => ['type' => 'object', 'additionalProperties' => true],
                        ],
                        'required' => ['action'],
                        'additionalProperties' => false,
                    ],
                ],
                'dry_run' => ['type' => 'boolean', 'default' => true],
            ],
            'required' => ['post_id', 'operations'],
            'additionalProperties' => false,
        ];
    }

    private function settingsInputSchema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'post_id' => ['type' => 'integer', 'minimum' => 1],
                'settings' => ['type' => 'object', 'additionalProperties' => true],
                'dry_run' => ['type' => 'boolean', 'default' => true],
            ],
            'required' => ['post_id', 'settings'],
            'additionalProperties' => false,
        ];
    }

    private function outputSchema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'dry_run' => ['type' => 'boolean'],
                'saved' => ['type' => 'boolean'],
                'document_id' => ['type' => 'integer'],
                'revision_id' => ['type' => 'integer'],
                'resulting_elements' => ['type' => 'array', 'items' => ['type' => 'object']],
                'page_settings' => ['type' => 'object', 'additionalProperties' => true],
                'diff' => ['type' => 'object', 'additionalProperties' => true],
                'validation_errors' => ['type' => 'array', 'items' => ['type' => 'object']],
            ],
            'required' => ['dry_run', 'saved', 'document_id', 'revision_id', 'resulting_elements', 'page_settings', 'diff', 'validation_errors'],
        ];
    }

    private function canCreate(array $input): bool {
        $postType = isset($input['post_type']) && is_string($input['post_type']) ? $input['post_type'] : 'page';
        return in_array($postType, ['page', 'post', 'elementor_library'], true)
            && $this->access->canCreateDocument($postType);
    }

    private function isDryRun(array $input): bool {
        return !array_key_exists('dry_run', $input) || $input['dry_run'] !== false;
    }

    private function statusError(array $settings, string $path): ?array {
        return array_key_exists('post_status', $settings)
            ? [
                'code' => 'status_change_not_allowed',
                'path' => $path . '.post_status',
                'message' => 'M3 write abilities cannot change document status.',
            ]
            : null;
    }

    private function featuredImageError(
        array $settings,
        WritableDocument $document,
        string $path
    ): ?array {
        if (!array_key_exists('post_featured_image', $settings)) {
            return null;
        }
        $value = $settings['post_featured_image'];
        if (!is_array($value) || !array_key_exists('id', $value)) {
            return [
                'code' => 'wrong_type',
                'path' => $path . '.post_featured_image.id',
                'message' => 'Featured image id must be a non-negative integer.',
            ];
        }
        $id = $value['id'];
        if ($id === '') {
            $attachmentId = 0;
        } elseif ((is_int($id) && $id >= 0) || (is_string($id) && ctype_digit($id))) {
            $attachmentId = (int) $id;
        } else {
            return [
                'code' => 'wrong_type',
                'path' => $path . '.post_featured_image.id',
                'message' => 'Featured image id must be a non-negative integer.',
            ];
        }
        if ($attachmentId > 0 && !$document->canReadAttachment($attachmentId)) {
            return [
                'code' => 'forbidden_attachment',
                'path' => $path . '.post_featured_image.id',
                'message' => 'Featured image must be a readable attachment.',
            ];
        }
        return null;
    }

    private function validateSettings(array $settings, WritableDocument $document, string $path): array {
        $validation = $this->validator->validateSettingsPatch(
            $settings,
            $document->pageSettingsControls(),
            $path
        );
        $errors = $validation['errors'];
        $featuredImageError = $this->featuredImageError($validation['settings'], $document, $path);
        if ($featuredImageError !== null) {
            $errors[] = $featuredImageError;
        }
        return ['settings' => $validation['settings'], 'errors' => $errors];
    }

    private function validationResponse(
        bool $dryRun,
        int $documentId,
        array $beforeElements,
        array $afterElements,
        array $beforeSettings,
        array $afterSettings,
        array $errors,
        ?array $diff = null
    ): array {
        return [
            'dry_run' => $dryRun,
            'saved' => false,
            'document_id' => $documentId,
            'revision_id' => 0,
            'resulting_elements' => $afterElements,
            'page_settings' => $afterSettings,
            'diff' => $diff ?? $this->diff($beforeElements, $afterElements, $beforeSettings, $afterSettings),
            'validation_errors' => $errors,
        ];
    }

    private function successResponse(
        bool $dryRun,
        bool $saved,
        int $documentId,
        int $revisionId,
        array $elements,
        array $settings,
        array $diff
    ): array {
        return [
            'dry_run' => $dryRun,
            'saved' => $saved,
            'document_id' => $documentId,
            'revision_id' => $revisionId,
            'resulting_elements' => $elements,
            'page_settings' => $settings,
            'diff' => $diff,
            'validation_errors' => [],
        ];
    }

    private function diff(array $before, array $after, array $beforeSettings, array $afterSettings): array {
        $beforeIndex = $this->indexTree($before);
        $afterIndex = $this->indexTree($after);
        $added = array_values(array_diff(array_keys($afterIndex), array_keys($beforeIndex)));
        $deleted = array_values(array_diff(array_keys($beforeIndex), array_keys($afterIndex)));
        $moved = [];
        $updated = [];
        foreach (array_intersect(array_keys($beforeIndex), array_keys($afterIndex)) as $id) {
            if ($beforeIndex[$id]['parent'] !== $afterIndex[$id]['parent']
                || $beforeIndex[$id]['index'] !== $afterIndex[$id]['index']) {
                $moved[] = $id;
            }
            $beforeNode = $beforeIndex[$id]['node'];
            $afterNode = $afterIndex[$id]['node'];
            unset($beforeNode['elements'], $afterNode['elements']);
            if ($beforeNode !== $afterNode) {
                $updated[] = $id;
            }
        }
        $settingKeys = array_unique(array_merge(array_keys($beforeSettings), array_keys($afterSettings)));
        $settingsChanged = [];
        foreach ($settingKeys as $key) {
            if (($beforeSettings[$key] ?? null) !== ($afterSettings[$key] ?? null)
                || array_key_exists($key, $beforeSettings) !== array_key_exists($key, $afterSettings)) {
                $settingsChanged[] = (string) $key;
            }
        }
        return [
            'before_count' => count($beforeIndex),
            'after_count' => count($afterIndex),
            'added' => $added,
            'updated' => $updated,
            'moved' => $moved,
            'deleted' => $deleted,
            'page_settings_changed' => $settingsChanged,
        ];
    }

    private function indexTree(array $elements): array {
        $index = [];
        $stack = [];
        $roots = array_values($elements);
        for ($rootIndex = count($roots) - 1; $rootIndex >= 0; $rootIndex--) {
            $stack[] = [$roots[$rootIndex], ElementTreeOperations::DOCUMENT_ROOT, $rootIndex];
        }
        while ($stack !== []) {
            [$node, $parent, $position] = array_pop($stack);
            if (!is_array($node)) {
                continue;
            }
            $id = $node['id'] ?? null;
            if (is_string($id) && $id !== '') {
                $index[$id] = ['parent' => $parent, 'index' => $position, 'node' => $node];
                $parent = $id;
            }
            $children = isset($node['elements']) && is_array($node['elements']) ? array_values($node['elements']) : [];
            for ($childIndex = count($children) - 1; $childIndex >= 0; $childIndex--) {
                $stack[] = [$children[$childIndex], $parent, $childIndex];
            }
        }
        return $index;
    }

    private function countElements(array $elements): int {
        $count = 0;
        $stack = $elements;
        while ($stack !== []) {
            $node = array_pop($stack);
            if (!is_array($node)) {
                continue;
            }
            $count++;
            foreach (($node['elements'] ?? []) as $child) {
                $stack[] = $child;
            }
        }
        return $count;
    }

    private function operationBatchSize(array $operations): int {
        $count = 0;
        foreach ($operations as $operation) {
            if (!is_array($operation)) {
                $count++;
                continue;
            }
            $count += ($operation['action'] ?? null) === 'insert' && isset($operation['element'])
                && is_array($operation['element'])
                    ? max(1, $this->countElements([$operation['element']]))
                    : 1;
        }
        return $count;
    }

    private function logCounts(array $diff): array {
        return [
            'added' => count($diff['added'] ?? []),
            'updated' => count($diff['updated'] ?? []),
            'moved' => count($diff['moved'] ?? []),
            'deleted' => count($diff['deleted'] ?? []),
            'page_settings' => count($diff['page_settings_changed'] ?? []),
        ];
    }

    private function error(string $code, string $message, array $data = []): mixed {
        if (class_exists('WP_Error')) {
            return new \WP_Error($code, $message, $data);
        }
        return ['error' => ['code' => $code, 'message' => $message, 'data' => $data]];
    }
}
