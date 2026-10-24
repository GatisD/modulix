<?php
use Modulix\Security\UploadCheck;
use PHPUnit\Framework\TestCase;

final class UploadCheckTest extends TestCase {
    private const WORDPRESS_MIME_TYPES = [
        'jpg|jpeg|jpe' => 'image/jpeg',
        'pdf' => 'application/pdf',
    ];

    public function test_accepts_a_wordpress_known_extension_when_wordpress_confirms_it(): void {
        $checked = ['ext' => 'jpg', 'type' => 'image/jpeg', 'proper_filename' => false];

        $this->assertTrue(UploadCheck::isAllowed('jpg', $checked, self::WORDPRESS_MIME_TYPES));
    }

    public function test_rejects_a_wordpress_known_extension_without_full_confirmation(): void {
        $this->assertFalse(UploadCheck::isAllowed('jpg', ['ext' => false, 'type' => false], self::WORDPRESS_MIME_TYPES));
        $this->assertFalse(UploadCheck::isAllowed('jpg', ['ext' => 'jpg', 'type' => false], self::WORDPRESS_MIME_TYPES));
        $this->assertFalse(UploadCheck::isAllowed('jpg', ['ext' => false, 'type' => 'image/jpeg'], self::WORDPRESS_MIME_TYPES));
    }

    public function test_rejects_a_mismatched_confirmed_extension(): void {
        $checked = ['ext' => 'png', 'type' => 'image/png', 'proper_filename' => false];

        $this->assertFalse(UploadCheck::isAllowed('jpg', $checked, self::WORDPRESS_MIME_TYPES));
    }

    public function test_compares_confirmed_extensions_case_insensitively(): void {
        $checked = ['ext' => 'JPG', 'type' => 'image/jpeg', 'proper_filename' => false];

        $this->assertTrue(UploadCheck::isAllowed('jpg', $checked, self::WORDPRESS_MIME_TYPES));
    }

    public function test_keeps_field_allowed_custom_extensions_when_wordpress_does_not_know_them(): void {
        $checked = ['ext' => false, 'type' => false, 'proper_filename' => false];

        $this->assertTrue(UploadCheck::isAllowed('myicons', $checked, self::WORDPRESS_MIME_TYPES));
    }
}
