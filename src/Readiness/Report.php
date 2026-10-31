<?php
namespace Modulix\Readiness;

/** Turns readiness results into text for people: labels, CLI rows and HTML (escaped here, no WordPress needed). */
final class Report {
    private const LABELS = [
        'document:code_snippet' => 'Custom Code snippets',
        'document:popup' => 'Popup templates',
        'document:loop-item' => 'Loop item templates',
        'document:product' => 'WooCommerce product templates',
        'document:product-archive' => 'WooCommerce product archive templates',
        'document:widget' => 'Global widgets',
        'feature:attributes' => 'Custom attributes on elements',
        'feature:atomic_custom_css' => 'Atomic Custom CSS',
        'feature:atomic_display_conditions' => 'Atomic Display Conditions',
        'feature:display_conditions' => 'Display conditions on elements',
        'feature:interactions' => 'Pro interactions',
        'feature:page_transitions' => 'Page transitions',
        'feature:scroll_snap' => 'Scroll snap',
    ];
    private const KINDS = ['widget' => 'Widget', 'tag' => 'Dynamic tag', 'document' => 'Template type',
                           'location' => 'Theme location', 'feature' => 'Feature'];

    public static function label(string $item): string {
        if (isset(self::LABELS[$item])) {
            return self::LABELS[$item];
        }
        [$kind, $name] = explode(':', $item, 2) + [1 => ''];
        return (self::KINDS[$kind] ?? $kind) . ' "' . $name . '"';
    }

    /**
     * @param array<string, int[]> $missing
     * @return array<int, array{item: string, label: string, documents: int, ids: string}>
     */
    public static function rows(array $missing, int $maxIds = 10): array {
        $rows = [];
        foreach ($missing as $item => $ids) {
            $rows[] = [
                'item' => $item,
                'label' => self::label($item),
                'documents' => count($ids),
                'ids' => implode(', ', array_slice($ids, 0, $maxIds)) . (count($ids) > $maxIds ? ', ...' : ''),
            ];
        }
        return $rows;
    }

    /**
     * @param array<string, int[]> $missing
     * @param array<int, string> $titles document id => title
     * @param callable(int): string $editUrl
     */
    public static function html(array $missing, array $titles, callable $editUrl, int $maxLinks = 10): string {
        if ($missing === []) {
            return '<p><strong>Modulix covers every Elementor Pro feature these pages and templates use.</strong></p>';
        }
        $out = '<table class="widefat striped"><thead><tr><th>Not in Modulix yet</th><th>Used on</th></tr></thead><tbody>';
        foreach ($missing as $item => $ids) {
            $links = [];
            foreach (array_slice($ids, 0, $maxLinks) as $id) {
                $links[] = '<a href="' . self::e($editUrl($id)) . '">' . self::e($titles[$id] ?? ('#' . $id)) . '</a>';
            }
            $more = count($ids) > $maxLinks ? ' and ' . (count($ids) - $maxLinks) . ' more' : '';
            $out .= '<tr><td>' . self::e(self::label($item)) . '</td><td>' . implode(', ', $links) . $more . '</td></tr>';
        }
        return $out . '</tbody></table>';
    }

    /** @param array<string, array{provided: string[], missing: string[], constants: string[]}> $results name => result */
    public static function ecosystemHtml(array $results): string {
        $rows = '';
        foreach ($results as $name => $r) {
            if ($r['missing'] === [] && $r['constants'] === []) {
                continue;
            }
            $rows .= '<tr><td>' . self::e($name) . '</td><td>' . count($r['provided']) . '</td><td>'
                . self::e(implode(', ', array_merge($r['missing'], $r['constants']))) . '</td></tr>';
        }
        if ($rows === '') {
            return '<p>No active theme or plugin uses an Elementor Pro name that Modulix does not provide.</p>';
        }
        return '<table class="widefat striped"><thead><tr><th>Theme or plugin</th><th>Pro names Modulix provides</th>'
            . '<th>Pro names Modulix does not provide</th></tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<p>Test these on a copy of the site after switching. Constants such as ELEMENTOR_PRO_VERSION stay undefined.</p>';
    }

    private static function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}
