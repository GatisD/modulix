<?php
namespace Modulix\Readiness;

/** One Elementor document as the readiness check sees it. */
final class Document {
    /**
     * @param string $templateType _elementor_template_type ('' when unset); 'code_snippet' for Custom Code posts
     * @param array<int, mixed> $elements decoded _elementor_data
     * @param array<string, mixed> $settings _elementor_page_settings
     * @param string $themeLocation _elementor_location when saved separately from the document type
     */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $templateType,
        public readonly array $elements,
        public readonly array $settings,
        public readonly string $themeLocation = '',
    ) {}
}
