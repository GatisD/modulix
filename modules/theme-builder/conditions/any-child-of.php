<?php
namespace Modulix\Modules\ThemeBuilder\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/conditions/any-child-of.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Any_Child_Of extends Child_Of {

	public function get_name() {
		return 'any_child_of';
	}

	public function get_label() {
		return esc_html__( 'Any child of', 'modulix' );
	}

	public function check( $args ) {
		if ( ! is_singular() ) {
			return false;
		}

		$id = (int) $args['id'];
		$parents = get_post_ancestors( get_the_ID() );

		return ( ( 0 === $id && ! empty( $parents ) ) || in_array( $id, $parents ) );
	}
}
