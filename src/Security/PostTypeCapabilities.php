<?php
namespace Modulix\Security;

final class PostTypeCapabilities {
    private const PRIMITIVE_KEYS = [
        'edit_posts',
        'edit_others_posts',
        'delete_posts',
        'publish_posts',
        'read_private_posts',
        'read',
        'delete_private_posts',
        'delete_published_posts',
        'delete_others_posts',
        'edit_private_posts',
        'edit_published_posts',
        'create_posts',
    ];

    /** @return array<string, string> */
    public static function restricted(string $postType, string $capability): array {
        return [
            'edit_post' => 'edit_' . $postType,
            'read_post' => 'read_' . $postType,
            'delete_post' => 'delete_' . $postType,
        ] + array_fill_keys(self::PRIMITIVE_KEYS, $capability);
    }
}
