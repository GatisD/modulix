<?php
namespace Modulix\Mcp;

interface HtmlSanitizer {
    public function sanitize(string $html): string;
}
