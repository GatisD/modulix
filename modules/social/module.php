<?php
namespace Modulix\Modules\Social;
// Modulix: derived from Elementor Pro 4.0.4, modules/social/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Base\Module_Base;
use Modulix\Modules\Social\Classes\Facebook_SDK_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Module extends Module_Base {
	const URL_TYPE_CURRENT_PAGE = 'current_page';
	const URL_TYPE_CUSTOM = 'custom';
	const URL_FORMAT_PLAIN = 'plain';
	const URL_FORMAT_PRETTY = 'pretty';

	public function get_widgets() {
		return [
			'Facebook_Button',
			'Facebook_Comments',
			'Facebook_Embed',
			'Facebook_Page',
		];
	}

	public function get_name() {
		return 'social';
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
	 * At build time, Elementor compiles `/modules/social/assets/scss/frontend.scss`
	 * to `/assets/css/widget-social.min.css`.
	 *
	 * @return void
	 */
	public function register_styles() {
		wp_register_style(
			'widget-social',
			$this->get_css_assets_url( 'widget-social', null, true, true ),
			[ 'elementor-frontend' ],
			MODULIX_VERSION
		);
	}

	public function __construct() {
		parent::__construct();

		$this->add_component( 'facebook_sdk', new Facebook_SDK_Manager() );

		add_action( 'elementor/frontend/after_register_styles', [ $this, 'register_styles' ] );
	}
}
