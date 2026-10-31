<?php
use Modulix\Security\UploadCheck;
use PHPUnit\Framework\TestCase;

final class UploadCheckTest extends TestCase {
    private const WORDPRESS_MIME_TYPES = [
        'jpg|jpeg|jpe' => 'image/jpeg',
        'pdf' => 'application/pdf',
    ];

    public function test_accepts_a_safe_extension_when_wordpress_and_content_confirm_it(): void {
        $checked = ['ext' => 'jpg', 'type' => 'image/jpeg', 'proper_filename' => false];

        $this->assertTrue(UploadCheck::isAllowed('jpg', $checked, self::WORDPRESS_MIME_TYPES, 'image/jpeg'));
    }

    public function test_rejects_a_wordpress_known_extension_without_full_confirmation(): void {
        $this->assertFalse(UploadCheck::isAllowed('jpg', ['ext' => false, 'type' => false], self::WORDPRESS_MIME_TYPES, 'image/jpeg'));
        $this->assertFalse(UploadCheck::isAllowed('jpg', ['ext' => 'jpg', 'type' => false], self::WORDPRESS_MIME_TYPES, 'image/jpeg'));
        $this->assertFalse(UploadCheck::isAllowed('jpg', ['ext' => false, 'type' => 'image/jpeg'], self::WORDPRESS_MIME_TYPES, 'image/jpeg'));
    }

    public function test_rejects_a_mismatched_confirmed_extension(): void {
        $checked = ['ext' => 'png', 'type' => 'image/png', 'proper_filename' => false];

        $this->assertFalse(UploadCheck::isAllowed('jpg', $checked, self::WORDPRESS_MIME_TYPES, 'image/png'));
    }

    public function test_compares_confirmed_extensions_case_insensitively(): void {
        $checked = ['ext' => 'JPG', 'type' => 'image/jpeg', 'proper_filename' => false];

        $this->assertTrue(UploadCheck::isAllowed('jpg', $checked, self::WORDPRESS_MIME_TYPES, 'image/jpeg'));
    }

    public function test_rejects_field_allowed_custom_extensions_when_the_type_cannot_be_confirmed(): void {
        $checked = ['ext' => false, 'type' => false, 'proper_filename' => false];

        $this->assertFalse(UploadCheck::isAllowed('myicons', $checked, self::WORDPRESS_MIME_TYPES, 'application/octet-stream'));
    }

    public function test_rejects_double_extensions_and_executable_polyglots(): void {
        $file = tempnam(sys_get_temp_dir(), 'modulix-upload-');
        file_put_contents($file, "GIF89a<?" . "php echo 1;");

        try {
            $checked = ['ext' => 'gif', 'type' => 'image/gif', 'proper_filename' => false];
            $this->assertFalse(UploadCheck::fileAllowed($file, 'image.gif.php', $checked, self::WORDPRESS_MIME_TYPES));
            $this->assertFalse(UploadCheck::fileAllowed($file, 'image.gif', $checked, self::WORDPRESS_MIME_TYPES));
        } finally {
            unlink($file);
        }
    }

    public function test_real_pdf_file_is_accepted_and_mismatched_name_is_rejected(): void {
        $file = tempnam(sys_get_temp_dir(), 'modulix-upload-');
        file_put_contents($file, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF\n");

        try {
            $pdf = ['ext' => 'pdf', 'type' => 'application/pdf', 'proper_filename' => false];
            $jpg = ['ext' => 'jpg', 'type' => 'image/jpeg', 'proper_filename' => false];
            $this->assertTrue(UploadCheck::fileAllowed($file, 'document.pdf', $pdf, self::WORDPRESS_MIME_TYPES));
            $this->assertFalse(UploadCheck::fileAllowed($file, 'photo.jpg', $jpg, self::WORDPRESS_MIME_TYPES));
        } finally {
            unlink($file);
        }
    }
}
