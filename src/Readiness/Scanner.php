<?php
namespace Modulix\Readiness;

use Modulix\Coverage;

/** Compares what a site's documents use with what Modulix covers. Only Elementor Pro names can be missing. */
final class Scanner {
    /** @var array<string, true> */
    private array $pro;
    /** @var array<string, true> */
    private array $covered;

    /**
     * @param array{widgets?: string[], tags?: string[], documents?: string[], locations?: string[], features?: string[]} $pro
     * @param array{widgets?: string[], tags?: string[], documents?: string[], locations?: string[], features?: string[]} $covered
     */
    public function __construct(array $pro, array $covered) {
        $this->pro = self::items($pro);
        $this->covered = self::items($covered);
    }

    public static function forModulix(): self {
        return new self(
            ['widgets' => ProCatalog::WIDGETS, 'tags' => ProCatalog::TAGS,
             'documents' => ProCatalog::DOCUMENT_TYPES, 'locations' => ProCatalog::THEME_LOCATIONS,
             'features' => ProCatalog::FEATURES],
            ['widgets' => Coverage::WIDGETS, 'tags' => Coverage::TAGS,
             'documents' => Coverage::DOCUMENT_TYPES, 'locations' => Coverage::THEME_LOCATIONS,
             'features' => Coverage::FEATURES],
        );
    }

    /**
     * @param iterable<Document> $documents
     * @return array<string, int[]> missing item => ids of the documents that use it, items sorted
     */
    public function missing(iterable $documents): array {
        $out = [];
        foreach ($documents as $doc) {
            foreach (Usage::of($doc) as $item) {
                if (isset($this->pro[$item]) && !isset($this->covered[$item])) {
                    $out[$item][] = $doc->id;
                }
            }
        }
        ksort($out);
        return $out;
    }

    /** @param array<string, string[]> $lists @return array<string, true> */
    private static function items(array $lists): array {
        $out = [];
        foreach (['widgets' => 'widget', 'tags' => 'tag', 'documents' => 'document',
                  'locations' => 'location', 'features' => 'feature'] as $key => $prefix) {
            foreach ($lists[$key] ?? [] as $name) {
                $out["$prefix:$name"] = true;
            }
        }
        return $out;
    }
}
