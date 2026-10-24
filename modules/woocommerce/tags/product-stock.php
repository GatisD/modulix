<?php
namespace Modulix\Modules\Woocommerce\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/tags/product-stock.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Product_Stock extends Base_Tag {
	public function get_name() {
		return 'woocommerce-product-stock-tag';
	}

	public function get_title() {
		return esc_html__( 'Product Stock', 'modulix' );
	}

	public function render() {
		$settings = $this->get_settings_for_display();

		$product = $this->get_product( $settings['product_id'] );

		if ( ! $product ) {
			return;
		}

		if ( 'yes' === $settings['show_text'] ) {
			$value = wc_get_stock_html( $product );
		} else {
			$value = (int) $product->get_stock_quantity();
		}

		// PHPCS - `wc_get_stock_html` is safe, and `get_stock_quantity` protected with (int).
		echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	protected function register_controls() {
		$this->add_control(
			'show_text',
			[
				'label' => esc_html__( 'Show Text', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'label_on' => esc_html__( 'Show', 'modulix' ),
				'label_off' => esc_html__( 'Hide', 'modulix' ),
			]
		);

		$this->add_product_id_control();
	}
}
