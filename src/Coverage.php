<?php
namespace Modulix;

/**
 * What Modulix supports, by the names Elementor stores in page data. The readiness check compares a site's pages
 * with these lists; tests/CoverageTest.php fails when modules/ and these lists differ.
 */
final class Coverage {
    public const WIDGETS = [
        'archive-posts',
        'contact-buttons-var-1',
        'contact-buttons-var-10',
        'contact-buttons-var-3',
        'contact-buttons-var-4',
        'contact-buttons-var-5',
        'contact-buttons-var-6',
        'contact-buttons-var-7',
        'contact-buttons-var-8',
        'contact-buttons-var-9',
        'floating-bars-var-2',
        'floating-bars-var-3',
        'gallery',
        'nav-menu',
        'portfolio',
        'posts',
        'template',
        'theme-archive-title',
        'theme-page-title',
        'theme-post-content',
        'theme-post-excerpt',
        'theme-post-featured-image',
        'theme-post-title',
        'theme-site-logo',
        'theme-site-title',
    ];

    public const TAGS = [
        'acf-color',
        'acf-date-time',
        'acf-file',
        'acf-gallery',
        'acf-image',
        'acf-number',
        'acf-text',
        'acf-url',
        'archive-description',
        'archive-meta',
        'archive-title',
        'archive-url',
        'author-info',
        'author-meta',
        'author-name',
        'author-profile-picture',
        'author-url',
        'comments-number',
        'comments-url',
        'contact-url',
        'current-date-time',
        'featured-image-data',
        'page-title',
        'post-custom-field',
        'post-date',
        'post-excerpt',
        'post-featured-image',
        'post-gallery',
        'post-id',
        'post-terms',
        'post-time',
        'post-title',
        'post-url',
        'site-logo',
        'site-tagline',
        'site-title',
        'site-url',
    ];

    public const DOCUMENT_TYPES = [
        'archive',
        'error-404',
        'footer',
        'header',
        'search-results',
        'single',
        'single-page',
        'single-post',
    ];

    /** Element, page and site settings features; keys in Readiness\Usage. */
    public const FEATURES = ['custom_css', 'motion_fx', 'sticky'];
}
