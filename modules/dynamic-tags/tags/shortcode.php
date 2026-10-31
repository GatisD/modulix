<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/shortcode.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-24): stored shortcodes require an allowed document and always use the post HTML boundary.

use Elementor\Controls_Manager;
use Modulix\Modules\DynamicTags\Tags\Base\Pro_Tag;
use Modulix\Modules\DynamicTags\Module;
use Modulix\Plugin;
use Modulix\Security\DynamicContent;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Shortcode extends Pro_Tag {
	public function get_name() {
		return 'shortcode';
	}

	public function get_title() {
		return esc_html__( 'Shortcode', 'modulix' );
	}

	public function get_group() {
		return Module::SITE_GROUP;
	}

	public function get_categories() {
		return [
			Module::TEXT_CATEGORY,
			Module::NUMBER_CATEGORY,
			Module::URL_CATEGORY,
			Module::POST_META_CATEGORY,
			Module::DATETIME_CATEGORY,
		];
	}

	protected function register_controls() {
		$this->add_control(
			'shortcode',
			[
				'label' => esc_html__( 'Shortcode', 'modulix' ),
				'type'  => Controls_Manager::TEXTAREA,
				'ai' => [
					'active' => false,
				],
			],
		);
	}

	public function render() {
		$settings = $this->get_settings();

		if ( empty( $settings['shortcode'] ) ) {
			return;
		}

		$document = Plugin::elementor()->documents->get_current();
		$post = $document ? get_post( $document->get_main_id() ) : null;
		$can_edit = $post && current_user_can( 'edit_post', (int) $post->ID );
		if ( ! DynamicContent::stored_shortcode_allowed( $post, $can_edit ) ) {
			return;
		}

		$value = do_shortcode( (string) $settings['shortcode'] );
		echo wp_kses_post( $value );
	}
}
