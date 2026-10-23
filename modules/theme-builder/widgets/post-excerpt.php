<?php
namespace Modulix\Modules\ThemeBuilder\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/widgets/post-excerpt.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Modulix\Base\Base_Widget;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Post_Excerpt extends Base_Widget {

	public function get_name() {
		// `theme` prefix is to avoid conflicts with a dynamic-tag with same name.
		return 'theme-post-excerpt';
	}

	public function get_title() {
		return esc_html__( 'Post Excerpt', 'modulix' );
	}

	public function get_icon() {
		return 'eicon-post-excerpt';
	}

	public function get_categories() {
		return [ 'theme-elements-single' ];
	}

	public function get_keywords() {
		return [ 'post', 'excerpt', 'description' ];
	}

	public function has_widget_inner_wrapper(): bool {
		return ! Plugin::elementor()->experiments->is_feature_active( 'e_optimized_markup' );
	}

	protected function register_controls() {
		$optimized_markup = Plugin::elementor()->experiments->is_feature_active( 'e_optimized_markup' ) && ! $this->has_widget_inner_wrapper();
		$widget_container_selector = $optimized_markup ? '' : ' .elementor-widget-container';

		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Content', 'modulix' ),
			]
		);

		$this->add_control(
			'excerpt',
			[
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic' => [
					'active' => true,
					'default' => Plugin::elementor()->dynamic_tags->tag_data_to_tag_text( null, 'post-excerpt' ),
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__( 'Style', 'modulix' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'align',
			[
				'label' => esc_html__( 'Alignment', 'modulix' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'start' => [
						'title' => esc_html__( 'Start', 'modulix' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'modulix' ),
						'icon' => 'eicon-text-align-center',
					],
					'end' => [
						'title' => esc_html__( 'End', 'modulix' ),
						'icon' => 'eicon-text-align-right',
					],
					'justify' => [
						'title' => esc_html__( 'Justified', 'modulix' ),
						'icon' => 'eicon-text-align-justify',
					],
				],
				'classes' => 'elementor-control-start-end',
				'selectors_dictionary' => [
					'left' => is_rtl() ? 'end' : 'start',
					'right' => is_rtl() ? 'start' : 'end',
				],
				'selectors' => [
					"{{WRAPPER}}{$widget_container_selector}" => 'text-align: {{VALUE}};',
				],
				'separator' => 'after',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'typography',
				'global' => [
					'default' => Global_Typography::TYPOGRAPHY_TEXT,
				],
				'selector' => "{{WRAPPER}}{$widget_container_selector}",
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'text_shadow',
				'selector' => '{{WRAPPER}}',
			]
		);

		$this->add_responsive_control(
			'paragraph_spacing',
			[
				'label' => esc_html__( 'Paragraph Spacing', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vh', 'custom' ],
				'range' => [
					'px' => [
						'max' => 100,
					],
					'em' => [
						'min' => 0.1,
						'max' => 20,
					],
				],
				'selectors' => [
					"{{WRAPPER}}{$widget_container_selector}" => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'separator',
			[
				'type' => Controls_Manager::DIVIDER,
			]
		);

		$this->start_controls_tabs( 'link_colors' );

		$this->start_controls_tab(
			'colors_normal',
			[
				'label' => esc_html__( 'Normal', 'modulix' ),
			]
		);

		$this->add_control(
			'title_color',
			[
				'label' => esc_html__( 'Text Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'global' => [
					'default' => Global_Colors::COLOR_TEXT,
				],
				'selectors' => [
					"{{WRAPPER}}{$widget_container_selector}" => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'link_color',
			[
				'label' => esc_html__( 'Link Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'colors_hover',
			[
				'label' => esc_html__( 'Hover', 'modulix' ),
			]
		);

		$this->add_control(
			'link_hover_color',
			[
				'label' => esc_html__( 'Link Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} a:hover, {{WRAPPER}} a:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'link_hover_color_transition_duration',
			[
				'label' => esc_html__( 'Transition Duration', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 's', 'ms', 'custom' ],
				'default' => [
					'unit' => 's',
				],
				'selectors' => [
					'{{WRAPPER}} a' => 'transition-duration: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function render() {
		$this->print_unescaped_setting( 'excerpt' );
	}
}
