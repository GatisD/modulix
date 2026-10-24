<?php
namespace Modulix\Modules\Woocommerce\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/tags/product-sale.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Product_Sale extends Base_Tag {
	public function get_name() {
		return 'woocommerce-product-sale-tag';
	}

	public function get_title() {
		return esc_html__( 'Product Sale', 'modulix' );
	}

	protected function register_controls() {
		$this->add_control( 'text', [
			'label' => esc_html__( 'Text', 'modulix' ),
			'type' => Controls_Manager::TEXT,
			'default' => esc_html__( 'Sale!', 'modulix' ),
		] );

		$this->add_product_id_control();
	}

	public function render() {
		$settings = $this->get_settings_for_display();

		$product = $this->get_product( $settings['product_id'] );

		if ( ! $product ) {
			return;
		}

		$value = '';

		if ( $product->is_on_sale() ) {
			$value = $settings['text'];
		}

		echo wp_kses_post( $value );
	}
}
