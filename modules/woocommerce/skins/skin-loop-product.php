<?php
namespace Modulix\Modules\WooCommerce\Skins;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/skins/skin-loop-product.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\LoopBuilder\Skins\Skin_Loop_Base;
use Modulix\Modules\LoopBuilder\Module as Loop_Module;
use Modulix\Modules\LoopBuilder\Widgets\Base as Loop_Widget_Base;
use Modulix\Modules\Woocommerce\Traits\Products_Trait;
use Modulix\Modules\Woocommerce\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Loop Products
 *
 * Skin for Product queries in Loop widgets.
 *
 * @since 3.8.0
 */
class Skin_Loop_Product extends Skin_Loop_Base {

	use Products_Trait;

	public function get_id() {
		return Module::LOOP_PRODUCT_SKIN_ID;
	}

	public function get_title() {
		return esc_html__( 'Products', 'modulix' );
	}

	public function render() {
		$this->parent->add_render_attribute( '_wrapper', 'class', 'woocommerce' );

		global $wp_query;
		$wp_query->is_loop_product = true;

		parent::render();

		$wp_query->is_loop_product = null;
	}

	/**
	 * Register Query Controls
	 *
	 * Registers the controls for the query used by the Loop.
	 *
	 * @since 3.8.0
	 */
	public function register_query_controls( Loop_Widget_Base $widget ) {
		$this->parent = $widget;

		$this->add_query_controls( Loop_Module::QUERY_ID );
	}

	protected function render_post() {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			$product = wc_get_product( get_the_ID() );
		}

		parent::render_post();
	}
}
