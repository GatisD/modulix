<?php
namespace Modulix\Modules\Woocommerce\Tags\Traits;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/tags/traits/tag-product-id.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\QueryControl\Module as QueryControlModule;

trait Tag_Product_Id {
	public function add_product_id_control() {
		$this->add_control(
			'product_id',
			[
				'label' => esc_html__( 'Product', 'modulix' ),
				'type' => QueryControlModule::QUERY_CONTROL_ID,
				'options' => [],
				'label_block' => true,
				'autocomplete' => [
					'object' => QueryControlModule::QUERY_OBJECT_POST,
					'query' => [
						'post_type' => [ 'product' ],
					],
				],
				// Since we're using the `wc_get_product` method to retrieve products, when no product selected manually
				// by the dynamic tag - the default should be `false` so the method will use the product id given in the
				// http request instead.
				'default' => false,
			]
		);
	}
}
