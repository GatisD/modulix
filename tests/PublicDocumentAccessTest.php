<?php
use Modulix\Security\PublicDocumentAccess;
use PHPUnit\Framework\TestCase;

final class PublicDocumentAccessTest extends TestCase {
    public function test_anonymous_access_requires_a_published_unprotected_document(): void {
        $published = (object) ['post_status' => 'publish', 'post_password' => ''];
        $draft = (object) ['post_status' => 'draft', 'post_password' => ''];
        $private = (object) ['post_status' => 'private', 'post_password' => ''];
        $protected = (object) ['post_status' => 'publish', 'post_password' => 'set'];

        $this->assertTrue(PublicDocumentAccess::allowed($published, false));
        $this->assertFalse(PublicDocumentAccess::allowed($draft, false));
        $this->assertFalse(PublicDocumentAccess::allowed($private, false));
        $this->assertFalse(PublicDocumentAccess::allowed($protected, false));
        $this->assertFalse(PublicDocumentAccess::allowed(null, false));
    }

    public function test_editors_may_preview_non_public_documents_but_not_deleted_ones(): void {
        foreach (['publish', 'draft', 'private', 'future'] as $status) {
            $post = (object) ['post_status' => $status, 'post_password' => 'set'];
            $this->assertTrue(PublicDocumentAccess::allowed($post, true), $status);
        }

        $trashed = (object) ['post_status' => 'trash', 'post_password' => ''];
        $this->assertFalse(PublicDocumentAccess::allowed($trashed, true));
    }

    public function test_request_ids_are_positive_unique_and_bounded(): void {
        $ids = PublicDocumentAccess::normalizeIds(['7', 7, 0, -1, 'bad', 8, 9], 2);

        $this->assertSame([7, 8], $ids);
    }

    public function test_forms_guard_source_and_nested_template_before_loading_elements(): void {
        $source = (string) file_get_contents(__DIR__ . '/../modules/forms/classes/ajax-handler.php');
        $sourcePost = strpos($source, 'get_post( $post_id )');
        $sourceGuard = strpos($source, 'PublicDocumentAccess::allowed');
        $documentLoad = strpos($source, 'documents->get( $post_id )');

        $this->assertNotFalse($sourcePost);
        $this->assertNotFalse($sourceGuard);
        $this->assertNotFalse($documentLoad);
        $this->assertLessThan($documentLoad, $sourceGuard);
        $this->assertStringContainsString('get_post( $form[\'templateID\'] )', $source);
        $this->assertGreaterThanOrEqual(2, substr_count($source, 'PublicDocumentAccess::allowed'));
    }
}
