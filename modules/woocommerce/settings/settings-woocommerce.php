<?php
namespace Modulix\Modules\Woocommerce\Settings;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/settings/settings-woocommerce.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Base\Document;
use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Tab_Base;
use Modulix\Plugin;
use Modulix\Modules\QueryControl\Module as QueryModule;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Settings_Woocommerce extends Tab_Base {

	public function get_id() {
		return 'settings-woocommerce';
	}

	public function get_title() {
		return esc_html__( 'WooCommerce', 'modulix' );
	}

	public function get_icon() {
		return 'eicon-woo-settings';
	}

	public function get_group() {
		return 'settings';
	}

	public function get_help_url() {
		return 'https://go.elementor.com/global-woocommerce';
	}

	protected function register_tab_controls() {

		$this->start_controls_section(
			'section_woocommerce_pages',
			[
				'label' => esc_html__( 'WooCommerce Pages', 'modulix' ),
				'tab' => $this->get_id(),
			]
		);

		$this->register_woocommerce_pages_controls();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_woocommerce_notices',
			[
				'label' => esc_html__( 'Notices', 'modulix' ),
				'tab' => $this->get_id(),
			]
		);

		$this->register_woocommerce_notices_controls();

		$this->end_controls_section();

		$this->start_controls_section(
			'woocommerce_error_notices',
			[
				'label' => esc_html__( 'Error Notices', 'modulix' ),
				'tab' => $this->get_id(),
				'condition' => [
					'woocommerce_notices_elements' => 'wc_error',
				],
			]
		);

		$this->add_notice_text_controls( 'error', $this->get_notice_text_selectors( 'error' ) );

		$this->add_control(
			'error_message_link_title',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Link Text', 'modulix' ),
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'error_message_link_typography',
				'selector' => $this->get_main_selector( 'error', 'body', ' a.wc-backward' ),
			]
		);

		$this->start_controls_tabs( 'error_message_links' );

		$this->start_controls_tab( 'error_message_normal_links', [
			'label' => esc_html__( 'Normal', 'modulix' ),
		] );

		$this->add_control(
			'error_message_normal_links_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					$this->get_main_selector( 'error' ) => '--error-message-normal-links-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'error_message_hover_links', [
			'label' => esc_html__( 'Hover', 'modulix' ),
		] );

		$this->add_control(
			'error_message_hover_links_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					$this->get_main_selector( 'error' ) => '--error-message-hover-links-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_notice_box_controls( 'error', $this->get_notice_box_selectors( 'error' ) );

		$this->end_controls_section();

		$this->start_controls_section(
			'woocommerce_message_notices',
			[
				'label' => esc_html__( 'Message Notices', 'modulix' ),
				'tab' => $this->get_id(),
				'condition' => [
					'woocommerce_notices_elements' => 'wc_message',
				],
			]
		);

		$this->add_notice_text_controls( 'message', $this->get_notice_text_selectors( 'message' ) );

		$this->add_control(
			'notice_message_link_title',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Link Text', 'modulix' ),
			]
		);

		$message_link_typography_selector = $this->get_main_selector( 'message', 'body', ' .restore-item' );
		$message_link_typography_selector .= ', ';
		$message_link_typography_selector .= $this->get_main_selector( 'message', 'body', ' a:not([class])' );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'notice_message_link_typography',
				'selector' => $message_link_typography_selector,
			]
		);

		$this->start_controls_tabs( 'notice_message_links' );

		$this->start_controls_tab( 'notice_message_normal_links', [
			'label' => esc_html__( 'Normal', 'modulix' ),
		] );

		$message_normal_link_color_selector = $this->get_main_selector( 'message', '', ' .restore-item' );
		$message_normal_link_color_selector .= ', ';
		$message_normal_link_color_selector .= $this->get_main_selector( 'message', '', ' a:not([class])' );

		$this->add_control(
			'notice_message_normal_links_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					$message_normal_link_color_selector => '--notice-message-normal-links-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'notice_message_hover_links', [
			'label' => esc_html__( 'Hover', 'modulix' ),
		] );

		$message_hover_link_color_selector = $this->get_main_selector( 'message', '', ' .restore-item:hover' );
		$message_hover_link_color_selector .= ', ';
		$message_hover_link_color_selector .= $this->get_main_selector( 'message', '', ' a:not([class]):hover' );

		$this->add_control(
			'notice_message_hover_links_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					$message_hover_link_color_selector => '--notice-message-hover-links-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_notice_box_controls( 'message', $this->get_notice_box_selectors( 'message' ) );

		$this->add_notice_button_controls( 'message', $this->get_notice_button_selectors( 'message' ) );

		$this->end_controls_section();

		$this->start_controls_section(
			'woocommerce_info_notices',
			[
				'label' => esc_html__( 'Info Notices', 'modulix' ),
				'tab' => $this->get_id(),
				'condition' => [
					'woocommerce_notices_elements' => 'wc_info',
				],
			]
		);

		$this->add_notice_text_controls( 'info', $this->get_notice_text_selectors( 'info' ) );

		$this->add_notice_box_controls( 'info', $this->get_notice_box_selectors( 'info' ) );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_notice_button_controls( 'info', $this->get_notice_button_selectors( 'info' ) );

		$this->end_controls_section();
	}

	private function get_main_selector( $notice_type, $selector_prefix = '', $selector_suffix = '' ) {
		$notice_name = 'message' === $notice_type ? 'success' : $notice_type;
		$old_notice_selector = $selector_prefix . '.e-wc-' . $notice_type . '-notice .woocommerce-' . $notice_type . $selector_suffix;
		$block_notice_selector = $selector_prefix . '.e-wc-' . $notice_type . '-notice .wc-block-components-notice-banner.is-' . $notice_name . $selector_suffix;

		return $old_notice_selector . ', ' . $block_notice_selector;
	}

	private function get_notice_text_selectors( $notice_type ) {
		return [
			$notice_type . '_message_text_color' => [
				$this->get_main_selector( $notice_type ) => '--' . $notice_type . '-message-text-color: {{VALUE}};',
			],
			$notice_type . '_message_text_typography' => $this->get_main_selector( $notice_type ),
			$notice_type . '_message_text_shadow' => $this->get_main_selector( $notice_type ),
			$notice_type . '_message_icon_color' => [
				$this->get_main_selector( $notice_type ) => '--' . $notice_type . '-message-icon-color: {{VALUE}};',
			],
			$notice_type . '_message_icon_size' => [
				$this->get_main_selector( $notice_type, '', ':before' ) => 'font-size: {{SIZE}}{{UNIT}};',
			],
			$notice_type . '_message_icon_spacing' => [
				$this->get_main_selector( $notice_type ) => '--' . $notice_type . '-message-icon-spacing: {{SIZE}}{{UNIT}};',
			],
		];
	}

	private function get_notice_box_selectors( $notice_type ) {
		return [
			$notice_type . '_notice_box_background' => $this->get_main_selector( $notice_type, 'body' ),
			$notice_type . '_notice_box_box_shadow' => $this->get_main_selector( $notice_type ),
			$notice_type . '_notice_box_border' => $this->get_main_selector( $notice_type, 'body' ),
			$notice_type . '_notice_box_border_radius' => [
				$this->get_main_selector( $notice_type ) => '--' . $notice_type . '-box-border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
			$notice_type . '_notice_box_padding' => [
				$this->get_main_selector( $notice_type ) => '--' . $notice_type . '-box-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		];
	}

	private function get_notice_button_selectors( $notice_type ) {
		$button_hover_background_selector = $this->get_main_selector( $notice_type, 'body', ' .button:hover' );

		if ( 'info' === $notice_type ) {
			// Override styling from the My Account widget.
			$button_hover_background_selector .= ', body.e-wc-info-notice .e-my-account-tab:not(.e-my-account-tab__dashboard--custom) .woocommerce .woocommerce-info .woocommerce-Button:hover';
			$button_hover_background_selector .= ', body.e-wc-info-notice .e-my-account-tab:not(.e-my-account-tab__dashboard--custom) .woocommerce .wc-block-components-notice-banner.is-info .woocommerce-Button:hover';
		}

		return [
			$notice_type . '_button_typography' => $this->get_main_selector( $notice_type, 'body', ' .button' ),
			$notice_type . '_button_text_shadow' => $this->get_main_selector( $notice_type, 'body', ' .button' ),
			$notice_type . '_buttons_normal_text_color' => [
				$this->get_main_selector( $notice_type ) => '--' . $notice_type . '-buttons-normal-text-color: {{VALUE}};',
			],
			$notice_type . '_buttons_normal_background' => $this->get_main_selector( $notice_type, 'body', ' .button' ),
			$notice_type . '_buttons_normal_box_shadow' => $this->get_main_selector( $notice_type, '', ' .button' ),
			$notice_type . '_buttons_hover_text_color' => [
				$this->get_main_selector( $notice_type ) => '--' . $notice_type . '-buttons-hover-text-color: {{VALUE}};',
			],
			$notice_type . '_buttons_hover_background' => $button_hover_background_selector,
			$notice_type . '_buttons_focus_box_shadow' => $this->get_main_selector( $notice_type, '', ' .button:hover' ),
			$notice_type . '_buttons_hover_border_color' => [
				$this->get_main_selector( $notice_type ) => '--' . $notice_type . '-buttons-hover-border-color: {{VALUE}};',
			],
			$notice_type . '_buttons_hover_transition_duration' => [
				$this->get_main_selector( $notice_type ) => '--' . $notice_type . '-buttons-hover-transition-duration: {{SIZE}}ms;',
			],
			$notice_type . '_buttons_border_type' => [
				$this->get_main_selector( $notice_type ) => '--' . $notice_type . '-border-type: {{VALUE}};',
			],
			$notice_type . '_buttons_border_width' => [
				$this->get_main_selector( $notice_type, 'body', ' .button' ) => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
			$notice_type . '_buttons_border_color' => [
				$this->get_main_selector( $notice_type ) => '--' . $notice_type . '-border-color: {{VALUE}};',
			],
			$notice_type . '_buttons_border_radius' => [
				$this->get_main_selector( $notice_type ) => '--' . $notice_type . '-buttons-border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
			$notice_type . '_buttons_padding' => [
				$this->get_main_selector( $notice_type ) => '--' . $notice_type . '-buttons-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		];
	}

	private function add_notice_text_controls( $notice_type, $selectors ) {
		$this->add_control(
			$notice_type . '_message_text_title',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Notice Text', 'modulix' ),
			]
		);

		$this->add_control(
			$notice_type . '_message_text_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => $selectors[ $notice_type . '_message_text_color' ],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => $notice_type . '_message_text_typography',
				'selector' => $selectors[ $notice_type . '_message_text_typography' ],
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => $notice_type . '_message_text_shadow',
				'selector' => $selectors[ $notice_type . '_message_text_shadow' ],
			]
		);

		$this->add_control(
			$notice_type . '_message_icon_title',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Icon', 'modulix' ),
			]
		);

		$this->add_control(
			$notice_type . '_message_icon_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => $selectors[ $notice_type . '_message_icon_color' ],
			]
		);
	}

	private function add_notice_box_controls( $notice_type, $selectors ) {
		$this->add_control(
			$notice_type . '_notice_box_title',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Notice Box', 'modulix' ),
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => $notice_type . '_notice_box_background',
				'selector' => $selectors[ $notice_type . '_notice_box_background' ],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => $notice_type . '_notice_box_box_shadow',
				'selector' => $selectors[ $notice_type . '_notice_box_box_shadow' ],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => $notice_type . '_notice_box_border',
				'selector' => $selectors[ $notice_type . '_notice_box_border' ],
			]
		);

		$this->add_responsive_control(
			$notice_type . '_notice_box_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => $selectors[ $notice_type . '_notice_box_border_radius' ],
			]
		);
	}

	private function add_notice_button_controls( $notice_type, $selectors ) {
		$this->add_control(
			$notice_type . '_button_title',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Button', 'modulix' ),
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => $notice_type . '_button_typography',
				'selector' => $selectors[ $notice_type . '_button_typography' ],
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => $notice_type . '_button_text_shadow',
				'selector' => $selectors[ $notice_type . '_button_text_shadow' ],
			]
		);

		$this->start_controls_tabs( $notice_type . '_buttons_styles' );

		$this->start_controls_tab( $notice_type . '_buttons_normal_styles', [
			'label' => esc_html__( 'Normal', 'modulix' ),
		] );

		$this->add_control(
			$notice_type . '_buttons_normal_text_color',
			[
				'label' => esc_html__( 'Text Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => $selectors[ $notice_type . '_buttons_normal_text_color' ],
			]
		);

		$button_background_selectors = [
			'image' => [
				'selectors' => [
					'{{SELECTOR}}' => 'background-image: url("{{URL}}") !important;',
				],
			],
			'color' => [
				'selectors' => [
					'{{SELECTOR}}' => 'background-color: {{VALUE}} !important; background-image: none !important',
				],
			],
			'gradient_angle' => [
				'selectors' => [
					'{{SELECTOR}}' => 'background-color: transparent !important; background-image: linear-gradient({{SIZE}}{{UNIT}}, {{color.VALUE}} {{color_stop.SIZE}}{{color_stop.UNIT}}, {{color_b.VALUE}} {{color_b_stop.SIZE}}{{color_b_stop.UNIT}}) !important',
				],
			],
			'gradient_position' => [
				'selectors' => [
					'{{SELECTOR}}' => 'background-color: transparent !important; background-image: radial-gradient(at {{VALUE}}, {{color.VALUE}} {{color_stop.SIZE}}{{color_stop.UNIT}}, {{color_b.VALUE}} {{color_b_stop.SIZE}}{{color_b_stop.UNIT}}) !important',
				],
			],
			'position' => [
				'selectors' => [
					'{{SELECTOR}}' => 'background-position: {{VALUE}} !important;',
				],
			],
			'xpos' => [
				'selectors' => [
					'{{SELECTOR}}' => 'background-position: {{SIZE}}{{UNIT}} {{ypos.SIZE}}{{ypos.UNIT}} !important',
				],
			],
			'ypos' => [
				'selectors' => [
					'{{SELECTOR}}' => 'background-position: {{xpos.SIZE}}{{xpos.UNIT}} {{SIZE}}{{UNIT}} !important',
				],
			],
			'repeat' => [
				'selectors' => [
					'{{SELECTOR}}' => 'background-repeat: {{VALUE}} !important;',
				],
			],
			'size' => [
				'selectors' => [
					'{{SELECTOR}}' => 'background-size: {{VALUE}} !important;',
				],
			],
			'bg_width' => [
				'selectors' => [
					'{{SELECTOR}}' => 'background-size: {{SIZE}}{{UNIT}} auto !important',
				],
			],
		];

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => $notice_type . '_buttons_normal_background',
				'selector' => $selectors[ $notice_type . '_buttons_normal_background' ],
				'fields_options' => $button_background_selectors,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => $notice_type . '_buttons_normal_box_shadow',
				'selector' => $selectors[ $notice_type . '_buttons_normal_box_shadow' ],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( $notice_type . '_buttons_hover_styles', [
			'label' => esc_html__( 'Hover', 'modulix' ),
		] );

		$this->add_control(
			$notice_type . '_buttons_hover_text_color',
			[
				'label' => esc_html__( 'Text Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => $selectors[ $notice_type . '_buttons_hover_text_color' ],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => $notice_type . '_buttons_hover_background',
				'selector' => $selectors[ $notice_type . '_buttons_hover_background' ],
				'fields_options' => $button_background_selectors,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => $notice_type . '_buttons_focus_box_shadow',
				'selector' => $selectors[ $notice_type . '_buttons_focus_box_shadow' ],
			]
		);

		$this->add_control(
			$notice_type . '_buttons_hover_border_color',
			[
				'label' => esc_html__( 'Border Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => $selectors[ $notice_type . '_buttons_hover_border_color' ],
				'condition' => [
					$notice_type . '_buttons_border_type!' => 'none',
				],
			]
		);

		$this->add_control(
			$notice_type . '_buttons_hover_transition_duration',
			[
				'label' => esc_html__( 'Transition Duration', 'modulix' ) . ' (ms)',
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 3000,
						'step' => 100,
					],
				],
				'selectors' => $selectors[ $notice_type . '_buttons_hover_transition_duration' ],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			$notice_type . '_buttons_border_type',
			[
				'label' => esc_html__( 'Border Type', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'none' => esc_html__( 'None', 'modulix' ),
					'solid' => esc_html__( 'Solid', 'modulix' ),
					'double' => esc_html__( 'Double', 'modulix' ),
					'dotted' => esc_html__( 'Dotted', 'modulix' ),
					'dashed' => esc_html__( 'Dashed', 'modulix' ),
					'groove' => esc_html__( 'Groove', 'modulix' ),
				],
				'selectors' => $selectors[ $notice_type . '_buttons_border_type' ],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			$notice_type . '_buttons_border_width',
			[
				'label' => esc_html__( 'Width', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors' => $selectors[ $notice_type . '_buttons_border_width' ],
				'condition' => [
					$notice_type . '_buttons_border_type!' => 'none',
				],
			]
		);

		$this->add_control(
			$notice_type . '_buttons_border_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => $selectors[ $notice_type . '_buttons_border_color' ],
				'condition' => [
					$notice_type . '_buttons_border_type!' => 'none',
				],
			]
		);

		$this->add_responsive_control(
			$notice_type . '_buttons_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => $selectors[ $notice_type . '_buttons_border_radius' ],
			]
		);

		$this->add_responsive_control(
			$notice_type . '_buttons_padding',
			[
				'label' => esc_html__( 'Padding', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors' => $selectors[ $notice_type . '_buttons_padding' ],
			]
		);
	}

	public function on_save( $data ) {
		if (
			! isset( $data['settings']['post_status'] ) ||
			Document::STATUS_PUBLISH !== $data['settings']['post_status'] ||
			// Should check for the current action to avoid infinite loop
			// when updating options like: "blogname" and "blogdescription".
			strpos( current_action(), 'update_option_' ) === 0
		) {
			return;
		}

		$ec_wc_key_mapping = [
			'woocommerce_cart_page_id' => 'woocommerce_cart_page_id',
			'woocommerce_checkout_page_id' => 'woocommerce_checkout_page_id',
			'woocommerce_myaccount_page_id' => 'woocommerce_myaccount_page_id',
			'woocommerce_terms_page_id' => 'woocommerce_terms_page_id',
			'woocommerce_purchase_summary_page_id' => 'elementor_woocommerce_purchase_summary_page_id',
			'woocommerce_shop_page_id' => 'woocommerce_shop_page_id',
		];
		foreach ( $ec_wc_key_mapping as $ec_key => $wc_key ) {
			if ( array_key_exists( $ec_key, $data['settings'] ) ) {
				$value = $data['settings'][ $ec_key ] ? $data['settings'][ $ec_key ] : '';
				update_option( $wc_key, $value );
			}
		}
	}

	private function register_woocommerce_pages_controls() {
		$this->add_control(
			'woocommerce_pages_intro',
			[
				'raw' => esc_html__( 'Select the pages you want to use as your default WooCommerce shop pages', 'modulix' ),
				'type' => Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-descriptor',
			]
		);

		$autocomplete = [
			'object' => QueryModule::QUERY_OBJECT_POST,
			'query' => [
				'post_type' => [ 'page' ],
			],
		];

		$this->add_control(
			'woocommerce_cart_page_id',
			[
				'label' => esc_html__( 'Cart', 'modulix' ),
				'type' => QueryModule::QUERY_CONTROL_ID,
				'select2options' => [
					'placeholder' => esc_html__( 'Select a page', 'modulix' ),
				],
				'autocomplete' => $autocomplete,
				'default' => get_option( 'woocommerce_cart_page_id' ),
			]
		);

		$this->add_control(
			'woocommerce_checkout_page_id',
			[
				'label' => esc_html__( 'Checkout', 'modulix' ),
				'type' => QueryModule::QUERY_CONTROL_ID,
				'select2options' => [
					'placeholder' => esc_html__( 'Select a page', 'modulix' ),
				],
				'autocomplete' => $autocomplete,
				'default' => get_option( 'woocommerce_checkout_page_id' ),
			]
		);

		$this->add_control(
			'woocommerce_myaccount_page_id',
			[
				'label' => esc_html__( 'My Account', 'modulix' ),
				'type' => QueryModule::QUERY_CONTROL_ID,
				'select2options' => [
					'placeholder' => esc_html__( 'Select a page', 'modulix' ),
				],
				'autocomplete' => $autocomplete,
				'default' => get_option( 'woocommerce_myaccount_page_id' ),
			]
		);

		$this->add_control(
			'woocommerce_terms_page_id',
			[
				'label' => esc_html__( 'Terms & Conditions', 'modulix' ),
				'type' => QueryModule::QUERY_CONTROL_ID,
				'select2options' => [
					'placeholder' => esc_html__( 'Select a page', 'modulix' ),
				],
				'autocomplete' => $autocomplete,
				'default' => get_option( 'woocommerce_terms_page_id' ),
			]
		);

		$this->add_control(
			'woocommerce_purchase_summary_page_id',
			[
				'label' => esc_html__( 'Purchase Summary', 'modulix' ),
				'type' => QueryModule::QUERY_CONTROL_ID,
				'select2options' => [
					'placeholder' => esc_html__( 'Select a page', 'modulix' ),
				],
				'autocomplete' => $autocomplete,
				'default' => get_option( 'elementor_woocommerce_purchase_summary_page_id' ), // This is not in WC core. This is a custom page added by Elementor.
			]
		);

		$this->add_control(
			'woocommerce_shop_page_id',
			[
				'label' => esc_html__( 'Shop', 'modulix' ),
				'type' => QueryModule::QUERY_CONTROL_ID,
				'select2options' => [
					'placeholder' => esc_html__( 'Select a page', 'modulix' ),
				],
				'autocomplete' => $autocomplete,
				'default' => get_option( 'woocommerce_shop_page_id' ),
			]
		);

		$this->add_control(
			'woocommerce_pages_notice',
			[
				'type' => Controls_Manager::ALERT,
				'alert_type' => 'info',
				'content' => esc_html__( 'Note: Changes you make here will also be reflected in the WooCommerce settings on your WP dashboard', 'modulix' ),
			]
		);
	}

	private function register_woocommerce_notices_controls() {
		$this->add_control(
			'woocommerce_notices_intro',
			[
				'raw' => esc_html__( 'Here\'s where you can customize how notices form WooCommerce will appear for your customers', 'modulix' ),
				'type' => Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-descriptor',
			]
		);

		$this->add_control(
			'woocommerce_notices_elements',
			[
				'label' => esc_html__( 'Notice Type', 'modulix' ),
				'type' => Controls_Manager::SELECT2,
				'multiple' => true,
				'options' => [
					'wc_error' => esc_html__( 'Error Notices', 'modulix' ),
					'wc_message' => esc_html__( 'Message Notices', 'modulix' ),
					'wc_info' => esc_html__( 'Info Notices', 'modulix' ),
				],
				'render_type' => 'ui',
				'label_block' => true,
				'frontend_available' => true,
				'default' => [],
			]
		);
	}

}
