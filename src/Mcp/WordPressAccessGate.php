<?php
namespace Modulix\Mcp;

final class WordPressAccessGate implements AccessGate {
    public function canReadSite(): bool {
        return current_user_can('edit_posts');
    }

    public function canReadDocument(int $postId): bool {
        return $postId > 0
            && current_user_can('read_post', $postId)
            && current_user_can('edit_post', $postId);
    }

    public function userId(): int {
        return (int) get_current_user_id();
    }
}
