<?php
namespace Modulix\Mcp;

final class WordPressHtmlSanitizer implements HtmlSanitizer {
    public function sanitize(string $html): string {
        return current_user_can('unfiltered_html') ? $html : wp_kses_post($html);
    }
}
