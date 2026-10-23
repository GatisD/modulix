<?php
use Modulix\Readiness\Report;
use PHPUnit\Framework\TestCase;

final class ReportTest extends TestCase {
    public function test_labels(): void {
        $this->assertSame('Custom Code snippets', Report::label('document:code_snippet'));
        $this->assertSame('Widget "form"', Report::label('widget:form'));
        $this->assertSame('Dynamic tag "popup"', Report::label('tag:popup'));
    }

    public function test_rows_cut_long_id_lists(): void {
        $this->assertSame(
            [['item' => 'widget:form', 'label' => 'Widget "form"', 'documents' => 3, 'ids' => '1, 2, ...']],
            Report::rows(['widget:form' => [1, 2, 3]], 2)
        );
    }

    public function test_html_escapes_titles_and_urls(): void {
        $html = Report::html(['widget:form' => [4]], [4 => '<b>Contact</b>'], fn(int $id): string => "https://x.test/?p=$id&a=\"e\"");
        $this->assertStringContainsString('&lt;b&gt;Contact&lt;/b&gt;', $html);
        $this->assertStringContainsString('href="https://x.test/?p=4&amp;a=&quot;e&quot;"', $html);
        $this->assertStringNotContainsString('<b>Contact', $html);
    }

    public function test_html_when_nothing_is_missing(): void {
        $this->assertStringContainsString('covers every Elementor Pro feature', Report::html([], [], fn(int $id): string => ''));
    }

    public function test_ecosystem_lists_only_themes_and_plugins_with_gaps(): void {
        $html = Report::ecosystemHtml([
            'Plugin: fine' => ['provided' => ['ElementorPro\\Plugin'], 'missing' => [], 'constants' => []],
            'Theme: <woodmart>' => ['provided' => ['ElementorPro\\Plugin'], 'missing' => ['ElementorPro\\License\\API'], 'constants' => ['ELEMENTOR_PRO_VERSION']],
        ]);
        $this->assertStringNotContainsString('Plugin: fine', $html);
        $this->assertStringContainsString('Theme: &lt;woodmart&gt;', $html);
        $this->assertStringContainsString('ElementorPro\\License\\API, ELEMENTOR_PRO_VERSION', $html);
        $this->assertStringContainsString('No active theme or plugin', Report::ecosystemHtml([]));
    }
}
