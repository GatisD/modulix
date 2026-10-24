<?php
namespace Modulix\Modules\Woocommerce\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/tags/product-terms.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Product_Terms extends Base_Tag {
	public function get_name() {
		return 'woocommerce-product-terms-tag';
	}

	public function get_title() {
		return esc_html__( 'Product Terms', 'modulix' );
	}

	protected function register_advanced_section() {
		parent::register_advanced_section();

		$this->update_control(
			'before',
			[
				'default' => esc_html__( 'Categories', 'modulix' ) . ': ',
			]
		);
	}

	protected function register_controls() {
		$taxonomy_filter_args = [
			'show_in_nav_menus' => true,
			'object_type' => [ 'product' ],
		];

		$taxonomies = get_taxonomies( $taxonomy_filter_args, 'objects' );

		$options = [];

		foreach ( $taxonomies as $taxonomy => $object ) {
			$options[ $taxonomy ] = $object->label;
		}

		$this->add_control(
			'taxonomy',
			[
				'label' => esc_html__( 'Taxonomy', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => $options,
				'default' => 'product_cat',
			]
		);

		$this->add_control(
			'separator',
			[
				'label' => esc_html__( 'Separator', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'default' => ', ',
			]
		);

		$this->add_product_id_control();
	}

	public function render() {
		$settings = $this->get_settings_for_display();

		$product = $this->get_product( $settings['product_id'] );

		if ( ! $product ) {
			return;
		}

		$value = get_the_term_list( $product->get_id(), $settings['taxonomy'], '', $settings['separator'] );

		echo wp_kses_post( $value );
	}
}
