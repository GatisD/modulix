<?php
namespace Modulix\Security;

final class UploadCheck {
    /**
     * WordPress MIME keys are extension lists such as "jpg|jpeg|jpe".
     *
     * @param array<string, string> $wordpressMimeTypes
     */
    private static function wordpressKnowsExtension(string $extension, array $wordpressMimeTypes): bool {
        $extension = strtolower($extension);

        foreach (array_keys($wordpressMimeTypes) as $extensions) {
            foreach (explode('|', strtolower((string) $extensions)) as $knownExtension) {
                if ($extension === $knownExtension) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Custom extensions keep Pro's field-level allowlist behaviour. WordPress-known extensions additionally need
     * the real file type and extension confirmation returned by wp_check_filetype_and_ext().
     *
     * @param array<string, mixed> $checked
     * @param array<string, string> $wordpressMimeTypes
     */
    public static function isAllowed(string $extension, array $checked, array $wordpressMimeTypes): bool {
        if (!self::wordpressKnowsExtension($extension, $wordpressMimeTypes)) {
            return true;
        }

        $confirmedExtension = $checked['ext'] ?? false;
        $confirmedType = $checked['type'] ?? false;

        return is_string($confirmedExtension)
            && strtolower($confirmedExtension) === strtolower($extension)
            && is_string($confirmedType)
            && $confirmedType !== '';
    }
}
