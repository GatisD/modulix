<?php
namespace Modulix\Modules\Woocommerce\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/conditions/product-archive.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\ThemeBuilder as ThemeBuilder;
use Modulix\Modules\Woocommerce\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Product_Archive extends ThemeBuilder\Conditions\Condition_Base {

	private $post_type = 'product';
	private $post_taxonomies;

	public function __construct( array $data = [] ) {
		$taxonomies = get_object_taxonomies( $this->post_type, 'objects' );
		$this->post_taxonomies = wp_filter_object_list( $taxonomies, [
			'public' => true,
			'show_in_nav_menus' => true,
		] );

		parent::__construct( $data );
	}

	public static function get_type() {
		return 'archive';
	}

	public function get_name() {
		return 'product_archive';
	}

	public static function get_priority() {
		return 40;
	}

	public function get_label() {
		return esc_html__( 'Product Archive', 'modulix' );
	}

	public function get_all_label() {
		return esc_html__( 'All Product Archives', 'modulix' );
	}

	public function register_sub_conditions() {
		$this->register_sub_condition( new Shop_page() );
		$this->register_sub_condition( new Product_Search() );

		foreach ( $this->post_taxonomies as $slug => $object ) {
			$condition = new ThemeBuilder\Conditions\Taxonomy( [
				'object' => $object,
			] );
			$this->register_sub_condition( $condition );
		}
	}

	public function check( $args ) {
		return is_shop() || is_product_taxonomy() || Module::is_product_search();
	}
}
