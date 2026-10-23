<?php
use Modulix\ContentAccess;
use PHPUnit\Framework\TestCase;

final class ContentAccessTest extends TestCase {
    public function test_acf_options_for_site_managers_anywhere(): void {
        $this->assertTrue(ContentAccess::acf_options_allowed(true, true, false));
    }

    public function test_acf_options_on_public_render_of_a_published_document(): void {
        $this->assertTrue(ContentAccess::acf_options_allowed(false, false, true));
    }

    public function test_acf_options_hidden_from_other_users_in_editor_preview_or_drafts(): void {
        $this->assertFalse(ContentAccess::acf_options_allowed(false, true, true));
        $this->assertFalse(ContentAccess::acf_options_allowed(false, false, false));
    }

    public function test_acf_password_fields_never_go_to_tags(): void {
        $this->assertTrue(ContentAccess::is_secret_acf_field(['type' => 'password']));
        $this->assertFalse(ContentAccess::is_secret_acf_field(['type' => 'text']));
        $this->assertFalse(ContentAccess::is_secret_acf_field(false));
    }

    public function test_preview_post_needs_read_access_and_no_password(): void {
        $post = (object) ['ID' => 7];
        $this->assertTrue(ContentAccess::preview_post_allowed($post, fn($id) => true, fn($p) => false));
        $this->assertFalse(ContentAccess::preview_post_allowed($post, fn($id) => false, fn($p) => false));
        $this->assertFalse(ContentAccess::preview_post_allowed($post, fn($id) => true, fn($p) => true));
        $this->assertFalse(ContentAccess::preview_post_allowed(null, fn($id) => true, fn($p) => false));
    }
}
