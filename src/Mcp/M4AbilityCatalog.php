<?php
namespace Modulix\Mcp;

final class M4AbilityCatalog {
    private const KIT_SETTINGS = [
        'system_colors',
        'custom_colors',
        'system_typography',
        'custom_typography',
    ];

    public function __construct(
        private M4Source $source,
        private M4AccessGate $access,
        private RequestGate $limits,
        private ElementTreeValidator $validator,
        private ChangeLogger $logger
    ) {}

    public function definitions(): array {
        return [
            'modulix/set-conditions' => $this->writeDefinition(
                'Set Theme Builder conditions',
                'Validates and replaces the display conditions of one Theme Builder template. Dry run defaults to true.',
                $this->conditionsInputSchema(),
                $this->conditionsOutputSchema(),
                fn($input = []): bool => $this->access->canManageOptions()
                    && $this->access->canEditDocument((int) (((array) $input)['post_id'] ?? 0)),
                fn($input = []): mixed => $this->guarded(fn(): mixed => $this->setConditions((array) $input))
            ),
            'modulix/update-kit' => $this->writeDefinition(
                'Update Elementor kit',
                'Validates and updates global colors and fonts in the active Elementor kit. Dry run defaults to true.',
                $this->kitInputSchema(),
                $this->kitOutputSchema(),
                fn($input = []): bool => $this->access->canManageOptions(),
                fn($input = []): mixed => $this->guarded(fn(): mixed => $this->updateKit((array) $input))
            ),
            'modulix/publish' => $this->writeDefinition(
                'Publish or unpublish Elementor document',
                'Changes one Elementor document between draft and published status. Dry run defaults to true.',
                $this->publishInputSchema(),
                $this->publishOutputSchema(),
                fn($input = []): bool => $this->access->canPublishDocument(
                    (int) (((array) $input)['post_id'] ?? 0),
                    is_string(((array) $input)['status'] ?? null) ? ((array) $input)['status'] : ''
                ),
                fn($input = []): mixed => $this->guarded(fn(): mixed => $this->publish((array) $input))
            ),
            'modulix/preview-url' => $this->readDefinition(
                'Get Elementor preview URL',
                'Returns a preview URL that requires an authenticated WordPress editor session and contains no preview nonce.',
                $this->documentInputSchema(),
                [
                    'type' => 'object',
                    'properties' => ['url' => ['type' => 'string', 'format' => 'uri']],
                    'required' => ['url'],
                    'additionalProperties' => false,
                ],
                fn($input = []): bool => $this->access->canPreviewDocument(
                    (int) (((array) $input)['post_id'] ?? 0)
                ),
                fn($input = []): mixed => $this->guarded(fn(): mixed => $this->previewUrl((array) $input))
            ),
            'modulix/readiness' => $this->readDefinition(
                'Run Modulix readiness check',
                'Returns uncovered Elementor Pro usage and active theme or plugin compatibility results.',
                ['type' => 'object', 'additionalProperties' => false],
                [
                    'type' => 'object',
                    'properties' => [
                        'ready' => ['type' => 'boolean'],
                        'missing' => ['type' => 'array', 'items' => ['type' => 'object']],
                        'ecosystem' => ['type' => 'object', 'additionalProperties' => true],
                    ],
                    'required' => ['ready', 'missing', 'ecosystem'],
                ],
                fn($input = []): bool => $this->access->canManageOptions(),
                fn($input = []): mixed => $this->guarded(fn(): mixed => $this->readiness((array) $input))
            ),
        ];
    }

    private function setConditions(array $input): mixed {
        $postId = (int) ($input['post_id'] ?? 0);
        if (!$this->access->canManageOptions() || !$this->access->canEditDocument($postId)) {
            return $this->error('forbidden', 'The current user cannot manage Theme Builder conditions.');
        }
        $raw = $input['conditions'] ?? null;
        $batchSize = is_array($raw) ? count($raw) : 0;
        $limitError = $this->limits->checkWrite($this->access->userId(), $input, $batchSize);
        if ($limitError !== null) {
            return $this->limitError($limitError, false);
        }
        if ($this->isDryRun($input)) {
            return $this->prepareConditions($postId, $raw, true);
        }
        return $this->withLockedDocument($postId, function (array $snapshot) use ($postId, $raw, $input): mixed {
            $prepared = $this->prepareConditions($postId, $raw, false);
            if (isset($prepared['error']) || $prepared['validation_errors'] !== [] || $prepared['diff']['changed'] === false) {
                return $prepared;
            }
            $before = $prepared['diff']['before'];
            $after = $prepared['conditions'];
            return $this->persistLocked(
                'modulix/set-conditions',
                $postId,
                $input,
                $snapshot,
                fn(): bool => $this->source->saveConditions($postId, $after),
                fn(): bool => $this->source->conditions($postId) === $after,
                fn(): bool => $this->source->conditions($postId) === $before,
                fn(int $revisionId): array => $this->conditionsResponse(false, true, $postId, $revisionId, $after, $before),
                ['updated' => count($after)]
            );
        });
    }

    private function prepareConditions(int $postId, mixed $raw, bool $dryRun): array {
        $document = $this->source->getDocument($postId);
        $before = $this->source->conditions($postId);
        if ($document === null || $document->postType() !== 'elementor_library' || $before === null) {
            return $this->error('template_not_found', 'A Theme Builder template was not found.');
        }
        $validation = $this->validateConditions($raw, $this->source->conditionRegistry());
        if ($validation['errors'] !== []) {
            return $this->conditionsResponse($dryRun, false, $postId, 0, $validation['conditions'], $before, $validation['errors']);
        }
        return $this->conditionsResponse($dryRun, false, $postId, 0, $validation['conditions'], $before);
    }

    private function validateConditions(mixed $raw, array $registry): array {
        if (!is_array($raw)) {
            return [
                'conditions' => [],
                'errors' => [$this->validationError('wrong_type', 'conditions', 'Conditions must be an array.')],
            ];
        }
        $allowed = array_fill_keys($this->conditionDescendants($registry, 'general', true), true);
        $conditions = [];
        $errors = [];
        foreach ($raw as $index => $condition) {
            $path = 'conditions[' . $index . ']';
            if (!is_array($condition)) {
                $errors[] = $this->validationError('wrong_type', $path, 'Each condition must be an object.');
                continue;
            }
            $entryErrors = [];
            foreach (array_keys($condition) as $key) {
                if (!is_string($key) || !in_array($key, ['type', 'name', 'sub_name', 'sub_id'], true)) {
                    $entryErrors[] = $this->validationError('unknown_condition_key', $path . '.' . (string) $key, 'Unknown condition field.');
                }
            }
            $type = $condition['type'] ?? null;
            $name = $condition['name'] ?? null;
            $subName = $condition['sub_name'] ?? '';
            $subId = $condition['sub_id'] ?? '';
            if (!is_string($type) || !in_array($type, ['include', 'exclude'], true)) {
                $entryErrors[] = $this->validationError('invalid_condition_type', $path . '.type', 'Condition type must be include or exclude.');
            }
            if (!is_string($name) || $name === '' || !isset($allowed[$name])) {
                $entryErrors[] = $this->validationError('unknown_condition', $path . '.name', 'Condition name is not registered.');
            }
            if (!is_string($subName) || ($subName !== '' && !isset($allowed[$subName]))) {
                $entryErrors[] = $this->validationError('unknown_condition', $path . '.sub_name', 'Condition sub-name is not registered.');
            }
            if ((!is_string($subId) && !is_int($subId))
                || strlen((string) $subId) > 191
                || !preg_match('/^[A-Za-z0-9_-]*$/', (string) $subId)) {
                $entryErrors[] = $this->validationError('invalid_condition_value', $path . '.sub_id', 'Condition value contains unsupported characters.');
            }
            if (!is_string($type) || !is_string($name) || !is_string($subName)
                || (!is_string($subId) && !is_int($subId))) {
                $errors = array_merge($errors, $entryErrors);
                continue;
            }
            if ($subName === '' && (string) $subId !== '') {
                $entryErrors[] = $this->validationError(
                    'invalid_condition_chain',
                    $path . '.sub_id',
                    'Condition target ids require a sub-name.'
                );
            }
            if (isset($allowed[$name]) && $subName !== '' && isset($allowed[$subName])) {
                $children = $registry[$name]['sub_conditions'] ?? [];
                $directChildren = is_array($children)
                    ? array_fill_keys(array_values(array_filter($children, static fn(mixed $child): bool =>
                        is_string($child) && $child !== '' && isset($registry[$child])
                    )), true)
                    : [];
                if ($name === 'general' || !isset($directChildren[$subName])) {
                    $entryErrors[] = $this->validationError(
                        'invalid_condition_chain',
                        $path . '.sub_name',
                        'Condition sub-name does not belong to the selected condition.'
                    );
                }
            }
            $leaf = $subName !== '' ? $subName : $name;
            if ($entryErrors === [] && isset($registry[$leaf]) && is_array($registry[$leaf])) {
                $entryErrors = array_merge(
                    $entryErrors,
                    $this->conditionTargetErrors($registry[$leaf], (string) $subId, $path . '.sub_id')
                );
            }
            if ($entryErrors !== []) {
                $errors = array_merge($errors, $entryErrors);
                continue;
            }
            $normalized = rtrim(implode('/', [$type, $name, $subName, (string) $subId]), '/');
            if (in_array($normalized, $conditions, true)) {
                $errors[] = $this->validationError('duplicate_condition', $path, 'Conditions must be unique.');
                continue;
            }
            $conditions[] = $normalized;
        }
        return ['conditions' => $conditions, 'errors' => $errors];
    }

    private function conditionDescendants(array $registry, string $name, bool $includeSelf = false): array {
        $found = $includeSelf && isset($registry[$name]) ? [$name => true] : [];
        $stack = [$name];
        $seen = [];
        while ($stack !== [] && count($seen) < 1000) {
            $parent = array_pop($stack);
            if (isset($seen[$parent])) {
                continue;
            }
            $seen[$parent] = true;
            $children = $registry[$parent]['sub_conditions'] ?? [];
            if (!is_array($children)) {
                continue;
            }
            foreach ($children as $child) {
                if (!is_string($child) || $child === '' || !isset($registry[$child])) {
                    continue;
                }
                $found[$child] = true;
                $stack[] = $child;
            }
        }
        return array_keys($found);
    }

    private function conditionTargetErrors(array $condition, string $subId, string $path): array {
        $targetConfig = $this->conditionTargetConfig($condition);
        if ($subId === '') {
            return [];
        }
        if ($targetConfig === null) {
            return [$this->validationError('invalid_condition_target', $path, 'This condition does not accept a target id.')];
        }
        if (!preg_match('/^[1-9][0-9]*$/', $subId)
            || filter_var($subId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            return [$this->validationError('invalid_condition_target', $path, 'Condition target ids must be positive integers.')];
        }
        $targetId = (int) $subId;
        $target = $this->source->conditionTarget($targetConfig['object'], $targetId);
        if ($target === null) {
            return [$this->validationError('condition_target_not_found', $path, 'The condition target was not found.')];
        }
        if (!$this->conditionTargetMatches($targetConfig, $target)) {
            return [$this->validationError('condition_target_mismatch', $path, 'The condition target does not match the selected condition.')];
        }
        if ($targetConfig['object'] === 'post' && !$this->access->canEditDocument($targetId)) {
            return [$this->validationError('condition_target_forbidden', $path, 'The current user cannot edit the condition target.')];
        }
        return [];
    }

    private function conditionTargetConfig(array $condition): ?array {
        $controls = $condition['controls'] ?? [];
        if (!is_array($controls)) {
            return null;
        }
        foreach ($controls as $control) {
            $autocomplete = is_array($control) ? ($control['autocomplete'] ?? null) : null;
            $object = is_array($autocomplete) ? ($autocomplete['object'] ?? null) : null;
            if (is_string($object) && in_array($object, ['post', 'tax', 'author'], true)) {
                return [
                    'object' => $object,
                    'query' => is_array($autocomplete['query'] ?? null) ? $autocomplete['query'] : [],
                ];
            }
        }
        return null;
    }

    private function conditionTargetMatches(array $config, array $target): bool {
        if (($target['object'] ?? null) !== $config['object']) {
            return false;
        }
        $query = $config['query'];
        if ($config['object'] === 'post' && isset($query['post_type'])) {
            $allowed = is_array($query['post_type']) ? $query['post_type'] : [$query['post_type']];
            return is_string($target['post_type'] ?? null)
                && in_array($target['post_type'], array_values(array_filter($allowed, 'is_string')), true);
        }
        if ($config['object'] === 'tax' && isset($query['taxonomy'])) {
            $allowed = is_array($query['taxonomy']) ? $query['taxonomy'] : [$query['taxonomy']];
            return is_string($target['taxonomy'] ?? null)
                && in_array($target['taxonomy'], array_values(array_filter($allowed, 'is_string')), true);
        }
        return true;
    }

    private function updateKit(array $input): mixed {
        if (!$this->access->canManageOptions()) {
            return $this->error('forbidden', 'The current user cannot manage the Elementor kit.');
        }
        $raw = $input['settings'] ?? null;
        $batchSize = is_array($raw) ? $this->kitBatchSize($raw) : 0;
        $limitError = $this->limits->checkWrite($this->access->userId(), $input, $batchSize);
        if ($limitError !== null) {
            return $this->limitError($limitError, false);
        }
        $kit = $this->source->activeKit();
        if ($kit === null || $kit->id() <= 0) {
            return $this->error('kit_not_found', 'The active Elementor kit was not found.');
        }
        $kitId = $kit->id();
        if ($this->isDryRun($input)) {
            return $this->prepareKit($kit, $raw, true);
        }
        return $this->withLockedDocument($kitId, function (array $snapshot) use ($kitId, $raw, $input): mixed {
            $kit = $this->source->activeKit();
            if ($kit === null || $kit->id() !== $kitId) {
                return $this->error('document_changed', 'The active Elementor kit changed before the write.');
            }
            $prepared = $this->prepareKit($kit, $raw, false);
            if (isset($prepared['error']) || $prepared['validation_errors'] !== [] || $prepared['diff']['changed'] === false) {
                return $prepared;
            }
            $before = $prepared['diff']['before'];
            $after = $prepared['settings'];
            $patch = $prepared['patch'];
            return $this->persistLocked(
                'modulix/update-kit',
                $kitId,
                $input,
                $snapshot,
                fn(): bool => $kit->save(['settings' => $after]),
                fn(): bool => $this->kitSettingsMatch($kitId, $patch),
                fn(): bool => $this->kitFullSettingsMatch($kitId, $before),
                fn(int $revisionId): array => $this->kitResponse(false, true, $kitId, $revisionId, $after, $before, $patch),
                ['page_settings' => count($patch)]
            );
        });
    }

    private function prepareKit(WritableDocument $kit, mixed $raw, bool $dryRun): array {
        $before = $kit->pageSettings();
        $validation = $this->validateKit($raw, $kit->pageSettingsControls());
        $after = array_replace($before, $validation['settings']);
        return $this->kitResponse(
            $dryRun,
            false,
            $kit->id(),
            0,
            $after,
            $before,
            $validation['settings'],
            $validation['errors']
        );
    }

    private function validateKit(mixed $raw, array $controls): array {
        if (!is_array($raw)) {
            return [
                'settings' => [],
                'errors' => [$this->validationError('wrong_type', 'settings', 'Kit settings must be an object.')],
            ];
        }
        $clean = [];
        $errors = [];
        foreach ($raw as $key => $value) {
            if (!is_string($key) || !in_array($key, self::KIT_SETTINGS, true)) {
                $errors[] = $this->validationError('unknown_kit_setting', 'settings.' . (string) $key, 'Only global colors and fonts may be changed.');
                continue;
            }
            $clean[$key] = $value;
        }
        if ($clean === [] && $errors === []) {
            $errors[] = $this->validationError('empty_patch', 'settings', 'At least one kit setting is required.');
        }
        $validated = $this->validator->validateSettingsPatch($clean, $controls, 'settings');
        $errors = array_merge($errors, $validated['errors']);
        $errors = array_merge($errors, $this->kitValueErrors($validated['settings']));
        $errors = array_merge($errors, $this->duplicateKitIdErrors($validated['settings']));
        return ['settings' => $validated['settings'], 'errors' => $errors];
    }

    private function kitValueErrors(array $settings): array {
        $errors = [];
        $seen = 0;
        $stack = [[$settings, 'settings', '', 0]];
        while ($stack !== []) {
            [$value, $path, $key, $depth] = array_pop($stack);
            if (++$seen > 10000) {
                $errors[] = $this->validationError('kit_too_complex', $path, 'Kit settings exceed the value limit.');
                break;
            }
            if (is_array($value)) {
                if ($depth >= 40) {
                    $errors[] = $this->validationError('kit_too_deep', $path, 'Kit settings exceed the nesting limit.');
                    continue;
                }
                foreach ($value as $nestedKey => $nested) {
                    $nestedPath = $path . (is_int($nestedKey) ? '[' . $nestedKey . ']' : '.' . $nestedKey);
                    $stack[] = [$nested, $nestedPath, (string) $nestedKey, $depth + 1];
                }
                continue;
            }
            if (!is_string($value)) {
                if ($key === 'color' || $key === '_id' || str_ends_with($key, 'font_family')) {
                    $errors[] = $this->validationError('wrong_type', $path, 'Global color and font values must be strings.');
                }
                continue;
            }
            if (strlen($value) > 500
                || preg_match('/[<>{};\x00-\x1F\x7F]/', $value)
                || preg_match('/(?:url|expression)\s*\(|javascript\s*:/i', $value)) {
                $errors[] = $this->validationError('unsafe_kit_value', $path, 'Kit values may not contain HTML, CSS declarations, URLs, or control characters.');
                continue;
            }
            if ($key === 'color' && $value !== 'transparent'
                && !preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value)) {
                $errors[] = $this->validationError('invalid_color', $path, 'Global colors must be hexadecimal colors or transparent.');
            }
            if ($key === '_id' && !preg_match('/^[A-Za-z0-9_-]{1,100}$/', $value)) {
                $errors[] = $this->validationError('invalid_kit_id', $path, 'Global color and font ids must use letters, digits, underscores, or hyphens.');
            }
            if (str_ends_with($key, 'font_family')
                && !preg_match("/^[\\pL\\pN _,'\".-]{1,200}$/u", $value)) {
                $errors[] = $this->validationError('invalid_font_family', $path, 'Font families contain unsupported characters.');
            }
        }
        return $errors;
    }

    private function duplicateKitIdErrors(array $settings): array {
        $errors = [];
        foreach (self::KIT_SETTINGS as $setting) {
            if (!isset($settings[$setting]) || !is_array($settings[$setting])) {
                continue;
            }
            $ids = [];
            foreach ($settings[$setting] as $index => $row) {
                if (!is_array($row) || !isset($row['_id']) || !is_string($row['_id'])) {
                    continue;
                }
                if (isset($ids[$row['_id']])) {
                    $errors[] = $this->validationError(
                        'duplicate_kit_id',
                        'settings.' . $setting . '[' . $index . ']._id',
                        'Global color and font ids must be unique within each collection.'
                    );
                }
                $ids[$row['_id']] = true;
            }
        }
        return $errors;
    }

    private function publish(array $input): mixed {
        $postId = (int) ($input['post_id'] ?? 0);
        $status = $input['status'] ?? null;
        $statusErrors = [];
        if (!is_string($status) || !in_array($status, ['publish', 'draft'], true)) {
            $statusErrors[] = $this->validationError('invalid_status', 'status', 'Status must be publish or draft.');
        }
        if ($statusErrors !== []) {
            return $this->publishResponse($this->isDryRun($input), false, $postId, 0, is_string($status) ? $status : '', '', $statusErrors);
        }
        if (!$this->access->canPublishDocument($postId, $status)) {
            return $this->error('forbidden', 'The current user cannot change this document status.');
        }
        $limitError = $this->limits->checkWrite($this->access->userId(), $input, 0);
        if ($limitError !== null) {
            return $this->limitError($limitError, false);
        }
        if ($this->isDryRun($input)) {
            return $this->preparePublish($postId, $status, true);
        }
        return $this->withLockedDocument($postId, function (array $snapshot) use ($postId, $status, $input): mixed {
            $prepared = $this->preparePublish($postId, $status, false);
            if (isset($prepared['error']) || $prepared['validation_errors'] !== [] || $prepared['diff']['changed'] === false) {
                return $prepared;
            }
            $before = $prepared['diff']['before'];
            return $this->persistLocked(
                'modulix/publish',
                $postId,
                $input,
                $snapshot,
                fn(): bool => $this->source->saveStatus($postId, $status),
                fn(): bool => $this->source->status($postId) === $status,
                fn(): bool => $this->source->status($postId) === $before,
                fn(int $revisionId): array => $this->publishResponse(false, true, $postId, $revisionId, $status, $before)
            );
        });
    }

    private function preparePublish(int $postId, string $status, bool $dryRun): array {
        $document = $this->source->getDocument($postId);
        $before = $this->source->status($postId);
        if ($document === null || $before === null) {
            return $this->error('document_not_found', 'The Elementor document was not found.');
        }
        return $this->publishResponse($dryRun, false, $postId, 0, $status, $before);
    }

    private function previewUrl(array $input): mixed {
        $postId = (int) ($input['post_id'] ?? 0);
        if (!$this->access->canPreviewDocument($postId)) {
            return $this->error('forbidden', 'The current user cannot preview this document.');
        }
        $limitError = $this->limits->checkRead($this->access->userId(), $input);
        if ($limitError !== null) {
            return $this->limitError($limitError, true);
        }
        if ($this->source->getDocument($postId) === null) {
            return $this->error('document_not_found', 'The Elementor document was not found.');
        }
        $url = $this->source->previewUrl($postId);
        if (!is_string($url) || $url === '') {
            return $this->error('preview_unavailable', 'A preview URL could not be created.');
        }
        $url = $this->stripPreviewNonce($url);
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return $this->error('preview_unavailable', 'A valid preview URL could not be created.');
        }
        return ['url' => $url];
    }

    private function readiness(array $input): mixed {
        if (!$this->access->canManageOptions()) {
            return $this->error('forbidden', 'The current user cannot run the readiness check.');
        }
        $limitError = $this->limits->checkRead($this->access->userId(), $input);
        if ($limitError !== null) {
            return $this->limitError($limitError, true);
        }
        return $this->source->readiness();
    }

    private function withLockedDocument(int $documentId, callable $callback): mixed {
        try {
            if (!$this->source->lockDocument($documentId)) {
                return $this->error('document_locked', 'The Elementor document is locked by another editor.');
            }
        } catch (\Throwable) {
            return $this->error('document_locked', 'The Elementor document lock could not be acquired.');
        }
        try {
            $snapshot = $this->source->snapshotDocument($documentId);
            if ($snapshot === null) {
                return $this->error('snapshot_failed', 'The pre-write document state could not be captured.');
            }
            return $callback($snapshot);
        } finally {
            $this->source->unlockDocument($documentId);
        }
    }

    private function persistLocked(
        string $ability,
        int $documentId,
        array $input,
        array $snapshot,
        callable $mutate,
        callable $verifyAfter,
        callable $verifyBefore,
        callable $success,
        array $counts = []
    ): mixed {
        $encoded = json_encode($input);
        $size = $encoded === false ? 0 : strlen($encoded);
        try {
            $logId = $this->logger->begin($this->access->userId(), $ability, $documentId, $size, 0, $counts);
        } catch (\Throwable) {
            $logId = 0;
        }
        if ($logId <= 0) {
            return $this->error('change_log_failed', 'The pending MCP change log row could not be recorded.');
        }
        $revisionId = 0;
        $mutationStarted = false;
        try {
            $revisionId = $this->source->createRevision($documentId);
            if ($revisionId <= 0) {
                return $this->preSaveFailure('revision_failed', 'A WordPress revision could not be created before saving.', $logId, 0);
            }
            $current = $this->source->snapshotDocument($documentId);
            if ($current === null || !$this->snapshotsMatch($snapshot, $current)) {
                return $this->preSaveFailure('document_changed', 'The document changed before it could be saved.', $logId, $revisionId);
            }
            $mutationStarted = true;
            if (!$mutate() || !$verifyAfter()) {
                return $this->rollbackFailure(
                    'save_failed',
                    'The Elementor change could not be saved and verified.',
                    $documentId,
                    $revisionId,
                    $logId,
                    $snapshot,
                    $verifyBefore
                );
            }
            if (!$this->logger->complete($logId, $revisionId)) {
                return $this->rollbackFailure(
                    'change_log_failed',
                    'The saved change could not be completed in the MCP change log.',
                    $documentId,
                    $revisionId,
                    $logId,
                    $snapshot,
                    $verifyBefore
                );
            }
            return $success($revisionId);
        } catch (\Throwable) {
            if (!$mutationStarted) {
                return $this->preSaveFailure('write_failed', 'The write failed before the document was changed.', $logId, $revisionId);
            }
            return $this->rollbackFailure(
                'write_failed',
                'The Elementor change failed and was rolled back.',
                $documentId,
                $revisionId,
                $logId,
                $snapshot,
                $verifyBefore
            );
        }
    }

    private function preSaveFailure(string $code, string $message, int $logId, int $revisionId): mixed {
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
        return $this->error($code, $message, $revisionId > 0 ? ['revision_id' => $revisionId] : []);
    }

    private function rollbackFailure(
        string $code,
        string $message,
        int $documentId,
        int $revisionId,
        int $logId,
        array $snapshot,
        callable $verifyBefore
    ): mixed {
        try {
            $restored = $revisionId > 0
                && $this->source->restoreRevision($documentId, $revisionId, $snapshot);
            $restoredSnapshot = $restored ? $this->source->snapshotDocument($documentId) : null;
            $restored = $restored
                && $restoredSnapshot !== null
                && $this->snapshotsMatch($snapshot, $restoredSnapshot)
                && $verifyBefore();
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

    private function snapshotsMatch(array $expected, array $actual): bool {
        if (isset($expected['hashes'], $actual['hashes'])
            && is_array($expected['hashes'])
            && is_array($actual['hashes'])) {
            return $expected['hashes'] === $actual['hashes'];
        }
        return $expected === $actual;
    }

    private function guarded(callable $callback): mixed {
        try {
            return $callback();
        } catch (\Throwable) {
            return $this->error('operation_failed', 'The MCP operation could not be completed safely.');
        }
    }

    private function kitSettingsMatch(int $kitId, array $patch): bool {
        $kit = $this->source->activeKit();
        if ($kit === null || $kit->id() !== $kitId) {
            return false;
        }
        $saved = $kit->pageSettings();
        foreach ($patch as $key => $value) {
            if (!array_key_exists($key, $saved) || $saved[$key] !== $value) {
                return false;
            }
        }
        return true;
    }

    private function kitFullSettingsMatch(int $kitId, array $before): bool {
        $kit = $this->source->activeKit();
        return $kit !== null && $kit->id() === $kitId && $kit->pageSettings() === $before;
    }

    private function conditionsResponse(
        bool $dryRun,
        bool $saved,
        int $documentId,
        int $revisionId,
        array $conditions,
        array $before,
        array $errors = []
    ): array {
        return [
            'dry_run' => $dryRun,
            'saved' => $saved,
            'document_id' => $documentId,
            'revision_id' => $revisionId,
            'conditions' => $conditions,
            'diff' => [
                'changed' => $before !== $conditions,
                'before' => $before,
                'after' => $conditions,
                'added' => array_values(array_diff($conditions, $before)),
                'removed' => array_values(array_diff($before, $conditions)),
            ],
            'validation_errors' => $errors,
        ];
    }

    private function kitResponse(
        bool $dryRun,
        bool $saved,
        int $documentId,
        int $revisionId,
        array $settings,
        array $before,
        array $patch,
        array $errors = []
    ): array {
        $changedKeys = [];
        foreach ($patch as $key => $value) {
            if (!array_key_exists($key, $before) || $before[$key] !== $value) {
                $changedKeys[] = $key;
            }
        }
        return [
            'dry_run' => $dryRun,
            'saved' => $saved,
            'document_id' => $documentId,
            'revision_id' => $revisionId,
            'settings' => $settings,
            'patch' => $patch,
            'diff' => [
                'changed' => $changedKeys !== [],
                'changed_keys' => $changedKeys,
                'before' => $before,
            ],
            'validation_errors' => $errors,
        ];
    }

    private function publishResponse(
        bool $dryRun,
        bool $saved,
        int $documentId,
        int $revisionId,
        string $status,
        string $before,
        array $errors = []
    ): array {
        return [
            'dry_run' => $dryRun,
            'saved' => $saved,
            'document_id' => $documentId,
            'revision_id' => $revisionId,
            'status' => $status,
            'diff' => ['changed' => $before !== $status, 'before' => $before, 'after' => $status],
            'validation_errors' => $errors,
        ];
    }

    private function writeDefinition(
        string $label,
        string $description,
        array $inputSchema,
        array $outputSchema,
        callable $permission,
        callable $execute
    ): array {
        return $this->definition($label, $description, $inputSchema, $outputSchema, $permission, $execute, false, true);
    }

    private function readDefinition(
        string $label,
        string $description,
        array $inputSchema,
        array $outputSchema,
        callable $permission,
        callable $execute
    ): array {
        return $this->definition($label, $description, $inputSchema, $outputSchema, $permission, $execute, true, false);
    }

    private function definition(
        string $label,
        string $description,
        array $inputSchema,
        array $outputSchema,
        callable $permission,
        callable $execute,
        bool $readonly,
        bool $destructive
    ): array {
        return [
            'label' => $label,
            'description' => $description,
            'category' => 'modulix',
            'input_schema' => $inputSchema,
            'output_schema' => $outputSchema,
            'permission_callback' => $permission,
            'execute_callback' => $execute,
            'meta' => [
                'show_in_rest' => false,
                'mcp' => ['public' => true],
                'annotations' => ['readonly' => $readonly, 'idempotent' => $readonly, 'destructive' => $destructive],
            ],
        ];
    }

    private function conditionsInputSchema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'post_id' => ['type' => 'integer', 'minimum' => 1],
                'conditions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => ['type' => 'string', 'enum' => ['include', 'exclude']],
                            'name' => ['type' => 'string', 'minLength' => 1],
                            'sub_name' => ['type' => 'string'],
                            'sub_id' => ['type' => ['string', 'integer']],
                        ],
                        'required' => ['type', 'name'],
                        'additionalProperties' => false,
                    ],
                ],
                'dry_run' => ['type' => 'boolean', 'default' => true],
            ],
            'required' => ['post_id', 'conditions'],
            'additionalProperties' => false,
        ];
    }

    private function kitInputSchema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'settings' => [
                    'type' => 'object',
                    'properties' => array_fill_keys(
                        self::KIT_SETTINGS,
                        ['type' => 'array', 'items' => ['type' => 'object']]
                    ),
                    'additionalProperties' => false,
                ],
                'dry_run' => ['type' => 'boolean', 'default' => true],
            ],
            'required' => ['settings'],
            'additionalProperties' => false,
        ];
    }

    private function publishInputSchema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'post_id' => ['type' => 'integer', 'minimum' => 1],
                'status' => ['type' => 'string', 'enum' => ['publish', 'draft']],
                'dry_run' => ['type' => 'boolean', 'default' => true],
            ],
            'required' => ['post_id', 'status'],
            'additionalProperties' => false,
        ];
    }

    private function documentInputSchema(): array {
        return [
            'type' => 'object',
            'properties' => ['post_id' => ['type' => 'integer', 'minimum' => 1]],
            'required' => ['post_id'],
            'additionalProperties' => false,
        ];
    }

    private function conditionsOutputSchema(): array {
        return $this->writeOutputSchema(['conditions' => ['type' => 'array', 'items' => ['type' => 'string']]]);
    }

    private function kitOutputSchema(): array {
        return $this->writeOutputSchema([
            'settings' => ['type' => 'object', 'additionalProperties' => true],
            'patch' => ['type' => 'object', 'additionalProperties' => true],
        ]);
    }

    private function publishOutputSchema(): array {
        return $this->writeOutputSchema(['status' => ['type' => 'string', 'enum' => ['publish', 'draft', '']]]);
    }

    private function writeOutputSchema(array $extra): array {
        $properties = [
            'dry_run' => ['type' => 'boolean'],
            'saved' => ['type' => 'boolean'],
            'document_id' => ['type' => 'integer'],
            'revision_id' => ['type' => 'integer'],
            'diff' => ['type' => 'object', 'additionalProperties' => true],
            'validation_errors' => ['type' => 'array', 'items' => ['type' => 'object']],
        ] + $extra;
        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => array_keys($properties),
        ];
    }

    private function isDryRun(array $input): bool {
        return !array_key_exists('dry_run', $input) || $input['dry_run'] !== false;
    }

    private function kitBatchSize(array $settings): int {
        $count = 0;
        foreach (self::KIT_SETTINGS as $key) {
            if (isset($settings[$key]) && is_array($settings[$key])) {
                $count += count($settings[$key]);
            }
        }
        return max(1, $count);
    }

    private function stripPreviewNonce(string $url): string {
        $url = preg_replace_callback(
            '/([?&])preview_nonce=[^&#]*(&?)/',
            static function (array $match): string {
                if ($match[1] === '?' && $match[2] === '&') {
                    return '?';
                }
                if ($match[1] === '&' && $match[2] === '&') {
                    return '&';
                }
                return '';
            },
            $url
        ) ?? '';
        return rtrim(str_replace(['?&', '&&'], ['?', '&'], $url), '?&');
    }

    private function limitError(string $code, bool $read): mixed {
        return $this->error($code, $read ? 'The MCP read limit was exceeded.' : 'The MCP write limit was exceeded.');
    }

    private function validationError(string $code, string $path, string $message): array {
        return ['code' => $code, 'path' => $path, 'message' => $message];
    }

    private function error(string $code, string $message, array $data = []): mixed {
        if (class_exists('WP_Error')) {
            return new \WP_Error($code, $message, $data);
        }
        return ['error' => ['code' => $code, 'message' => $message, 'data' => $data]];
    }
}
