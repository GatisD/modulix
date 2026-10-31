<?php
namespace Modulix\Mcp;

final class ElementTreeOperations {
    public const DOCUMENT_ROOT = 'document';

    public function apply(array $elements, array $operations): array {
        $original = $elements;
        $tree = $elements;

        foreach (array_values($operations) as $index => $operation) {
            if (!is_array($operation)) {
                return $this->failure($original, $index, 'invalid_operation', "operations[$index]", 'Operation must be an object.');
            }
            $action = $operation['action'] ?? null;
            if (!is_string($action) || !in_array($action, ['insert', 'update', 'move', 'delete'], true)) {
                return $this->failure($original, $index, 'invalid_operation', "operations[$index].action", 'Unknown operation action.');
            }

            $error = match ($action) {
                'insert' => $this->insert($tree, $operation, $index),
                'update' => $this->update($tree, $operation, $index),
                'move' => $this->move($tree, $operation, $index),
                'delete' => $this->delete($tree, $operation, $index),
            };
            if ($error !== null) {
                return ['valid' => false, 'errors' => [$error], 'elements' => $original];
            }
        }

        return ['valid' => true, 'errors' => [], 'elements' => $tree];
    }

    private function insert(array &$tree, array $operation, int $index): ?array {
        $parentId = $operation['parent_id'] ?? null;
        $position = $operation['index'] ?? null;
        $element = $operation['element'] ?? null;
        if (!is_string($parentId) || $parentId === '') {
            return $this->error($index, 'invalid_parent_id', "operations[$index].parent_id", 'Parent id must be a non-empty string.');
        }
        if (!is_int($position) || $position < 0) {
            return $this->error($index, 'invalid_index', "operations[$index].index", 'Insert index must be a non-negative integer.');
        }
        if (!is_array($element)) {
            return $this->error($index, 'invalid_element', "operations[$index].element", 'Inserted element must be an object.');
        }
        $idError = $this->validateInsertedIds($tree, $element, $index);
        if ($idError !== null) {
            return $idError;
        }

        if ($parentId === self::DOCUMENT_ROOT) {
            if ($position > count($tree)) {
                return $this->error($index, 'invalid_index', "operations[$index].index", 'Insert index is outside the parent.');
            }
            array_splice($tree, $position, 0, [$element]);
            return null;
        }

        $found = false;
        $validIndex = true;
        $this->insertInto($tree, $parentId, $position, $element, $found, $validIndex);
        if (!$found) {
            return $this->error($index, 'unknown_parent_id', "operations[$index].parent_id", 'Parent element id was not found.');
        }
        if (!$validIndex) {
            return $this->error($index, 'invalid_index', "operations[$index].index", 'Insert index is outside the parent.');
        }
        return null;
    }

    private function update(array &$tree, array $operation, int $index): ?array {
        $elementId = $operation['element_id'] ?? null;
        $settings = $operation['settings'] ?? null;
        if (!is_string($elementId) || $elementId === '') {
            return $this->error($index, 'invalid_element_id', "operations[$index].element_id", 'Element id must be a non-empty string.');
        }
        if (!is_array($settings)) {
            return $this->error($index, 'invalid_settings', "operations[$index].settings", 'Settings must be an object.');
        }
        $found = false;
        $this->updateIn($tree, $elementId, $settings, $found);
        return $found
            ? null
            : $this->error($index, 'unknown_element_id', "operations[$index].element_id", 'Element id was not found.');
    }

    private function move(array &$tree, array $operation, int $index): ?array {
        $elementId = $operation['element_id'] ?? null;
        $parentId = $operation['parent_id'] ?? null;
        $position = $operation['index'] ?? null;
        if (!is_string($elementId) || $elementId === '') {
            return $this->error($index, 'invalid_element_id', "operations[$index].element_id", 'Element id must be a non-empty string.');
        }
        if (!is_string($parentId) || $parentId === '') {
            return $this->error($index, 'invalid_parent_id', "operations[$index].parent_id", 'Parent id must be a non-empty string.');
        }
        if (!is_int($position) || $position < 0) {
            return $this->error($index, 'invalid_index', "operations[$index].index", 'Move index must be a non-negative integer.');
        }

        $node = $this->find($tree, $elementId);
        if ($node === null) {
            return $this->error($index, 'unknown_element_id', "operations[$index].element_id", 'Element id was not found.');
        }
        if ($parentId !== self::DOCUMENT_ROOT && $this->find($tree, $parentId) === null) {
            return $this->error($index, 'unknown_parent_id', "operations[$index].parent_id", 'Parent element id was not found.');
        }
        if ($parentId === $elementId || $this->find($node['elements'] ?? [], $parentId) !== null) {
            return $this->error($index, 'invalid_move_target', "operations[$index].parent_id", 'An element cannot be moved into itself or its descendants.');
        }

        $removed = false;
        $this->deleteFrom($tree, $elementId, $removed);
        if (!$removed) {
            return $this->error($index, 'unknown_element_id', "operations[$index].element_id", 'Element id was not found.');
        }
        if ($parentId === self::DOCUMENT_ROOT) {
            if ($position > count($tree)) {
                return $this->error($index, 'invalid_index', "operations[$index].index", 'Move index is outside the parent.');
            }
            array_splice($tree, $position, 0, [$node]);
            return null;
        }
        $found = false;
        $validIndex = true;
        $this->insertInto($tree, $parentId, $position, $node, $found, $validIndex);
        if (!$found) {
            return $this->error($index, 'unknown_parent_id', "operations[$index].parent_id", 'Parent element id was not found.');
        }
        if (!$validIndex) {
            return $this->error($index, 'invalid_index', "operations[$index].index", 'Move index is outside the parent.');
        }
        return null;
    }

    private function delete(array &$tree, array $operation, int $index): ?array {
        $elementId = $operation['element_id'] ?? null;
        if (!is_string($elementId) || $elementId === '') {
            return $this->error($index, 'invalid_element_id', "operations[$index].element_id", 'Element id must be a non-empty string.');
        }
        $removed = false;
        $this->deleteFrom($tree, $elementId, $removed);
        return $removed
            ? null
            : $this->error($index, 'unknown_element_id', "operations[$index].element_id", 'Element id was not found.');
    }

    private function validateInsertedIds(array $tree, array $element, int $index): ?array {
        $existing = [];
        $this->collectIds($tree, $existing);
        $inserted = [];
        $stack = [$element];
        while ($stack !== []) {
            $node = array_pop($stack);
            $id = $node['id'] ?? null;
            if (!is_string($id) || $id === '') {
                return $this->error($index, 'invalid_element_id', "operations[$index].element.id", 'Every inserted element must have a non-empty string id.');
            }
            if (isset($existing[$id]) || isset($inserted[$id])) {
                return $this->error($index, 'duplicate_element_id', "operations[$index].element.id", 'Inserted element ids must be unique.');
            }
            $inserted[$id] = true;
            foreach (($node['elements'] ?? []) as $child) {
                if (is_array($child)) {
                    $stack[] = $child;
                }
            }
        }
        return null;
    }

    private function collectIds(array $tree, array &$ids): void {
        $stack = $tree;
        while ($stack !== []) {
            $node = array_pop($stack);
            if (!is_array($node)) {
                continue;
            }
            if (isset($node['id']) && is_string($node['id']) && $node['id'] !== '') {
                $ids[$node['id']] = true;
            }
            foreach (($node['elements'] ?? []) as $child) {
                $stack[] = $child;
            }
        }
    }

    private function find(array $tree, string $id): ?array {
        $stack = $tree;
        while ($stack !== []) {
            $node = array_pop($stack);
            if (!is_array($node)) {
                continue;
            }
            if (($node['id'] ?? null) === $id) {
                return $node;
            }
            foreach (($node['elements'] ?? []) as $child) {
                $stack[] = $child;
            }
        }
        return null;
    }

    private function insertInto(
        array &$tree,
        string $parentId,
        int $position,
        array $element,
        bool &$found,
        bool &$validIndex
    ): void {
        foreach ($tree as &$node) {
            if (!is_array($node)) {
                continue;
            }
            if (($node['id'] ?? null) === $parentId) {
                $found = true;
                $children = isset($node['elements']) && is_array($node['elements']) ? $node['elements'] : [];
                if ($position > count($children)) {
                    $validIndex = false;
                    return;
                }
                array_splice($children, $position, 0, [$element]);
                $node['elements'] = $children;
                return;
            }
            if (isset($node['elements']) && is_array($node['elements'])) {
                $this->insertInto($node['elements'], $parentId, $position, $element, $found, $validIndex);
                if ($found) {
                    return;
                }
            }
        }
        unset($node);
    }

    private function updateIn(array &$tree, string $elementId, array $settings, bool &$found): void {
        foreach ($tree as &$node) {
            if (!is_array($node)) {
                continue;
            }
            if (($node['id'] ?? null) === $elementId) {
                $current = isset($node['settings']) && is_array($node['settings']) ? $node['settings'] : [];
                $node['settings'] = array_replace($current, $settings);
                $found = true;
                return;
            }
            if (isset($node['elements']) && is_array($node['elements'])) {
                $this->updateIn($node['elements'], $elementId, $settings, $found);
                if ($found) {
                    return;
                }
            }
        }
        unset($node);
    }

    private function deleteFrom(array &$tree, string $elementId, bool &$removed): void {
        foreach ($tree as $position => &$node) {
            if (!is_array($node)) {
                continue;
            }
            if (($node['id'] ?? null) === $elementId) {
                array_splice($tree, (int) $position, 1);
                $removed = true;
                return;
            }
            if (isset($node['elements']) && is_array($node['elements'])) {
                $this->deleteFrom($node['elements'], $elementId, $removed);
                if ($removed) {
                    return;
                }
            }
        }
        unset($node);
    }

    private function failure(array $elements, int $index, string $code, string $path, string $message): array {
        return ['valid' => false, 'errors' => [$this->error($index, $code, $path, $message)], 'elements' => $elements];
    }

    private function error(int $index, string $code, string $path, string $message): array {
        return ['code' => $code, 'path' => $path, 'operation_index' => $index, 'message' => $message];
    }
}
