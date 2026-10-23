<?php
use Modulix\Readiness\Document;
use Modulix\Readiness\Usage;
use PHPUnit\Framework\TestCase;

final class UsageTest extends TestCase {
    private static function doc(array $elements, array $settings = [], string $type = 'wp-page'): Document {
        return new Document(7, 'Home', $type, $elements, $settings);
    }

    public function test_nested_widgets_and_document_type(): void {
        $doc = self::doc([['elType' => 'container', 'elements' => [
            ['elType' => 'widget', 'widgetType' => 'form', 'settings' => []],
            ['elType' => 'container', 'elements' => [['elType' => 'widget', 'widgetType' => 'heading']]],
        ]]]);
        $this->assertSame(['document:wp-page', 'widget:form', 'widget:heading'], Usage::of($doc));
    }

    public function test_dynamic_tags_in_settings_and_repeaters(): void {
        $doc = self::doc([['elType' => 'widget', 'widgetType' => 'icon-list', 'settings' => [
            '__dynamic__' => ['title' => '[elementor-tag id="a1" name="post-title" settings="%7B%7D"]'],
            'icon_list' => [['__dynamic__' => ['link' => '[elementor-tag id="b2" name="popup" settings="%7B%7D"]']]],
        ]]]);
        $this->assertSame(['document:wp-page', 'tag:popup', 'tag:post-title', 'widget:icon-list'], Usage::of($doc));
    }

    public function test_features_count_only_values_that_are_set(): void {
        $doc = self::doc([
            ['elType' => 'container', 'settings' => ['sticky' => 'top', '_attributes' => '', 'custom_css' => 'no']],
            ['elType' => 'container', 'settings' => ['e_display_conditions' => [[]], 'motion_fx_motion_fx_scrolling' => 'yes']],
        ]);
        $this->assertSame(['document:wp-page', 'feature:motion_fx', 'feature:sticky'], Usage::of($doc));
    }

    public function test_page_and_kit_settings(): void {
        $this->assertSame(['document:wp-page', 'feature:scroll_snap'], Usage::of(self::doc([], ['scroll_snap' => 'yes'])));
        $this->assertSame(['document:kit', 'feature:page_transitions'],
            Usage::of(self::doc([], ['settings_page_transitions_entrance_animation' => 'fadeIn'], 'kit')));
    }

    public function test_broken_data_is_skipped(): void {
        $doc = self::doc(['oops', ['elType' => 'widget', 'widgetType' => 7, 'settings' => 'x', 'elements' => 'y']], [], '');
        $this->assertSame([], Usage::of($doc));
    }
}
