<?php
namespace Modulix\Mcp;

final class WordPressWritableDocument implements WritableDocument {
    public function __construct(private object $document) {}

    public function id(): int {
        return method_exists($this->document, 'get_main_id') ? (int) $this->document->get_main_id() : 0;
    }

    public function postType(): string {
        if (!method_exists($this->document, 'get_post')) {
            return '';
        }
        $post = $this->document->get_post();
        return is_object($post) && isset($post->post_type) ? (string) $post->post_type : '';
    }

    public function elements(): array {
        if (!method_exists($this->document, 'get_elements_data')) {
            return [];
        }
        $elements = $this->document->get_elements_data();
        return is_array($elements) ? $elements : [];
    }

    public function pageSettings(): array {
        if (!method_exists($this->document, 'get_settings')) {
            return [];
        }
        $settings = $this->document->get_settings();
        return is_array($settings) ? $settings : [];
    }

    public function pageSettingsControls(): array {
        if (!method_exists($this->document, 'get_controls')) {
            return [];
        }
        $schema = (new WidgetSchemaMapper())->map(
            'page-settings',
            'document',
            (array) $this->document->get_controls()
        );
        return is_array($schema['controls'] ?? null) ? $schema['controls'] : [];
    }

    public function canReadAttachment(int $attachmentId): bool {
        return $attachmentId > 0
            && get_post_type($attachmentId) === 'attachment'
            && current_user_can('read_post', $attachmentId);
    }

    public function save(array $data): bool {
        return method_exists($this->document, 'save') && true === $this->document->save($data);
    }
}
