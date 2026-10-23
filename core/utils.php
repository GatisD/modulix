<?php
namespace Modulix\Core;
// Modulix: derived from Elementor Pro 4.0.4, core/utils.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): kept get_public_post_types, set_global_authordata, _unstable_get_super_global_value and its two sanitizers; other helpers removed.


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Utils {
	public static function get_public_post_types( $args = [] ) {
		$post_type_args = [
			// Default is the value $public.
			'show_in_nav_menus' => true,
		];

		// Keep for backwards compatibility
		if ( ! empty( $args['post_type'] ) ) {
			$post_type_args['name'] = $args['post_type'];
			unset( $args['post_type'] );
		}

		$post_type_args = wp_parse_args( $post_type_args, $args );

		$_post_types = get_post_types( $post_type_args, 'objects' );

		$post_types = [];

		foreach ( $_post_types as $post_type => $object ) {
			$post_types[ $post_type ] = $object->label;
		}

		/**
		 * Supported post types.
		 *
		 * Filters the allowed post types Elementor should work on.
		 *
		 * By default Elementor can be applied on publicly available post
		 * types. This hook allows developers to alter those post types to
		 * add new and remove existing types.
		 *
		 * @since 2.3.0
		 *
		 * @param array $post_types Elementor supported post types.
		 */
		$post_types = apply_filters( 'elementor_pro/utils/get_public_post_types', $post_types );

		return $post_types;
	}

	public static function set_global_authordata() {
		global $authordata;
		if ( ! isset( $authordata->ID ) ) {
			$post = get_post();
			$authordata = get_userdata( $post->post_author ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * TODO: Use core method instead (after Pro minimum requirements is updated).
	 * PR URL: https://github.com/elementor/elementor/pull/24092
	 */
	public static function _unstable_get_super_global_value( $super_global, $key ) {
		if ( ! isset( $super_global[ $key ] ) ) {
			return null;
		}

		if ( $_FILES === $super_global ) {
			return isset( $super_global[ $key ]['name'] ) ?
				static::sanitize_file_name( $super_global[ $key ] ) :
				static::sanitize_multi_upload( $super_global[ $key ] );
		}

		return wp_kses_post_deep( wp_unslash( $super_global[ $key ] ) );
	}

	private static function sanitize_multi_upload( $fields ) {
		return array_map( function( $field ) {
			return array_map( [ __CLASS__, 'sanitize_file_name' ], $field );
		}, $fields );
	}

	private static function sanitize_file_name( $file ) {
		$file['name'] = sanitize_file_name( $file['name'] );

		return $file;
	}
}
