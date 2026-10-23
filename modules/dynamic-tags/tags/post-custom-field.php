<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/post-custom-field.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): licence metadata in the editor config removed.

use Elementor\Controls_Manager;
use Modulix\Modules\DynamicTags\Tags\Base\Tag;
use Modulix\Modules\DynamicTags\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Post_Custom_Field extends Tag {

	public function get_name() {
		return 'post-custom-field';
	}

	public function get_title() {
		return esc_html__( 'Post Custom Field', 'modulix' );
	}

	public function get_group() {
		return Module::POST_GROUP;
	}

	public function get_categories() {
		$categories = [
			Module::TEXT_CATEGORY,
			Module::URL_CATEGORY,
			Module::POST_META_CATEGORY,
			Module::COLOR_CATEGORY,
			Module::DATETIME_CATEGORY,
			Module::MEDIA_CATEGORY,
		];

		// TODO: Remove this in 3.37.0
		$categories = Module::add_v4_svg_category( $categories );

		return $categories;
	}

	public function get_panel_template_setting_key() {
		return 'key';
	}

	public function is_settings_required() {
		return true;
	}

	protected function register_controls() {
		$this->add_control(
			'key',
			[
				'label' => esc_html__( 'Key', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => $this->get_custom_keys_array(),
			]
		);

		$this->add_control(
			'custom_key',
			[
				'label' => esc_html__( 'Custom Key', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => 'key',
				'condition' => [
					'key' => '',
				],
				'ai' => [
					'active' => false,
				],
			]
		);

	}

	public function get_custom_field_value( string $key ) : string {
		$value = get_post_meta( get_the_ID(), $key, true );

		if ( ! is_string( $value ) ) {
			return '';
		}

		return $value;
	}

	public function render() {
		$key = $this->get_settings( 'key' );

		if ( empty( $key ) ) {
			$key = $this->get_settings( 'custom_key' );
		}

		if ( empty( $key ) ) {
			return;
		}

		$value = $this->get_custom_field_value( $key );

		if ( empty( $value ) ) {
			return;
		}

		echo wp_kses_post( $value );
	}

	private function get_custom_keys_array() {
		$custom_keys = get_post_custom_keys();
		$options = [
			'' => esc_html__( 'Select...', 'modulix' ),
		];

		if ( ! empty( $custom_keys ) ) {
			foreach ( $custom_keys as $custom_key ) {
				if ( '_' !== substr( $custom_key, 0, 1 ) ) {
					$options[ $custom_key ] = $custom_key;
				}
			}
		}

		return $options;
	}

}
