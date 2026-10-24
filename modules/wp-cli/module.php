<?php
namespace Modulix\Modules\WpCli;
// Modulix: derived from Elementor Pro 4.0.4, modules/wp-cli/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Base\Module as BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Module extends BaseModule {

	/**
	 * Get module name.
	 *
	 * @since  2.1.0
	 * @access public
	 *
	 * @return string Module name.
	 */
	public function get_name() {
		return 'wp-cli';
	}

	public static function is_active() {
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	/**
	 *
	 * @since  2.1.0
	 * @access public
	 */
	public function __construct() {
		\WP_CLI::add_command( 'elementor-pro theme-builder', '\Modulix\Modules\WpCli\ThemeBuilder' );
	}
}
