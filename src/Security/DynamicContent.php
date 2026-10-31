<?php
namespace Modulix\Security;

/** Access and value rules for dynamic content rendered from stored Elementor settings. */
final class DynamicContent {
    /** @param mixed $value */
    public static function scalar_text($value): string {
        return is_scalar($value) ? (string) $value : '';
    }

    public static function user_meta_allowed(string $key, bool $isProtected): bool {
        return $key !== '' && !$isProtected;
    }

    public static function stored_shortcode_allowed(?object $post, bool $canEdit): bool {
        if ($post === null || !in_array($post->post_status ?? '', ['publish', 'private', 'draft', 'pending', 'future'], true)) {
            return false;
        }

        return $canEdit || (($post->post_status ?? '') === 'publish' && empty($post->post_password));
    }

    public static function template_allowed(?object $post, string $postType, bool $canRead, bool $canEdit): bool {
        if ($post === null || ($post->post_type ?? '') !== $postType || !empty($post->post_password)) {
            return false;
        }

        $status = $post->post_status ?? '';
        if ($status === 'publish') {
            return true;
        }
        if ($status === 'private') {
            return $canRead;
        }

        return in_array($status, ['draft', 'pending', 'future'], true) && $canEdit;
    }

    public static function linked_post_allowed(?object $post, bool $canRead, ?object $parent = null): bool {
        if ($post === null || in_array($post->post_status ?? '', ['', 'trash', 'auto-draft'], true)) {
            return false;
        }

        if (($post->post_status ?? '') === 'publish') {
            return true;
        }

        if (($post->post_status ?? '') === 'inherit') {
            return $parent === null || ($parent->post_status ?? '') === 'publish' || $canRead;
        }

        return $canRead;
    }

    public static function lightbox_attachment_allowed(
        ?object $attachment,
        bool $canReadAttachment,
        ?object $parent,
        bool $canReadParent
    ): bool {
        if (
            $attachment === null
            || ($attachment->post_type ?? '') !== 'attachment'
            || !empty($attachment->post_password)
            || in_array($attachment->post_status ?? '', ['', 'trash', 'auto-draft'], true)
        ) {
            return false;
        }

        $attachmentIsPublic = in_array($attachment->post_status ?? '', ['publish', 'inherit'], true);
        $parentIsPublic = $parent === null
            || (($parent->post_status ?? '') === 'publish' && empty($parent->post_password));

        if ($attachmentIsPublic && $parentIsPublic) {
            return true;
        }

        if ($parent !== null && in_array($parent->post_status ?? '', ['', 'trash', 'auto-draft'], true)) {
            return false;
        }

        return $canReadAttachment && ($parent === null || $canReadParent);
    }

    public static function author_link_allowed(?object $user, bool $canListUsers, bool $hasPublishedPosts): bool {
        return $user !== null && ($canListUsers || $hasPublishedPosts);
    }
}
