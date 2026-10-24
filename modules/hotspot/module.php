<?php
namespace Modulix\Modules\Hotspot;
// Modulix: derived from Elementor Pro 4.0.4, modules/hotspot/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
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

	public function get_widgets() {
		return [
			'Hotspot',
		];
	}

	public function get_name() {
		return 'hotspot';
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
	 * At build time, Elementor compiles `/modules/hotspot/assets/scss/frontend.scss`
	 * to `/assets/css/widget-hotspot.min.css`.
	 *
	 * @return void
	 */
	public function register_styles() {
		wp_register_style(
			'widget-hotspot',
			$this->get_css_assets_url( 'widget-hotspot', null, true, true ),
			[ 'elementor-frontend' ],
			MODULIX_VERSION
		);
	}
}
