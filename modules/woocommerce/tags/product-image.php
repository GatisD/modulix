<?php
namespace Modulix\Modules\Woocommerce\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/tags/product-image.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\Woocommerce\Module;
use Modulix\Modules\Woocommerce\Tags\Traits\Tag_Product_Id;
use Modulix\Modules\Woocommerce\Traits\Product_Id_Trait;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Product_Image extends Base_Data_Tag {

	use Tag_Product_Id;
	use Product_Id_Trait;

	public function get_name() {
		return 'woocommerce-product-image-tag';
	}

	public function get_title() {
		return esc_html__( 'Product Image', 'modulix' );
	}

	protected function register_controls() {
		$this->add_product_id_control();
	}

	public function get_group() {
		return Module::WOOCOMMERCE_GROUP;
	}

	public function get_categories() {
		return [ \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY ];
	}

	public function get_value( array $options = [] ) {
		$product = $this->get_product( $this->get_settings( 'product_id' ) );

		if ( ! $product ) {
			return [];
		}

		$image_id = $product->get_image_id();

		if ( ! $image_id ) {
			return [];
		}

		$src = wp_get_attachment_image_src( $image_id, 'full' );

		return [
			'id' => $image_id,
			'url' => $src[0],
		];
	}
}
