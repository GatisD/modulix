<?php
namespace Modulix\Modules\OffCanvas;
// Modulix: derived from Elementor Pro 4.0.4, modules/off-canvas/tag.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DynamicTags\Tags\Base\Tag as DynamicTagsTag;
use Modulix\Modules\DynamicTags\Module as DynamicTagsModule;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Tag extends DynamicTagsTag {

	public function get_name() {
		return 'off-canvas';
	}

	public function get_title() {
		return esc_html__( 'Off-Canvas', 'modulix' );
	}

	public function get_group() {
		return DynamicTagsModule::ACTION_GROUP;
	}

	public function get_categories() {
		return [ DynamicTagsModule::URL_CATEGORY ];
	}

	public static function on_import_replace_dynamic_content( $config, $map_old_new_post_ids ) {
		if ( isset( $config['settings']['off_canvas'] ) ) {
			$config['settings']['off_canvas'] = $map_old_new_post_ids[ $config['settings']['off_canvas'] ];
		}

		return $config;
	}

	public function register_controls() {
		$this->add_control(
			'action',
			[
				'label' => esc_html__( 'Action', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'open',
				'options' => [
					'open' => esc_html__( 'Open Off-Canvas', 'modulix' ),
					'close' => esc_html__( 'Close Off-Canvas', 'modulix' ),
					'toggle' => esc_html__( 'Toggle Off-Canvas', 'modulix' ),
				],
			]
		);

		$this->add_control(
			'off_canvas',
			[
				'label' => esc_html__( 'Choose Off-Canvas widget', 'modulix' ),
				'atomic_label' => esc_html__( 'Off-canvas widget', 'modulix' ),
				'placeholder' => '',
				'type' => Controls_Manager::SELECT,
				'label_block' => true,
				'options' => [
					'' => esc_html__( 'Select Off-canvas', 'modulix' ),
				],
				'default' => '',
				'collection_id' => $this->get_name(),
			]
		);
	}

	public function render() {
		$settings = $this->get_active_settings();

		if ( ! $settings['off_canvas'] ) {
			return;
		}

		$link_action_url = Plugin::elementor()->frontend->create_action_hash( 'off_canvas:' . $settings['action'], [
			'id' => apply_filters( 'elementor-pro/off-canvas/id', $settings['off_canvas'] ),
			'displayMode' => $settings['action'],
		] );

		// PHPCS - `create_action_hash` is safe.
		echo $link_action_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	// Keep Empty to avoid default advanced section
	protected function register_advanced_section() {}
}
