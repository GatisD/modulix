<?php
namespace Modulix\Modules\DynamicTags\ACF;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/acf/dynamic-value-provider.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): ACF options values only for site managers or public renders of published documents; password fields never printed.
// Modified by Modulix (2026-10-23): loop builder references replaced: loop taxonomy branch removed, loop item type as its string 'loop-item'.

use Modulix\ContentAccess;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Dynamic_Value_Provider {

	public function get_value( $key ) {
		if ( empty( $key ) ) {
			return [];
		}

		[ $field_key, $meta_key ] = explode( ':', $key );

		$document = Plugin::elementor()->documents->get_current();

		if ( 'options' === $field_key ) {
			// Modulix: site-wide options only for site managers, or on the public render of a published document.
			$editing = Plugin::elementor()->editor->is_edit_mode() || Plugin::elementor()->preview->is_preview_mode() || is_preview() || wp_doing_ajax();
			$published = ! empty( $document ) && 'publish' === get_post_status( $document->get_main_id() );
			if ( ! ContentAccess::acf_options_allowed( current_user_can( 'manage_options' ), $editing, $published ) ) {
				return [ false, '' ]; // empty meta key: the tags' get_field( $meta_key ) fallback finds nothing
			}
			$field = $this->get_field_object( $meta_key, $field_key );
		} elseif ( ! empty( $document ) && 'loop-item' === $document::get_type() ) {
			$field = $this->get_field_object( $field_key, get_the_ID() );
		} else {
			$field = $this->get_field_object( $field_key, get_queried_object() );
		}

		// Modulix: password fields are never printed by dynamic tags.
		if ( ContentAccess::is_secret_acf_field( $field ) ) {
			return [ false, '' ]; // empty meta key: the tags' get_field( $meta_key ) fallback finds nothing
		}

		return [ $field, $meta_key ];
	}

	/**
	 * Retrieve the custom field value from `ACF` plugin.
	 * Used for testing.
	 *
	 * @param $selector
	 * @param $post_id
	 *
	 * @return array|false
	 */
	protected function get_field_object( $selector, $post_id ) {
		return get_field_object( $selector, $post_id );
	}

}
