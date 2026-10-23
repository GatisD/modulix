<?php
use Modulix\TemplateOptions;
use PHPUnit\Framework\TestCase;

final class TemplateOptionsTest extends TestCase {
    public function test_options_are_id_to_title_with_type_label(): void {
        $posts = [(object) ['ID' => 6, 'post_title' => 'Footer - LV'], (object) ['ID' => 13, 'post_title' => '']];
        $types = [6 => 'footer', 13 => 'section'];
        $labels = ['footer' => 'Footer', 'section' => 'Section'];
        $this->assertSame(['6' => 'Footer - LV (Footer)', '13' => '#13 (Section)'],
            TemplateOptions::from_posts($posts, $types, $labels));
    }

    public function test_unknown_type_shows_title_only(): void {
        $posts = [(object) ['ID' => 5, 'post_title' => 'Kit']];
        $this->assertSame(['5' => 'Kit'], TemplateOptions::from_posts($posts, [5 => 'kit'], []));
    }
}
