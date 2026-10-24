<?php
namespace Modulix\Modules\Woocommerce\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/widgets/product-stock.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Product_Stock extends Base_Widget {

	public function get_name() {
		return 'woocommerce-product-stock';
	}

	public function get_title() {
		return esc_html__( 'Product Stock', 'modulix' );
	}

	public function get_icon() {
		return 'eicon-product-stock';
	}

	public function get_keywords() {
		return [ 'woocommerce', 'shop', 'store', 'stock', 'quantity', 'product' ];
	}

	public function has_widget_inner_wrapper(): bool {
		return ! Plugin::elementor()->experiments->is_feature_active( 'e_optimized_markup' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_product_stock_style',
			[
				'label' => esc_html__( 'Style', 'modulix' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'wc_style_warning',
			[
				'type' => Controls_Manager::ALERT,
				'alert_type' => 'info',
				'content' => esc_html__( 'The style of this widget is often affected by your theme and plugins. If you experience any such issue, try to switch to a basic theme and deactivate related plugins.', 'modulix' ),
			]
		);

		$this->add_control(
			'text_color',
			[
				'label' => esc_html__( 'Text Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'.woocommerce {{WRAPPER}} .stock' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'text_typography',
				'selector' => '.woocommerce {{WRAPPER}} .stock',
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

		// PHPCS - the method wc_get_stock_html is safe.
		echo wc_get_stock_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function render_plain_content() {}

	public function get_group_name() {
		return 'woocommerce';
	}
}
