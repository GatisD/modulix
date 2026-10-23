<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/archive-meta.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\DynamicTags\Tags\Base\Pro_Tag;
use Modulix\Modules\DynamicTags\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Archive_Meta extends Pro_Tag {

	public function get_name() {
		return 'archive-meta';
	}

	public function get_title() {
		return esc_html__( 'Archive Meta', 'modulix' );
	}

	public function get_group() {
		return Module::ARCHIVE_GROUP;
	}

	public function get_categories() {
		return [ Module::TEXT_CATEGORY ];
	}

	public function get_panel_template() {
		return ' ({{{ key }}})';
	}

	public function render() {
		$key = $this->get_settings( 'key' );

		if ( empty( $key ) ) {
			return;
		}

		$value = '';

		if ( is_category() || is_tax() ) {
			$value = get_term_meta( get_queried_object_id(), $key, true );
		} elseif ( is_author() ) {
			$value = get_user_meta( get_queried_object_id(), $key, true );
		}

		echo wp_kses_post( $value );
	}

	public function get_panel_template_setting_key() {
		return 'key';
	}

	protected function register_controls() {
		$this->add_control(
			'key',
			[
				'label' => esc_html__( 'Meta Key', 'modulix' ),
				'ai' => [
					'active' => false,
				],
			]
		);
	}
}
