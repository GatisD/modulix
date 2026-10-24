<?php
namespace Modulix\Modules\Woocommerce\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/conditions/product-search.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\ThemeBuilder as ThemeBuilder;
use Modulix\Modules\Woocommerce\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Product_Search extends ThemeBuilder\Conditions\Condition_Base {

	public static function get_type() {
		return 'archive';
	}

	public function get_name() {
		return 'product_search';
	}

	public static function get_priority() {
		return 40;
	}

	public function get_label() {
		return esc_html__( 'Search Results', 'modulix' );
	}

	public function check( $args ) {
		return Module::is_product_search();
	}
}
