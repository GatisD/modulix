<?php
namespace Modulix\Modules\ThemeBuilder\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/conditions/search.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Search extends Condition_Base {

	public static function get_type() {
		return 'archive';
	}

	public static function get_priority() {
		return 70;
	}

	public function get_name() {
		return 'search';
	}

	public function get_label() {
		return esc_html__( 'Search results', 'modulix' );
	}

	public function check( $args ) {
		return is_search();
	}
}
