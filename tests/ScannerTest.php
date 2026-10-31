<?php
use Modulix\Readiness\Document;
use Modulix\Readiness\Scanner;
use PHPUnit\Framework\TestCase;

final class ScannerTest extends TestCase {
    private const PRO = ['widgets' => ['form', 'posts'], 'tags' => ['popup', 'post-title'],
                         'documents' => ['popup', 'header', 'floating-buttons'],
                         'locations' => ['header', 'popup', 'floating_buttons'],
                         'features' => ['sticky', 'attributes']];
    private const COVERED = ['widgets' => ['posts'], 'tags' => ['post-title'],
                             'documents' => ['header', 'floating-buttons'], 'locations' => ['header', 'popup'],
                             'features' => ['sticky']];

    private static function widget(string $name): array {
        return ['elType' => 'widget', 'widgetType' => $name];
    }

    public function test_reports_only_pro_items_modulix_lacks(): void {
        $docs = [
            new Document(1, 'Home', 'wp-page', [self::widget('form'), self::widget('posts'), self::widget('heading')], []),
            new Document(2, 'Offer', 'popup', [['elType' => 'container', 'settings' => ['_attributes' => 'data-x|1', 'sticky' => 'top']]], []),
            new Document(3, 'Header', 'header', [self::widget('form')], []),
            new Document(4, 'Contact button', 'floating-buttons', [], []),
        ];
        $this->assertSame(
            ['document:popup' => [2], 'feature:attributes' => [2], 'location:floating_buttons' => [4], 'widget:form' => [1, 3]],
            (new Scanner(self::PRO, self::COVERED))->missing($docs)
        );
    }

    public function test_nothing_missing_gives_an_empty_array(): void {
        $docs = [new Document(1, 'Blog', 'header', [self::widget('posts')], [])];
        $this->assertSame([], (new Scanner(self::PRO, self::COVERED))->missing($docs));
    }

    public function test_real_lists_include_form_and_posts(): void {
        $docs = [new Document(5, 'Contact', 'wp-page', [self::widget('form'), self::widget('posts')], [])];
        $this->assertSame([], Scanner::forModulix()->missing($docs));
    }

    public function test_real_lists_ignore_serialized_core_interaction_defaults(): void {
        $coreInteraction = [
            'elType' => 'container',
            'interactions' => [
                'version' => 1,
                'items' => [['$$type' => 'interaction-item', 'value' => [
                    'trigger' => ['$$type' => 'string', 'value' => 'scrollIn'],
                    'animation' => ['$$type' => 'animation-preset-props', 'value' => [
                        'effect' => ['$$type' => 'string', 'value' => 'fade'],
                        'type' => ['$$type' => 'string', 'value' => 'in'],
                        'timing_config' => ['$$type' => 'timing-config', 'value' => [
                            'duration' => ['$$type' => 'size', 'value' => ['size' => 600, 'unit' => 'ms']],
                            'delay' => ['$$type' => 'size', 'value' => ['size' => 0, 'unit' => 'ms']],
                        ]],
                        'config' => ['$$type' => 'config', 'value' => [
                            'replay' => ['$$type' => 'boolean', 'value' => false],
                            'easing' => ['$$type' => 'string', 'value' => 'easeIn'],
                            'relativeTo' => ['$$type' => 'string', 'value' => 'viewport'],
                            'repeat' => ['$$type' => 'string', 'value' => ''],
                            'times' => ['$$type' => 'number', 'value' => 1],
                            'start' => ['$$type' => 'size', 'value' => ['size' => 85, 'unit' => '%']],
                            'end' => ['$$type' => 'size', 'value' => ['size' => 15, 'unit' => '%']],
                        ]],
                    ]],
                ]]],
            ],
        ];

        $this->assertSame([], Scanner::forModulix()->missing([
            new Document(6, 'Core Interaction', 'wp-page', [$coreInteraction], []),
        ]));
    }

    public function test_real_lists_report_atomic_form_and_atomic_element_gaps(): void {
        $atomicWidgets = array_map(self::widget(...), [
            'e-form-checkbox',
            'e-form-input',
            'e-form-label',
            'e-form-submit-button',
            'e-form-textarea',
        ]);
        $atomicFeatureElement = [
            'elType' => 'widget',
            'widgetType' => 'heading',
            'settings' => ['display-conditions' => [[
                '$$type' => 'condition-group',
                'value' => [['$$type' => 'string', 'value' => '{"condition":"logged_in"}']],
            ]]],
            'styles' => ['local' => ['variants' => [[
                'custom_css' => ['raw' => 'color%3Ared%3B'],
            ]]]],
        ];
        $proInteraction = [
            'elType' => 'container',
            'interactions' => [
                'version' => 1,
                'items' => [['$$type' => 'interaction-item', 'value' => [
                    'trigger' => ['$$type' => 'string', 'value' => 'click'],
                    'animation' => ['$$type' => 'animation-preset-props', 'value' => [
                        'effect' => ['$$type' => 'string', 'value' => 'fade'],
                        'type' => ['$$type' => 'string', 'value' => 'in'],
                        'timing_config' => ['$$type' => 'timing-config', 'value' => []],
                    ]],
                ]]],
            ],
        ];

        $this->assertSame([
            'feature:atomic_custom_css' => [12],
            'feature:atomic_display_conditions' => [12],
            'feature:interactions' => [13],
            'widget:e-form-checkbox' => [11],
            'widget:e-form-input' => [11],
            'widget:e-form-label' => [11],
            'widget:e-form-submit-button' => [11],
            'widget:e-form-textarea' => [11],
        ], Scanner::forModulix()->missing([
            new Document(11, 'Atomic Form', 'wp-page', $atomicWidgets, []),
            new Document(12, 'Atomic Features', 'wp-page', [$atomicFeatureElement], []),
            new Document(13, 'Pro Interaction', 'wp-page', [$proInteraction], []),
        ]));
    }
}
