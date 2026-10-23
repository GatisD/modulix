<?php
namespace Modulix;

/**
 * User and author lookups for the editor's query control: WP_User_Query arguments with a fixed field list (ID, display
 * name, and e-mail for users who may see private data).
 */
final class QueryAccess {
    public static function user_search_args(string $term, bool $with_email): array {
        return [
            'fields' => self::fields($with_email),
            'search' => '*' . $term . '*',
            'search_columns' => ['user_login', 'user_nicename'],
        ];
    }

    /** @param array<mixed> $ids */
    public static function user_titles_args(array $ids, bool $with_email): array {
        $clean = [];
        foreach ($ids as $id) {
            if ((is_int($id) || (is_string($id) && ctype_digit($id))) && (int) $id > 0 && !in_array((int) $id, $clean, true)) {
                $clean[] = (int) $id;
            }
        }
        return ['fields' => self::fields($with_email), 'include' => $clean];
    }

    public static function user_display_text(object $user, string $display, bool $can_see_email): string {
        $name = (string) ($user->display_name ?? '');
        if ($display === 'detailed' && $can_see_email && !empty($user->user_email)) {
            return sprintf('%s (%s)', $name, $user->user_email);
        }
        return $name;
    }

    /** @return string[] */
    private static function fields(bool $with_email): array {
        return $with_email ? ['ID', 'display_name', 'user_email'] : ['ID', 'display_name'];
    }
}
