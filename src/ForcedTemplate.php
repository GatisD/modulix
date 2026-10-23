<?php
namespace Modulix;

/**
 * Gate for the ?theme_template_id preview parameter: Modulix honours it only for a user who may edit that
 * template.
 */
final class ForcedTemplate {
    /**
     * @param mixed                $raw      raw $_GET value
     * @param callable(int): bool  $can_edit e.g. fn($id) => current_user_can('edit_post', $id)
     */
    public static function id($raw, callable $can_edit): ?int {
        if (!is_string($raw) || !preg_match('/^[1-9][0-9]*$/', $raw)) {
            return null;
        }
        $id = (int) $raw;
        return $can_edit($id) ? $id : null;
    }
}
