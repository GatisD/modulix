<?php
namespace Modulix\Modules\CallToAction;
// Modulix: derived from Elementor Pro 4.0.4, modules/call-to-action/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Base\Module_Base;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Module extends Module_Base {

	public function __construct() {
		parent::__construct();

		add_action( 'elementor/frontend/after_register_styles', [ $this, 'register_styles' ] );
		add_action( 'elementor/preview/enqueue_styles', [ $this, 'enqueue_preview_styles' ] );
	}

	public function get_widgets() {
		return [
			'Call_To_Action',
		];
	}

	public function get_name() {
		return 'call-to-action';
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
	 * At build time, Elementor compiles `/modules/call-to-action/assets/scss/frontend.scss`
	 * to `/assets/css/widget-call-to-action.min.css`.
	 *
	 * @return void
	 */
	public function register_styles() {
		$direction_suffix = is_rtl() ? '-rtl' : '';
		$has_custom_breakpoints = Plugin::elementor()->breakpoints->has_custom_breakpoints();

		wp_register_style(
			'widget-call-to-action',
			Plugin::get_frontend_file_url( "widget-call-to-action{$direction_suffix}.min.css", $has_custom_breakpoints ),
			[ 'elementor-frontend' ],
			$has_custom_breakpoints ? null : MODULIX_VERSION
		);

		wp_register_style(
			'e-transitions',
			$this->get_css_assets_url( 'transitions', 'assets/css/conditionals/', true ),
			[],
			MODULIX_VERSION
		);

		wp_register_style(
			'e-ribbon',
			$this->get_css_assets_url( 'ribbon', 'assets/css/conditionals/', true ),
			[],
			MODULIX_VERSION
		);
	}

	public function enqueue_preview_styles() {
		wp_enqueue_style( 'e-ribbon' );
	}
}
