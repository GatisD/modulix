<?php
namespace Modulix;

/** Read-access rules for content that lifted widgets and tags print. */
final class ContentAccess {
    /**
     * ACF options-page values through dynamic tags: site managers anywhere; everyone else only when a published
     * document is rendered on the public site (not in the editor, a preview or a draft).
     */
    public static function acf_options_allowed(bool $can_manage, bool $editing_or_preview, bool $document_published): bool {
        return $can_manage || (!$editing_or_preview && $document_published);
    }

    /** @param mixed $field ACF field object (array) or false */
    public static function is_secret_acf_field($field): bool {
        return is_array($field) && ($field['type'] ?? '') === 'password';
    }

    /**
     * A theme template's preview post (Post Content widget) is shown only to a user who may read it, and not
     * when the post is password-protected.
     */
    public static function preview_post_allowed(?object $post, callable $can_read, callable $password_required): bool {
        return $post !== null && $can_read((int) $post->ID) && !$password_required($post);
    }
}
