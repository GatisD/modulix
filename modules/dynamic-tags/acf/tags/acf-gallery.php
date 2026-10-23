<?php
namespace Modulix\Modules\DynamicTags\ACF\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/acf/tags/acf-gallery.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\DynamicTags\ACF\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class ACF_Gallery extends Base_ACF_Tag {

	public function get_name() {
		return 'acf-gallery';
	}

	public function get_title() {
		return esc_html__( 'ACF', 'modulix' ) . ' ' . esc_html__( 'Gallery Field', 'modulix' );
	}

	public function get_categories() {
		return [ Module::GALLERY_CATEGORY ];
	}

	public function get_group() {
		return Module::ACF_GROUP;
	}

	public function get_panel_template_setting_key() {
		return 'key';
	}

	public function get_value( array $options = [] ) {
		$images = [];

		list( $field, $meta_key ) = Module::get_tag_value_field( $this );

		if ( $field ) {
			$value = $field['value'];
		} else {
			// Field settings has been deleted or not available.
			$value = get_field( $meta_key );
		}

		if ( is_array( $value ) && ! empty( $value ) ) {
			foreach ( $value as $image ) {
				$images[] = [
					'id' => $image['ID'],
				];
			}
		}

		return $images;
	}

	protected function register_controls() {
		Module::add_key_control( $this );
	}

	public function get_supported_fields() {
		return [
			'gallery',
		];
	}
}
