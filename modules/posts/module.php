<?php
namespace Modulix\Modules\Posts;
// Modulix: derived from Elementor Pro 4.0.4, modules/posts/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): posts-widget data endpoint not registered: no front-end consumer (load more fetches the next page).

use Elementor\Utils;
use Modulix\Base\Module_Base;
use Modulix\Modules\Posts\Widgets\Posts_Base;
use Modulix\Plugin;
use Modulix\Modules\Posts\Traits\Pagination_Trait;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Module extends Module_Base {
	use Pagination_Trait;

	public function get_name() {
		return 'posts';
	}

	public function get_widgets() {
		return [
			'Posts',
			'Portfolio',
		];
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
	 * At build time, Elementor compiles `/modules/posts/assets/scss/frontend.scss`
	 * to `/assets/css/widget-posts.min.css`.
	 *
	 * @return void
	 */
	public function register_styles() {
		wp_register_style(
			'widget-posts',
			$this->get_css_assets_url( 'widget-posts', null, true, true ),
			[ 'elementor-frontend' ],
			MODULIX_VERSION
		);
	}

	/**
	 * Fix WP 5.5 pagination issue.
	 *
	 * Return true to mark that it's handled and avoid WP to set it as 404.
	 *
	 * @see https://github.com/elementor/elementor/issues/12126
	 * @see https://core.trac.wordpress.org/ticket/50976
	 *
	 * Based on the logic at \WP::handle_404.
	 *
	 * @param $handled - Default false.
	 * @param $wp_query
	 *
	 * @return bool
	 */
	public function allow_posts_widget_pagination( $handled, $wp_query ) {
		// Check it's not already handled and it's a single paged query.
		if ( $handled || empty( $wp_query->query_vars['page'] ) || ! is_singular() || empty( $wp_query->post ) ) {
			return $handled;
		}

		$document = Plugin::elementor()->documents->get( $wp_query->post->ID );

		return $this->is_valid_pagination( $document->get_elements_data(), $wp_query->query_vars['page'] );
	}

	public function __construct() {
		parent::__construct();


		add_filter( 'pre_handle_404', [ $this, 'allow_posts_widget_pagination' ], 10, 2 );

		add_action( 'elementor/frontend/after_register_styles', [ $this, 'register_styles' ] );
	}

}
