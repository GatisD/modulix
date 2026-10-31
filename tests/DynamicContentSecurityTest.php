<?php
use Modulix\Security\DynamicContent;
use PHPUnit\Framework\TestCase;

final class DynamicContentSecurityTest extends TestCase {
    public function test_request_values_must_be_scalar_text(): void {
        $this->assertSame('value', DynamicContent::scalar_text('value'));
        $this->assertSame('17', DynamicContent::scalar_text(17));
        $this->assertSame('', DynamicContent::scalar_text(['value']));
        $this->assertSame('', DynamicContent::scalar_text(new stdClass()));
        $this->assertSame('', DynamicContent::scalar_text(null));
    }

    public function test_user_meta_rejects_empty_and_protected_keys(): void {
        $this->assertTrue(DynamicContent::user_meta_allowed('public_field', false));
        $this->assertFalse(DynamicContent::user_meta_allowed('', false));
        $this->assertFalse(DynamicContent::user_meta_allowed('private_field', true));
    }

    public function test_stored_shortcode_needs_a_public_document_or_edit_access(): void {
        $published = (object) ['post_status' => 'publish', 'post_password' => ''];
        $protected = (object) ['post_status' => 'publish', 'post_password' => 'set'];
        $draft = (object) ['post_status' => 'draft', 'post_password' => ''];

        $this->assertTrue(DynamicContent::stored_shortcode_allowed($published, false));
        $this->assertFalse(DynamicContent::stored_shortcode_allowed($protected, false));
        $this->assertFalse(DynamicContent::stored_shortcode_allowed($draft, false));
        $this->assertTrue(DynamicContent::stored_shortcode_allowed($draft, true));
    }

    public function test_template_status_and_capabilities_control_rendering(): void {
        $published = (object) ['post_type' => 'elementor_library', 'post_status' => 'publish', 'post_password' => ''];
        $private = (object) ['post_type' => 'elementor_library', 'post_status' => 'private', 'post_password' => ''];
        $draft = (object) ['post_type' => 'elementor_library', 'post_status' => 'draft', 'post_password' => ''];
        $page = (object) ['post_type' => 'page', 'post_status' => 'publish', 'post_password' => ''];

        $this->assertTrue(DynamicContent::template_allowed($published, 'elementor_library', false, false));
        $this->assertFalse(DynamicContent::template_allowed($private, 'elementor_library', false, false));
        $this->assertTrue(DynamicContent::template_allowed($private, 'elementor_library', true, false));
        $this->assertFalse(DynamicContent::template_allowed($draft, 'elementor_library', true, false));
        $this->assertTrue(DynamicContent::template_allowed($draft, 'elementor_library', true, true));
        $this->assertFalse(DynamicContent::template_allowed($page, 'elementor_library', true, true));
        $this->assertFalse(DynamicContent::template_allowed(null, 'elementor_library', true, true));
    }

    public function test_internal_links_need_public_status_or_read_access(): void {
        $published = (object) ['post_status' => 'publish'];
        $private = (object) ['post_status' => 'private'];
        $trashed = (object) ['post_status' => 'trash'];

        $this->assertTrue(DynamicContent::linked_post_allowed($published, false));
        $this->assertFalse(DynamicContent::linked_post_allowed($private, false));
        $this->assertTrue(DynamicContent::linked_post_allowed($private, true));
        $this->assertFalse(DynamicContent::linked_post_allowed($trashed, true));
    }

    public function test_lightbox_metadata_only_uses_readable_unprotected_attachments(): void {
        $publicAttachment = (object) [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_password' => '',
        ];
        $privatePost = (object) [
            'post_type' => 'post',
            'post_status' => 'private',
            'post_password' => '',
        ];
        $privateParent = (object) [
            'post_type' => 'page',
            'post_status' => 'private',
            'post_password' => '',
        ];
        $passwordProtectedAttachment = (object) [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_password' => 'set',
        ];

        $this->assertTrue(DynamicContent::lightbox_attachment_allowed($publicAttachment, false, null, false));
        $this->assertFalse(DynamicContent::lightbox_attachment_allowed($privatePost, true, null, false));
        $this->assertFalse(DynamicContent::lightbox_attachment_allowed($publicAttachment, false, $privateParent, false));
        $this->assertTrue(DynamicContent::lightbox_attachment_allowed($publicAttachment, true, $privateParent, true));
        $this->assertFalse(DynamicContent::lightbox_attachment_allowed($passwordProtectedAttachment, true, null, false));
    }

    public function test_author_links_need_public_posts_or_user_listing_access(): void {
        $subscriber = (object) ['ID' => 17];

        $this->assertFalse(DynamicContent::author_link_allowed($subscriber, false, false));
        $this->assertTrue(DynamicContent::author_link_allowed($subscriber, true, false));
        $this->assertTrue(DynamicContent::author_link_allowed($subscriber, false, true));
        $this->assertFalse(DynamicContent::author_link_allowed(null, true, true));
    }

    public function test_lifted_tags_use_the_security_boundary(): void {
        $root = dirname(__DIR__);
        $request = file_get_contents($root . '/modules/dynamic-tags/tags/request-parameter.php');
        $shortcode = file_get_contents($root . '/modules/dynamic-tags/tags/shortcode.php');
        $user = file_get_contents($root . '/modules/dynamic-tags/tags/user-info.php');
        $internal = file_get_contents($root . '/modules/dynamic-tags/tags/internal-url.php');
        $lightbox = file_get_contents($root . '/modules/dynamic-tags/tags/lightbox.php');
        $template = file_get_contents($root . '/modules/library/classes/shortcode.php');
        $module = file_get_contents($root . '/modules/dynamic-tags/module.php');

        $this->assertStringContainsString('DynamicContent::scalar_text', $request);
        $this->assertStringContainsString('esc_html(', $request);
        $this->assertStringContainsString('DynamicContent::stored_shortcode_allowed', $shortcode);
        $this->assertStringNotContainsString('shortcode/should_escape', $shortcode);
        $this->assertStringContainsString('DynamicContent::user_meta_allowed', $user);
        $this->assertStringContainsString('DynamicContent::linked_post_allowed', $internal);
        $this->assertStringContainsString('DynamicContent::author_link_allowed', $internal);
        $this->assertStringContainsString('DynamicContent::lightbox_attachment_allowed', $lightbox);
        $this->assertStringContainsString('wp_get_attachment_image_src( $attachment_id', $lightbox);
        $this->assertStringContainsString('get_lightbox_image_attributes( $attachment_id )', $lightbox);
        $this->assertStringNotContainsString('get_lightbox_image_attributes( $image_settings[\'id\'] )', $lightbox);
        $this->assertStringContainsString('DynamicContent::template_allowed', $template);
        $this->assertStringNotContainsString('security-reviewed', $module);
    }

    public function test_query_control_keeps_its_existing_security_trim(): void {
        $source = file_get_contents(dirname(__DIR__) . '/modules/query-control/module.php');

        $this->assertStringContainsString('verify_user_access_for_editing', $source);
        $this->assertStringContainsString('QueryAccess::user_search_args', $source);
        $this->assertStringContainsString('QueryAccess::taxonomy_allowed', $source);
        $this->assertStringContainsString('get_viewable_taxonomy_names', $source);
        $this->assertStringContainsString("register_ajax_action( 'query_control_value_titles'", $source);
        $this->assertStringContainsString("register_ajax_action( 'pro_panel_posts_control_filter_autocomplete'", $source);
        $this->assertStringNotContainsString("register_ajax_action( 'query_control_value_titles_deprecated'", $source);
        $this->assertStringNotContainsString("register_ajax_action( 'pro_panel_posts_control_filter_autocomplete_deprecated'", $source);
    }
}
