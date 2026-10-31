<?php
use Modulix\Readiness\Document;
use Modulix\Readiness\Usage;
use PHPUnit\Framework\TestCase;

final class UsageTest extends TestCase {
    private static function doc(array $elements, array $settings = [], string $type = 'wp-page'): Document {
        return new Document(7, 'Home', $type, $elements, $settings);
    }

    private static function interaction(string $trigger = 'load', array $config = [], string $effect = 'fade'): array {
        $animation = [
            'effect' => ['$$type' => 'string', 'value' => $effect],
            'type' => ['$$type' => 'string', 'value' => 'in'],
            'timing_config' => ['$$type' => 'timing-config', 'value' => [
                'duration' => ['$$type' => 'number', 'value' => 600],
                'delay' => ['$$type' => 'number', 'value' => 0],
            ]],
        ];
        if ($config !== []) {
            $animation['config'] = ['$$type' => 'config-v2', 'value' => $config];
        }
        return [
            'version' => 1,
            'items' => ['$$type' => 'array', 'value' => [[
                '$$type' => 'interaction-item',
                'value' => [
                    'interaction_id' => ['$$type' => 'string', 'value' => 'i-1'],
                    'trigger' => ['$$type' => 'string', 'value' => $trigger],
                    'animation' => ['$$type' => 'animation-preset-props', 'value' => $animation],
                ],
            ]]],
        ];
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

    public function test_atomic_display_conditions_and_custom_css_are_distinct_features(): void {
        $doc = self::doc([[
            'elType' => 'widget',
            'widgetType' => 'e-form-input',
            'settings' => [
                'display-conditions' => [[
                    '$$type' => 'condition-group',
                    'value' => [['$$type' => 'string', 'value' => '{"condition":"logged_in"}']],
                ]],
            ],
            'styles' => [
                'local' => ['variants' => [[
                    'meta' => ['breakpoint' => 'desktop', 'state' => 'default'],
                    'props' => [],
                    'custom_css' => ['raw' => 'color%3Ared%3B'],
                ]]],
            ],
        ]]);

        $this->assertSame([
            'document:wp-page',
            'feature:atomic_custom_css',
            'feature:atomic_display_conditions',
            'widget:e-form-input',
        ], Usage::of($doc));
    }

    public function test_empty_atomic_feature_values_are_ignored(): void {
        $doc = self::doc([[
            'elType' => 'widget',
            'widgetType' => 'heading',
            'settings' => ['display-conditions' => []],
            'styles' => ['local' => ['variants' => [['custom_css' => []]]]],
        ]]);

        $this->assertSame(['document:wp-page', 'widget:heading'], Usage::of($doc));
    }

    public function test_pro_interaction_trigger_is_detected(): void {
        $doc = self::doc([['elType' => 'container', 'interactions' => self::interaction('hover')]]);
        $this->assertSame(['document:wp-page', 'feature:interactions'], Usage::of($doc));
    }

    public function test_pro_interaction_easing_is_detected_in_json_storage(): void {
        $interactions = self::interaction('load', [
            'easing' => ['$$type' => 'string', 'value' => 'easeOut'],
        ]);
        $doc = self::doc([['elType' => 'container', 'interactions' => json_encode($interactions, JSON_THROW_ON_ERROR)]]);
        $this->assertSame(['document:wp-page', 'feature:interactions'], Usage::of($doc));
    }

    public function test_pro_interaction_config_is_detected(): void {
        $doc = self::doc([['elType' => 'container', 'interactions' => self::interaction('load', [
            'repeat' => ['$$type' => 'string', 'value' => 'loop'],
        ])]]);
        $this->assertSame(['document:wp-page', 'feature:interactions'], Usage::of($doc));
    }

    public function test_pro_replay_on_scroll_in_is_detected(): void {
        $doc = self::doc([['elType' => 'container', 'interactions' => self::interaction('scrollIn', [
            'replay' => ['$$type' => 'boolean', 'value' => true],
        ])]]);
        $this->assertSame(['document:wp-page', 'feature:interactions'], Usage::of($doc));
    }

    public function test_config_values_ignored_by_the_trigger_are_not_reported_as_pro(): void {
        $doc = self::doc([['elType' => 'container', 'interactions' => self::interaction('load', [
            'replay' => ['$$type' => 'boolean', 'value' => true],
            'relativeTo' => ['$$type' => 'string', 'value' => 'document'],
            'times' => ['$$type' => 'number', 'value' => 3],
            'start' => ['$$type' => 'size', 'value' => ['size' => 50, 'unit' => '%']],
            'end' => ['$$type' => 'size', 'value' => ['size' => 50, 'unit' => '%']],
        ])]]);
        $this->assertSame(['document:wp-page'], Usage::of($doc));
    }

    public function test_core_only_interaction_is_not_reported_as_pro(): void {
        $doc = self::doc([['elType' => 'container', 'interactions' => self::interaction('scrollIn', [
            'replay' => ['$$type' => 'boolean', 'value' => false],
            'easing' => ['$$type' => 'string', 'value' => 'easeIn'],
            'relativeTo' => ['$$type' => 'string', 'value' => 'viewport'],
            'repeat' => ['$$type' => 'string', 'value' => ''],
            'times' => ['$$type' => 'number', 'value' => 1],
            'start' => ['$$type' => 'size', 'value' => ['size' => 85, 'unit' => '%']],
            'end' => ['$$type' => 'size', 'value' => ['size' => 15, 'unit' => '%']],
        ])]]);
        $this->assertSame(['document:wp-page'], Usage::of($doc));
    }

    public function test_page_and_kit_settings(): void {
        $this->assertSame(['document:wp-page', 'feature:scroll_snap'], Usage::of(self::doc([], ['scroll_snap' => 'yes'])));
        $this->assertSame(['document:kit', 'feature:page_transitions'],
            Usage::of(self::doc([], ['settings_page_transitions_entrance_animation' => 'fadeIn'], 'kit')));
    }

    public function test_theme_locations_from_document_type_and_saved_meta(): void {
        $this->assertSame(
            ['document:header', 'location:header'],
            Usage::of(new Document(7, 'Header', 'header', [], []))
        );
        $this->assertSame(
            ['document:header', 'location:header'],
            Usage::of(new Document(7, 'Header', 'header', [], [], 'single'))
        );
        $this->assertSame(
            ['document:code_snippet', 'location:elementor_body_end'],
            Usage::of(new Document(8, 'Tracking', 'code_snippet', [], [], 'elementor_body_end'))
        );
        $this->assertSame(
            ['document:floating-buttons', 'location:floating_buttons'],
            Usage::of(new Document(9, 'Contact', 'floating-buttons', [], []))
        );
    }

    public function test_broken_data_is_skipped(): void {
        $doc = self::doc(['oops', ['elType' => 'widget', 'widgetType' => 7, 'settings' => 'x', 'elements' => 'y']], [], '');
        $this->assertSame([], Usage::of($doc));
    }
}
