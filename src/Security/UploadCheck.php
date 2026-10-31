<?php
namespace Modulix\Security;

final class UploadCheck {
    private const SAFE_MIME_TYPES = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'jpe' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'avif' => ['image/avif'],
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/x-ole-storage', 'application/CDFV2'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/x-ole-storage', 'application/CDFV2'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'odt' => ['application/vnd.oasis.opendocument.text', 'application/zip'],
        'avi' => ['video/x-msvideo'],
        'ogg' => ['audio/ogg', 'video/ogg', 'application/ogg'],
        'm4a' => ['audio/mp4', 'audio/x-m4a'],
        'mov' => ['video/quicktime'],
        'mp3' => ['audio/mpeg'],
        'mp4' => ['video/mp4'],
        'mpg' => ['video/mpeg'],
        'wav' => ['audio/wav', 'audio/x-wav'],
        'wmv' => ['video/x-ms-wmv'],
    ];

    /**
     * @param array<string, mixed> $checked
     * @param array<string, string> $wordpressMimeTypes
     */
    public static function isAllowed(string $extension, array $checked, array $wordpressMimeTypes, string $actualMime): bool {
        unset($wordpressMimeTypes);
        $extension = strtolower($extension);
        $confirmedExtension = $checked['ext'] ?? false;
        $confirmedType = $checked['type'] ?? false;

        return isset(self::SAFE_MIME_TYPES[$extension])
            && is_string($confirmedExtension)
            && strtolower($confirmedExtension) === $extension
            && is_string($confirmedType)
            && in_array($confirmedType, self::SAFE_MIME_TYPES[$extension], true)
            && in_array($actualMime, self::SAFE_MIME_TYPES[$extension], true);
    }

    /** @param array<string, mixed> $checked @param array<string, string> $wordpressMimeTypes */
    public static function fileAllowed(string $path, string $name, array $checked, array $wordpressMimeTypes): bool {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset(self::SAFE_MIME_TYPES[$extension]) || self::containsPhpMarker($path)) {
            return false;
        }

        if (!class_exists(\finfo::class)) {
            return false;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $actualMime = $finfo->file($path);

        return is_string($actualMime) && self::isAllowed($extension, $checked, $wordpressMimeTypes, $actualMime);
    }

    private static function containsPhpMarker(string $path): bool {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return true;
        }

        $tail = '';
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, 8192);
                if ($chunk === false) {
                    return true;
                }
                $sample = $tail . $chunk;
                if (stripos($sample, '<?' . 'php') !== false || str_contains($sample, '<?=')) {
                    return true;
                }
                $tail = substr($sample, -5);
            }
        } finally {
            fclose($handle);
        }

        return false;
    }
}
