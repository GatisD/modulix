<?php
namespace Modulix\Mcp;

final class WordPressAccessGate implements AccessGate, M4AccessGate {
    public function canReadSite(): bool {
        return current_user_can('edit_posts');
    }

    public function canReadDocument(int $postId): bool {
        return $postId > 0
            && current_user_can('read_post', $postId)
            && current_user_can('edit_post', $postId);
    }

    public function canEditDocument(int $postId): bool {
        return $postId > 0 && current_user_can('edit_post', $postId);
    }

    public function canCreateDocument(string $postType): bool {
        if (!in_array($postType, ['page', 'post', 'elementor_library'], true)) {
            return false;
        }
        $postTypeObject = get_post_type_object($postType);
        if (!is_object($postTypeObject) || !isset($postTypeObject->cap) || !is_object($postTypeObject->cap)) {
            return false;
        }
        $capability = isset($postTypeObject->cap->create_posts)
            && is_string($postTypeObject->cap->create_posts)
            && $postTypeObject->cap->create_posts !== ''
                ? $postTypeObject->cap->create_posts
                : (isset($postTypeObject->cap->edit_posts) && is_string($postTypeObject->cap->edit_posts)
                    ? $postTypeObject->cap->edit_posts
                    : '');
        return $capability !== '' && current_user_can($capability);
    }

    public function canManageOptions(): bool {
        return current_user_can('manage_options');
    }

    public function canPublishDocument(int $postId, string $status): bool {
        if ($postId <= 0 || !in_array($status, ['publish', 'draft'], true)
            || !current_user_can('edit_post', $postId)) {
            return false;
        }
        if ($status === 'draft') {
            return true;
        }
        $postType = get_post_type($postId);
        if (!is_string($postType) || $postType === '') {
            return false;
        }
        $postTypeObject = get_post_type_object($postType);
        if (!is_object($postTypeObject) || !isset($postTypeObject->cap) || !is_object($postTypeObject->cap)
            || !isset($postTypeObject->cap->publish_posts) || !is_string($postTypeObject->cap->publish_posts)
            || $postTypeObject->cap->publish_posts === '') {
            return false;
        }
        return current_user_can($postTypeObject->cap->publish_posts);
    }

    public function canPreviewDocument(int $postId): bool {
        return $postId > 0 && current_user_can('edit_post', $postId);
    }

    public function userId(): int {
        return (int) get_current_user_id();
    }
}
