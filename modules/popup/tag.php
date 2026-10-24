<?php
namespace Modulix\Modules\Popup;
// Modulix: derived from Elementor Pro 4.0.4, modules/popup/tag.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DynamicTags\Tags\Base\Tag as DynamicTagsTag;
use Modulix\Modules\DynamicTags\Module as DynamicTagsModule;
use Modulix\Modules\QueryControl\Module as QueryControlModule;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Tag extends DynamicTagsTag {

	public function get_name() {
		return 'popup';
	}

	public function get_title() {
		return esc_html__( 'Popup', 'modulix' );
	}

	public function get_group() {
		return DynamicTagsModule::ACTION_GROUP;
	}

	public function get_categories() {
		return [ DynamicTagsModule::URL_CATEGORY ];
	}

	/**
	 * @since 3.6.0
	 *
	 * @deprecated 3.8.0
	 * On_Import_Trait::on_import_update_dynamic_content() should be used instead.
	 * Remove in the future.
	 */
	public static function on_import_replace_dynamic_content( $config, $map_old_new_post_ids ) {
		if ( isset( $config['settings']['popup'] ) ) {
			$config['settings']['popup'] = $map_old_new_post_ids[ $config['settings']['popup'] ];
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
					'open' => esc_html__( 'Open Popup', 'modulix' ),
					'close' => esc_html__( 'Close Popup', 'modulix' ),
					'toggle' => esc_html__( 'Toggle Popup', 'modulix' ),
				],
			]
		);

		$this->add_control(
			'popup',
			[
				'label' => esc_html__( 'Popup', 'modulix' ),
				'type' => QueryControlModule::QUERY_CONTROL_ID,
				'autocomplete' => [
					'object' => QueryControlModule::QUERY_OBJECT_LIBRARY_TEMPLATE,
					'query' => [
						'posts_per_page' => 20,
						'post_status' => [ 'publish', 'private' ],
						'meta_query' => [
							[
								'key' => Document::TYPE_META_KEY,
								'value' => 'popup',
							],
						],
					],
				],
				'label_block' => true,
				'condition' => [
					'action' => [ 'open', 'toggle' ],
				],
			]
		);

		$this->add_control(
			'do_not_show_again',
			[
				'label' => esc_html__( 'Don\'t Show Again', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'condition' => [
					'action' => 'close',
				],
			]
		);
	}

	public function render() {
		$settings = $this->get_active_settings();

		if ( 'close' === $settings['action'] ) {
			$this->print_close_popup_link( $settings );

			return;
		}

		$this->print_open_popup_link( $settings );
	}

	// Keep Empty to avoid default advanced section
	protected function register_advanced_section() {}

	private function print_open_popup_link( array $settings ) {
		if ( ! $settings['popup'] ) {
			return;
		}

		$link_action_url = Plugin::elementor()->frontend->create_action_hash( 'popup:open', [
			'id' => $settings['popup'],
			'toggle' => 'toggle' === $settings['action'],
		] );

		Module::add_popup_to_location( $settings['popup'] );

		// PHPCS - `create_action_hash` is safe.
		echo $link_action_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	private function print_close_popup_link( array $settings ) {
		// PHPCS - `create_action_hash` is safe.
		echo Plugin::elementor()->frontend->create_action_hash( 'popup:close', [ 'do_not_show_again' => $settings['do_not_show_again'] ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
