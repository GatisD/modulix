<?php
namespace Modulix\Modules\Woocommerce\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/tags/product-sku.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Product_SKU extends Base_Tag {
	public function get_name() {
		return 'woocommerce-product-sku-tag';
	}

	public function get_title() {
		return esc_html__( 'Product SKU', 'modulix' );
	}

	protected function register_controls() {
		$this->add_product_id_control();
	}

	public function render() {
		$product = $this->get_product( $this->get_settings( 'product_id' ) );

		if ( ! $product ) {
			return;
		}

		$value = '';

		if ( $product->get_sku() ) {
			$value = $product->get_sku();
		}

		echo esc_html( $value );
	}
}
