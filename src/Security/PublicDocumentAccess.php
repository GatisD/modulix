<?php
namespace Modulix\Security;

final class PublicDocumentAccess {
    private const EDITABLE_STATUSES = ['publish', 'draft', 'private', 'future'];

    public static function allowed(?object $post, bool $canEdit): bool {
        if ($post === null) {
            return false;
        }

        $status = (string) ($post->post_status ?? '');
        if ($canEdit) {
            return in_array($status, self::EDITABLE_STATUSES, true);
        }

        return $status === 'publish' && (string) ($post->post_password ?? '') === '';
    }

    /** @param mixed[] $values @return int[] */
    public static function normalizeIds(array $values, int $limit): array {
        if ($limit < 1) {
            return [];
        }

        $ids = [];
        foreach ($values as $value) {
            if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
                continue;
            }
            $id = (int) $value;
            if ($id < 1 || isset($ids[$id])) {
                continue;
            }
            $ids[$id] = $id;
            if (count($ids) === $limit) {
                break;
            }
        }

        return array_values($ids);
    }
}
