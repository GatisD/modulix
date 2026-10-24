<?php
namespace Modulix\Modules\Woocommerce\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/widgets/product-additional-information.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Product_Additional_Information extends Base_Widget {

	public function get_name() {
		return 'woocommerce-product-additional-information';
	}

	public function get_title() {
		return esc_html__( 'Additional Information', 'modulix' );
	}

	public function get_icon() {
		return ' eicon-product-info';
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
		return [ 'widget-woocommerce-product-additional-information' ];
	}

	protected function register_controls() {

		$this->start_controls_section( 'section_additional_info_style', [
			'label' => esc_html__( 'General', 'modulix' ),
			'tab' => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control(
			'show_heading',
			[
				'label' => esc_html__( 'Heading', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'modulix' ),
				'label_off' => esc_html__( 'Hide', 'modulix' ),
				'render_type' => 'ui',
				'return_value' => 'yes',
				'default' => 'yes',
				'prefix_class' => 'elementor-show-heading-',
			]
		);

		$this->add_control(
			'heading_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'.woocommerce {{WRAPPER}} h2' => 'color: {{VALUE}}',
				],
				'condition' => [
					'show_heading!' => '',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'heading_typography',
				'selector' => '.woocommerce {{WRAPPER}} h2',
				'condition' => [
					'show_heading!' => '',
				],
			]
		);

		$this->add_control(
			'content_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'.woocommerce {{WRAPPER}} .shop_attributes' => 'color: {{VALUE}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'content_typography',
				'selector' => '.woocommerce {{WRAPPER}} .shop_attributes',
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		global $product;
		$product = $this->get_product();

		if ( ! $product ) {
			return;
		}

		wc_get_template( 'single-product/tabs/additional-information.php' );
	}

	public function render_plain_content() {}

	public function get_group_name() {
		return 'woocommerce';
	}
}
