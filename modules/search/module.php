<?php
namespace Modulix\Modules\Search;
// Modulix: derived from Elementor Pro 4.0.4, modules/search/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Utils;
use Modulix\Core\Utils as Pro_Utils;
use Modulix\Base\Module_Base;
use Modulix\Modules\Search\Data\Controller;
use Modulix\Modules\QueryControl\Classes\Elementor_Post_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Module extends Module_Base {

	const FEATURE_ID = 'search';

	public function __construct() {
		parent::__construct();

		add_action( 'elementor/frontend/after_register_styles', [ $this, 'register_styles' ] );
		add_action( 'pre_get_posts', [ $this, 'set_query' ] );
		add_filter( 'elementor_pro/editor/localize_settings', [ $this, 'add_localize_data' ] );

		new Controller();
	}

	public function get_name() {
		return self::FEATURE_ID;
	}

	protected function get_widgets() {
		return [ 'Search' ];
	}

	/**
	 * Get the base URL for assets.
	 *
	 * @return string
	 */
	public function get_assets_base_url(): string {
		return MODULIX_URL;
	}

	/**
	 * Register styles.
	 *
	 * At build time, Elementor compiles `/modules/search/assets/scss/frontend.scss`
	 * to `/assets/css/widget-search.min.css`.
	 *
	 * @return void
	 */
	public function register_styles() {
		wp_register_style(
			'widget-search',
			$this->get_css_assets_url( 'widget-search', null, true, true ),
			[ 'elementor-frontend' ],
			MODULIX_VERSION
		);
	}

	public function add_localize_data( $config ) {

		$config['eSearch'] = [
			'nonce' => wp_create_nonce( 'wp_rest' ),
		];

		return $config;
	}

	public function set_query( $query ) {
		$e_search_props = Utils::get_super_global_value( $_GET, 'e_search_props' );
		$search_term = Utils::get_super_global_value( $_GET, 's' );

		if ( empty( $e_search_props ) || ! isset( $search_term ) || is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$formatted_query_vars = $this->get_query_based_on_widget_props( $e_search_props );

		if ( ! $formatted_query_vars ) {
			return;
		}

		foreach ( $formatted_query_vars as $key => $value ) {
			$query->set( $key, $value );
		}
	}

	private function get_query_based_on_widget_props( $e_search_props ) {
		list( $widget_id, $post_id ) = explode( '-', $e_search_props );
		$widget_instance = Pro_Utils::create_widget_instance_from_db( $post_id, $widget_id );

		if ( ! $widget_instance ) {
			return null;
		}

		$query_vars = ( new Elementor_Post_Query( $widget_instance, 'search_query' ) )->get_query_args();
		$query_vars['posts_per_page'] = -1;

		return $query_vars;
	}
}
