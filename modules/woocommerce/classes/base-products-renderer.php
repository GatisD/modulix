<?php
namespace Modulix\Modules\Woocommerce\Classes;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/classes/base-products-renderer.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class Base_Products_Renderer extends \WC_Shortcode_Products {

	/**
	 * Override original `get_content` that returns an HTML wrapper even if no results found.
	 *
	 * @return string Products HTML
	 */
	public function get_content() {
		$result = $this->get_query_results();

		if ( empty( $result->total ) ) {
			return '';
		}

		return parent::get_content();
	}
}
