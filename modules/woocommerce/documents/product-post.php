<?php
namespace Modulix\Modules\Woocommerce\Documents;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/documents/product-post.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\DocumentTypes\Post;
use Elementor\Utils;
use Modulix\Core\Behaviors\Feature_Lock;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Product_Post extends Post {

	public static function get_properties() {
		$properties = parent::get_properties();

		$properties['cpt'] = [
			'product',
		];

		return $properties;
	}

	/**
	 * @since  2.0.0
	 * @access public
	 */
	public function get_name() {
		return 'product-post';
	}

	/**
	 * @since  2.0.0
	 * @access public
	 * @static
	 */
	public static function get_title() {
		return esc_html__( 'Product Post', 'modulix' );
	}

	public static function get_plural_title() {
		return esc_html__( 'Product Posts', 'modulix' );
	}

	public static function get_lock_behavior_v2() {
		return new Feature_Lock( [
			'type' => static::get_type(),
		] );
	}

	protected static function get_editor_panel_categories() {
		$categories = parent::get_editor_panel_categories();

		unset( $categories['theme-elements-single'] );

		$categories = Utils::array_inject(
			$categories,
			'theme-elements',
			[
				'woocommerce-elements-single' => [
					'title' => esc_html__( 'Product', 'modulix' ),
					'active' => false,
				],
			]
		);

		return $categories;
	}

	protected function get_remote_library_config() {
		$config = parent::get_remote_library_config();

		$config['category'] = 'single product';

		return $config;
	}
}
