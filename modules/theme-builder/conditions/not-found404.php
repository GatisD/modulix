<?php
namespace Modulix\Modules\ThemeBuilder\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/conditions/not-found404.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Not_Found404 extends Condition_Base {

	public static function get_type() {
		return 'singular';
	}

	public static function get_priority() {
		return 20;
	}

	public function get_name() {
		return 'not_found404';
	}

	public function get_label() {
		return esc_html__( '404 Page', 'modulix' );
	}

	public function check( $args ) {
		return is_404();
	}
}
