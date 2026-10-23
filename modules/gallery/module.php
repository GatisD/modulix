<?php

namespace Modulix\Modules\Gallery;
// Modulix: derived from Elementor Pro 4.0.4, modules/gallery/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Base\Module_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Module extends Module_Base {

	public function __construct() {
		parent::__construct();

		add_action( 'elementor/frontend/after_register_styles', [ $this, 'register_styles' ] );
	}

	/**
	 * Get module name.
	 *
	 * Retrieve the module name.
	 *
	 * @since  2.7.0
	 * @access public
	 *
	 * @return string Module name.
	 */
	public function get_name() {
		return 'gallery';
	}

	public function get_widgets() {
		return [
			'gallery',
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
	 * At build time, Elementor compiles `/modules/gallery/assets/scss/frontend.scss`
	 * to `/assets/css/widget-gallery.min.css`.
	 *
	 * @return void
	 */
	public function register_styles() {
		wp_register_style(
			'widget-gallery',
			$this->get_css_assets_url( 'widget-gallery', null, true, true ),
			[ 'elementor-frontend' ],
			MODULIX_VERSION
		);

		wp_register_style(
			'e-transitions',
			$this->get_css_assets_url( 'transitions', 'assets/css/conditionals/', true ),
			[],
			MODULIX_VERSION
		);
	}
}
