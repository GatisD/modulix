<?php
namespace Modulix;

/**
 * Choices for the Template widget (Pro: AJAX "query" control, whose editor side lives in Pro's editor bundle).
 * Modulix lists the published templates in a core select2 control; the saved value stays the template ID.
 */
final class TemplateOptions {
    /**
     * @param object[]              $posts  WP_Post-like objects with ID and post_title
     * @param array<int,string>     $types  post ID => Elementor template type
     * @param array<string,string>  $labels template type => document type title
     * @return array<string,string> "ID" => "Title (Type)"
     */
    public static function from_posts(array $posts, array $types, array $labels): array {
        $options = [];
        foreach ($posts as $post) {
            $title = $post->post_title !== '' ? $post->post_title : '#' . $post->ID;
            $label = $labels[$types[$post->ID] ?? ''] ?? null;
            $options[(string) $post->ID] = $label ? "$title ($label)" : $title;
        }
        return $options;
    }
}
