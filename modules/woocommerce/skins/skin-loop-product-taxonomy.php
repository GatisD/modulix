<?php
namespace Modulix\Modules\WooCommerce\Skins;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/skins/skin-loop-product-taxonomy.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\LoopBuilder\Providers\Taxonomy_Loop_Provider;
use Modulix\Modules\LoopBuilder\Skins\Skin_Loop_Taxonomy_Base;
use Modulix\Modules\WooCommerce\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Skin_Loop_Product_Taxonomy extends Skin_Loop_Taxonomy_Base {
	protected $post_type = 'product';

	public function get_id() {
		return Module::LOOP_PRODUCT_TAXONOMY_SKIN_ID;
	}

	public function get_title() {
		return esc_html__( 'Product Taxonomy', 'modulix' );
	}

	public function render() {
		$this->parent->add_render_attribute( '_wrapper', 'class', 'woocommerce' );
		parent::render();
	}

	protected function get_default_source_option() {
		return Taxonomy_Loop_Provider::PRODUCT_CATEGORY_TAXONOMY;
	}
}
