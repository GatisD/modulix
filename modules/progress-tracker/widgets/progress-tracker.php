<?php

namespace Modulix\Modules\ProgressTracker\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/progress-tracker/widgets/progress-tracker.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Modules\PageTemplates\Module as PageTemplatesModule;
use Modulix\Base\Base_Widget;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class ProgressTracker extends Base_Widget {

	public function get_name() {
		return 'progress-tracker';
	}

	public function get_title() {
		return esc_html__( 'Progress Tracker', 'modulix' );
	}

	public function get_icon() {
		return 'eicon-progress-tracker';
	}

	public function get_categories() {
		return [ 'pro-elements', 'theme-elements-single' ];
	}

	public function get_keywords() {
		return [ 'progress', 'tracker', 'read', 'scroll' ];
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	public function has_widget_inner_wrapper(): bool {
		return ! Plugin::elementor()->experiments->is_feature_active( 'e_optimized_markup' );
	}

	/**
	 * Get style dependencies.
	 *
	 * Retrieve the list of style dependencies the widget requires.
	 *
	 * @since 3.24.0
	 * @access public
	 *
	 * @return array Widget style dependencies.
	 */
	public function get_style_depends(): array {
		return [ 'widget-progress-tracker' ];
	}

	private function register_content_controls() {
		$this->start_controls_section(
			'section_content_scrolling_tracker',
			[
				'label' => esc_html__( 'Progress Tracker', 'modulix' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'type',
			[
				'label' => esc_html__( 'Tracker Type', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'frontend_available' => true,
				'options' => [
					'horizontal' => esc_html__( 'Horizontal', 'modulix' ),
					'circular' => esc_html__( 'Circular', 'modulix' ),
				],
				'default' => 'horizontal',
			]
		);

		$this->add_control(
			'relative_to',
			[
				'label' => esc_html__( 'Progress relative to', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'frontend_available' => true,
				'options' => [
					'entire_page' => esc_html__( 'Entire Page', 'modulix' ),
					'post_content' => esc_html__( 'Post Content', 'modulix' ),
					'selector' => esc_html__( 'Selector', 'modulix' ),
				],
				'default' => 'entire_page',
			]
		);

		$this->add_control(
			'selector',
			[
				'label' => esc_html__( 'Selector', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'description' => esc_html__( 'Add the CSS ID or Class of a specific element on this page to track its progress separately', 'modulix' ),
				'frontend_available' => true,
				'ai' => [
					'active' => false,
				],
				'condition' => [
					'relative_to' => 'selector',
				],
				'placeholder' => '#id, .class',
			]
		);

		$this->add_control(
			'relative_to_description',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw' => esc_html__( 'Note: You can only track progress relative to Post Content on a single post template.', 'modulix' ),
				'content_classes' => 'elementor-descriptor',
				'condition' => [
					'relative_to' => 'post_content',
				],
			]
		);

		$this->add_control(
			'direction',
			[
				'label' => esc_html__( 'Direction', 'modulix' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'ltr' => [
						'title' => esc_html__( 'Left', 'modulix' ),
						'icon' => 'eicon-h-align-left',
					],
					'rtl' => [
						'title' => esc_html__( 'Right', 'modulix' ),
						'icon' => 'eicon-h-align-right',
					],
				],
				'render_type' => 'template',
				'frontend_available' => true,
				'selectors' => [
					'{{WRAPPER}}' => '--direction: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'percentage',
			[
				'label' => esc_html__( 'Percentage', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'modulix' ),
				'label_off' => esc_html__( 'Hide', 'modulix' ),
				'default' => 'no',
				'frontend_available' => true,
			]
		);

		$this->add_responsive_control(
			'percentage_position',
			[
				'label' => esc_html__( 'Percentage Position', 'modulix' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'rtl' => [
						'title' => esc_html__( 'Left', 'modulix' ),
						'icon' => 'eicon-h-align-left',
					],
					'ltr' => [
						'title' => esc_html__( 'Right', 'modulix' ),
						'icon' => 'eicon-h-align-right',
					],
				],
				'condition' => [
					'type' => 'horizontal',
					'percentage' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--text-direction: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_tracker_style_controls() {
		$this->start_controls_section(
			'section_style_scrolling_tracker',
			[
				'label' => esc_html__( 'Tracker', 'modulix' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'circular_size',
			[
				'label' => esc_html__( 'Size', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'max' => 200,
					],
					'em' => [
						'max' => 20,
					],
					'rem' => [
						'max' => 20,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--circular-width: {{SIZE}}{{UNIT}}; --circular-height: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'type' => 'circular',
				],
			]
		);

		$this->add_control(
			'heading_progress_style',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Progress Indicator', 'modulix' ),
			]
		);

		$this->add_control(
			'circular_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--circular-color: {{VALUE}}',
				],
				'condition' => [
					'type' => 'circular',
				],
			]
		);

		$this->add_responsive_control(
			'circular_width',
			[
				'label' => esc_html__( 'Width', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'range' => [
					'px' => [
						'max' => 400,
					],
					'em' => [
						'max' => 40,
					],
					'rem' => [
						'max' => 40,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--circular-progress-width: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'type' => 'circular',
				],
			]
		);

		$this->add_responsive_control(
			'align',
			[
				'label' => esc_html__( 'Alignment', 'modulix' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => esc_html__( 'Left', 'modulix' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'modulix' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'modulix' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'condition' => [
					'type' => 'circular',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'horizontal_color',
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .current-progress',
				'condition' => [
					'type' => 'horizontal',
				],
				'fields_options' => [
					'background' => [
						'label' => esc_html__( 'Progress Color', 'modulix' ),
					],
				],
			]
		);

		$this->add_control(
			'horizontal_border_style',
			[
				'label' => esc_html__( 'Border Type', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => [
					'none' => esc_html__( 'None', 'modulix' ),
					'solid' => esc_html__( 'Solid', 'modulix' ),
					'double' => esc_html__( 'Double', 'modulix' ),
					'dotted' => esc_html__( 'Dotted', 'modulix' ),
					'dashed' => esc_html__( 'Dashed', 'modulix' ),
					'groove' => esc_html__( 'Groove', 'modulix' ),
				],
				'selectors' => [
					'{{WRAPPER}}' => '--horizontal-progress-border: {{VALUE}};',
				],
				'condition' => [
					'type' => 'horizontal',
				],
			]
		);

		$this->add_responsive_control(
			'horizontal_border_width',
			[
				'label' => esc_html__( 'Width', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors' => [
					'{{WRAPPER}}' => '--horizontal-progress-border-top-width: {{TOP}}{{UNIT}}; --horizontal-progress-border-right-width: {{RIGHT}}{{UNIT}}; --horizontal-progress-border-bottom-width: {{BOTTOM}}{{UNIT}}; --horizontal-progress-border-left-width: {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'horizontal_border_style!' => 'none',
					'type' => 'horizontal',
				],
			]
		);

		$this->add_control(
			'horizontal_border_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--horizontal-progress-border-color: {{VALUE}}',
				],
				'condition' => [
					'horizontal_border_style!' => 'none',
					'type' => 'horizontal',
				],
			]
		);

		$this->add_responsive_control(
			'horizontal_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}}' => '--progress-border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'type' => 'horizontal',
				],
			]
		);

		$this->add_control(
			'heading_tracker_background_style',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Tracker Background', 'modulix' ),
				'separator' => 'before',
			]
		);

		$this->add_control(
			'circular_background_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--circular-background-color: {{VALUE}}',
				],
				'condition' => [
					'type' => 'circular',
				],
			]
		);

		$this->add_responsive_control(
			'circular_background_width',
			[
				'label' => esc_html__( 'Width', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'range' => [
					'px' => [
						'max' => 400,
					],
					'em' => [
						'max' => 40,
					],
					'rem' => [
						'max' => 40,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--circular-background-width: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'type' => 'circular',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'horizontal_background_color',
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .elementor-scrolling-tracker-horizontal',
				'fields_options' => [
					'background' => [
						'label' => esc_html__( 'Background Color', 'modulix' ),
					],
				],
				'condition' => [
					'type' => 'horizontal',
				],
			]
		);

		$this->add_responsive_control(
			'horizontal_height',
			[
				'label' => esc_html__( 'Height', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vh', 'custom' ],
				'range' => [
					'px' => [
						'max' => 100,
					],
					'em' => [
						'max' => 10,
					],
					'rem' => [
						'max' => 10,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--horizontal-height: {{SIZE}}{{UNIT}}',
				],
				'condition' => [
					'type' => 'horizontal',
				],
			]
		);

		$this->add_control(
			'horizontal_tracker_border_style',
			[
				'label' => esc_html__( 'Border Type', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => [
					'none' => esc_html__( 'None', 'modulix' ),
					'solid' => esc_html__( 'Solid', 'modulix' ),
					'double' => esc_html__( 'Double', 'modulix' ),
					'dotted' => esc_html__( 'Dotted', 'modulix' ),
					'dashed' => esc_html__( 'Dashed', 'modulix' ),
					'groove' => esc_html__( 'Groove', 'modulix' ),
				],
				'selectors' => [
					'{{WRAPPER}}' => '--horizontal-border-style: {{VALUE}};',
				],
				'condition' => [
					'type' => 'horizontal',
				],
			]
		);

		$this->add_responsive_control(
			'horizontal_tracker_border_width',
			[
				'label' => esc_html__( 'Width', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors' => [
					'{{WRAPPER}}' => '--horizontal-border-top-width: {{TOP}}{{UNIT}}; --horizontal-border-right-width: {{RIGHT}}{{UNIT}}; --horizontal-border-bottom-width: {{BOTTOM}}{{UNIT}}; --horizontal-border-left-width: {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'horizontal_tracker_border_style!' => 'none',
					'type' => 'horizontal',
				],
			]
		);

		$this->add_control(
			'horizontal_tracker_border_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--horizontal-border-color: {{VALUE}}',
				],
				'condition' => [
					'horizontal_tracker_border_style!' => 'none',
					'type' => 'horizontal',
				],
			]
		);

		$this->add_responsive_control(
			'horizontal_tracker_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}}' => '--border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'type' => 'horizontal',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'box_shadow',
				'selector' => '{{WRAPPER}} .elementor-scrolling-tracker',
				'condition' => [
					'type' => 'horizontal',
				],
			]
		);

		$this->add_responsive_control(
			'horizontal_padding',
			[
				'label' => esc_html__( 'Padding', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors' => [
					'{{WRAPPER}}' => '--tracker-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'type' => 'horizontal',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_content_style_controls() {
		$this->start_controls_section(
			'section__content_style_scrolling_tracker',
			[
				'label' => esc_html__( 'Content', 'modulix' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'percentage' => 'yes',
				],
			]
		);

		$this->add_control(
			'heading_percentage_style',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Percentage', 'modulix' ),
				'separator' => 'before',
			]
		);

		$this->add_control(
			'percentage_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--percentage-color: {{VALUE}}',
				],
				'frontend_available' => true,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'percentage_typography',
				'selector' => '{{WRAPPER}} .current-progress-percentage',
				'global' => [
					'default' => Global_Typography::TYPOGRAPHY_TEXT,
				],
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'percentage_text_shadow',
				'selector' => '{{WRAPPER}} .current-progress-percentage',
			]
		);

		$this->end_controls_section();
	}

	protected function register_controls() {
		$this->register_content_controls();
		$this->register_tracker_style_controls();
		$this->register_content_style_controls();
	}

	public function render_plain_content() {}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$horizontal = 'horizontal' === $settings['type'];
		$this->add_render_attribute( 'scrolling-percentage', 'class', 'current-progress-percentage' );
		$this->add_render_attribute( 'scrolling-tracker', 'class', [
			'elementor-scrolling-tracker',
			'elementor-scrolling-tracker-' . $settings['type'],
			'elementor-scrolling-tracker-alignment-' . $settings['align'],
		] ); ?>

		<div <?php $this->print_render_attribute_string( 'scrolling-tracker' ); ?>>
		<?php if ( $horizontal ) : ?>
				<div class="current-progress">
					<div <?php $this->print_render_attribute_string( 'scrolling-percentage' ); ?>></div>
				</div>
		<?php else : ?>
				<svg
						width="100%"
						height="100%">
					<circle class="circle"
							r="40%"
							cx="50%"
							cy="50%"/>

					<circle class="current-progress"
							r="40%"
							cx="50%"
							cy="50%"/>
				</svg>
				<div <?php $this->print_render_attribute_string( 'scrolling-percentage' ); ?>></div>
		<?php endif; ?>
		</div>
		<?php
	}
}
