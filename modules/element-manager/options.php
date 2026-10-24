<?php
namespace Modulix\Modules\ElementManager;
// Modulix: derived from Elementor Pro 4.0.4, modules/element-manager/options.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Options {

	public static function get_role_restrictions() {
		return (array) get_option( 'elementor_pro_element_manager_role_permission', [] );
	}

	public static function update_role_restrictions( $role_restrictions ) {
		update_option( 'elementor_pro_element_manager_role_permission', (array) $role_restrictions );
	}
}
