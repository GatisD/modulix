<?php
namespace Modulix\Modules\Woocommerce\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/tags/product-gallery.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\Woocommerce\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Product_Gallery extends Base_Data_Tag {
	public function get_name() {
		return 'woocommerce-product-gallery-tag';
	}

	public function get_title() {
		return esc_html__( 'Product Gallery', 'modulix' );
	}

	public function get_group() {
		return Module::WOOCOMMERCE_GROUP;
	}

	public function get_categories() {
		return [ \Elementor\Modules\DynamicTags\Module::GALLERY_CATEGORY ];
	}

	public function get_value( array $options = [] ) {
		$product = $this->get_product( $this->get_settings( 'product_id' ) );

		if ( ! $product ) {
			return [];
		}

		$value = [];

		$attachment_ids = $product->get_gallery_image_ids();

		foreach ( $attachment_ids as $attachment_id ) {
			$value[] = [
				'id' => $attachment_id,
			];
		}

		return $value;
	}
}
