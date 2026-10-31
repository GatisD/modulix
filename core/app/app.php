<?php
namespace Modulix\Core\App;
// Modulix: derived from Elementor Pro 4.0.4, core/app/app.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-24): site-editor only; local generated assets; Connect, cloud, onboarding and kit import/export components removed.

use Elementor\Core\Base\App as BaseApp;
use Modulix\App as App_Module;
use Modulix\Core\App\Modules\SiteEditor\Module as SiteEditor;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class App extends BaseApp {
	/**
	 * Get module name.
	 *
	 * Retrieve the module name.
	 *
	 * @since 3.0.0
	 * @access public
	 *
	 * @return string Module name.
	 */
	public function get_name() {
		return 'app-pro';
	}

	public function init() {
		$this->enqueue_assets();
	}

	public function set_menu_url() {
		$menu_url = Plugin::elementor()->app->get_base_url() . '#/site-editor';

		foreach ( App_Module::coreAppSettings( $menu_url ) as $key => $value ) {
			Plugin::elementor()->app->set_settings( $key, $value );
		}
	}

	protected function get_init_settings() {
		return App_Module::baseConfig( App_Module::mode(), MODULIX_URL );
	}

	private function enqueue_assets() {
		$assets = App_Module::assetDefinitions( App_Module::mode(), MODULIX_ASSETS_URL, MODULIX_VERSION );
		if ( ! $assets ) {
			return;
		}

		wp_enqueue_style(
			App_Module::HANDLE,
			$assets['style']['src'],
			$assets['style']['dependencies'],
			$assets['style']['version']
		);

		wp_enqueue_script(
			App_Module::HANDLE,
			$assets['script']['src'],
			$assets['script']['dependencies'],
			$assets['script']['version'],
			true
		);

		\Modulix\I18n::setScriptTranslations( App_Module::HANDLE );
	}

	private function enqueue_config() {
		// If script didn't loaded, config is still relevant, enqueue without a file.
		if ( ! wp_script_is( App_Module::HANDLE ) ) {
			wp_register_script( App_Module::HANDLE, false, [], MODULIX_VERSION );
			wp_enqueue_script( App_Module::HANDLE );
		}

		$this->print_config( App_Module::HANDLE );
	}

	public function __construct() {
		$this->add_component( 'site-editor', new SiteEditor() );

		add_action( 'elementor/app/init', [ $this, 'init' ] );

		add_action( 'elementor/common/after_register_scripts', function () {
			$this->enqueue_config();
		} );
	}
}
