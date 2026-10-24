<?php
namespace Modulix\Modules\Woocommerce\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/tags/product-short-description.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Product_Short_Description extends Base_Tag {
	public function get_name() {
		return 'woocommerce-product-short-description-tag';
	}

	public function get_title() {
		return esc_html__( 'Product Short Description', 'modulix' );
	}

	protected function register_controls() {
		$this->add_product_id_control();
	}

	public function render() {
		$product = $this->get_product( $this->get_settings( 'product_id' ) );

		if ( ! $product ) {
			return;
		}

		echo wp_kses_post( $product->get_short_description() );
	}
}
