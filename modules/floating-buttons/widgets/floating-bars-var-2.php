<?php

namespace Modulix\Modules\FloatingButtons\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/floating-buttons/widgets/floating-bars-var-2.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\FloatingButtons\Base\Widget_Floating_Bars_Base_Pro;
use Modulix\Modules\FloatingButtons\Classes\Render\Floating_Bars_Var_2_Render;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Floating_Bars_Var_2 extends Widget_Floating_Bars_Base_Pro {
	public static function get_configuration() {
		$config = parent::get_configuration();
		$config['content']['floating_bar_section']['close_switch_default'] = 'no';
		$config['content']['floating_bar_section']['has_pause_switch'] = true;

		$config['style']['floating_bar_section']['has_close_bg'] = true;
		$config['style']['floating_bar_section']['close_offset'] = '0';
		$config['style']['floating_bar_section']['close_position_selector'] = 'align-self: {{VALUE}}';
		$config['style']['floating_bar_section']['has_close_position_control'] = false;

		return $config;
	}

	public function get_name(): string {
		return 'floating-bars-var-2';
	}

	public function get_title(): string {
		return esc_html__( 'Ticker', 'modulix' );
	}

	protected function add_floating_bar_style_section(): void {
		$this->start_controls_section(
			'style_floating_bar',
			[
				'label' => esc_html__( 'Floating Bar', 'modulix' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'style_floating_bar_horizontal_position',
			[
				'label' => esc_html__( 'Controls Horizontal position', 'modulix' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'start' => [
						'title' => esc_html__( 'Left', 'modulix' ),
						'icon' => 'eicon-h-align-left',
					],
					'end' => [
						'title' => esc_html__( 'Right', 'modulix' ),
						'icon' => 'eicon-h-align-right',
					],
				],
				'default' => 'end',
				'toggle' => true,
				'selectors' => [
					'{{WRAPPER}} .e-floating-bars' => '--e-floating-bars-ticker-controls-order: {{VALUE}}',
				],
				'selectors_dictionary' => [
					'start' => '-1',
					'end' => '2',
				],
				'conditions' => [
					'relation' => 'or',
					'terms' => [
						[
							'name' => 'floating_bar_close_switch',
							'operator' => '===',
							'value' => 'yes',
						],
						[
							'name' => 'floating_bar_pause_switch',
							'operator' => '===',
							'value' => 'yes',
						],
					],
				],
			]
		);

		$this->add_responsive_control(
			'style_floating_bar_controls_size',
			[
				'label' => esc_html__( 'Controls Size', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 150,
					],
				],
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .e-floating-bars' => '--e-floating-bars-controls-icon-size: {{SIZE}}{{UNIT}}',
				],
				'separator' => 'after',
				'conditions' => [
					'relation' => 'or',
					'terms' => [
						[
							'name' => 'floating_bar_close_switch',
							'operator' => '===',
							'value' => 'yes',
						],
						[
							'name' => 'floating_bar_pause_switch',
							'operator' => '===',
							'value' => 'yes',
						],
					],
				],
			]
		);

		$this->add_floating_bar_pause_style_controls();

		$this->add_floating_bar_close_button_style_controls();

		$this->add_responsive_control(
			'style_floating_bar_element_spacing',
			[
				'label' => esc_html__( 'Headline Spacing', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
				],
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .e-floating-bars' => '--e-floating-bars-headlines-gap: {{SIZE}}{{UNIT}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'style_floating_bar_padding',
			[
				'label' => esc_html__( 'Vertical Padding', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
				],
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .e-floating-bars' => '--e-floating-bars-vertical-padding: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_floating_bar_background_style_controls();

		$this->end_controls_section();
	}

	protected function add_accessible_name_control(): void {
		$this->add_control(
			'accessible_name',
			[
				'label' => esc_html__( 'Accessible Name', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'placeholder' => esc_html__( 'Enter text', 'modulix' ),
				'default' => esc_html__( 'Banner', 'modulix' ),
				'conditions' => [
					'relation' => 'or',
					'terms' => [
						[
							'name' => 'floating_bar_close_switch',
							'operator' => '===',
							'value' => 'yes',
						],
						[
							'name' => 'floating_bar_pause_switch',
							'operator' => '===',
							'value' => 'yes',
						],
					],
				],
			],
		);
	}

	protected function add_ticker_content_section(): void {
		$this->start_controls_section(
			'style_ticker',
			[
				'label' => esc_html__( 'Ticker', 'modulix' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_responsive_control(
			'style_ticker_animation_type',
			[
				'label' => esc_html__( 'Type', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'autoplay',
				'mobile_default' => 'autoplay',
				'options' => [
					'autoplay' => esc_html__( 'Autoplay', 'modulix' ),
					'scroll' => esc_html__( 'On page scroll', 'modulix' ),
				],
				'frontend_available' => true,
			]
		);

		$this->add_responsive_control(
			'style_ticker_scroll_direction',
			[
				'label' => esc_html__( 'Scroll Direction', 'modulix' ),
				'type' => Controls_Manager::CHOOSE,
				'default' => 'forwards',
				'mobile_default' => 'forwards',
				'toggle' => false,
				'options' => [
					'forwards' => [
						'title' => esc_html__( 'Left', 'modulix' ),
						'icon' => 'eicon-arrow-left',
					],
					'reverse' => [
						'title' => esc_html__( 'Right', 'modulix' ),
						'icon' => 'eicon-arrow-right',
					],
				],
				'selectors_dictionary' => [
					'forwards' => is_rtl() ? 'reverse' : 'forwards',
					'reverse' => is_rtl() ? 'forwards' : 'reverse',
				],
				'selectors' => [
					'{{WRAPPER}} .e-floating-bars' => '--e-floating-bars-scroll-direction: {{VALUE}};',
				],
				'frontend_available' => true,
				'condition' => [
					'style_ticker_animation_type' => 'autoplay',
				],
			]
		);

		$this->add_control(
			'style_ticker_scroll_speed',
			[
				'label' => esc_html__( 'Scroll speed', 'modulix' ) . ' (s)',
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 0.5,
						'max' => 100,
						'step' => 0.5,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .e-floating-bars' => '--e-floating-bars-scroll-duration: {{SIZE}}s;',
				],
				'condition' => [
					'style_ticker_animation_type' => 'autoplay',
				],
			]
		);

		$this->add_control(
			'style_ticker_pause_hover',
			[
				'label' => esc_html__( 'Pause on Hover', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'modulix' ),
				'label_off' => esc_html__( 'No', 'modulix' ),
				'return_value' => 'yes',
				'default' => 'yes',
				'condition' => [
					'style_ticker_animation_type' => 'autoplay',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function add_content_tab(): void {
		$this->add_headlines_content_section();
		$this->add_ticker_content_section();
		$this->add_floating_bar_content_section();
	}

	protected function add_style_tab(): void {
		$this->add_headlines_style_section();
		$this->add_floating_bar_style_section();
	}

	public function render(): void {
		$render_strategy = new Floating_Bars_Var_2_Render( $this );

		$render_strategy->render();
	}
}
