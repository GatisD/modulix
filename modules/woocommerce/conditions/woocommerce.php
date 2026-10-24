<?php
namespace Modulix\Modules\Woocommerce\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/conditions/woocommerce.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\ThemeBuilder as ThemeBuilder;
use Modulix\Modules\Woocommerce\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Woocommerce extends ThemeBuilder\Conditions\Condition_Base {

	public static function get_type() {
		return 'woocommerce';
	}

	public function get_name() {
		return 'woocommerce';
	}

	public function get_label() {
		return esc_html__( 'WooCommerce', 'modulix' );
	}

	public function get_all_label() {
		return esc_html__( 'Entire Shop', 'modulix' );
	}

	public function register_sub_conditions() {
		$product_archive = new Product_Archive();

		$product_single = new ThemeBuilder\Conditions\Post( [
			'post_type' => 'product',
		] );

		$this->register_sub_condition( $product_archive );
		$this->register_sub_condition( $product_single );
	}

	public function check( $args ) {
		return is_woocommerce() || Module::is_product_search();
	}
}
