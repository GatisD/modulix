<?php
namespace Modulix\Modules\ThemeBuilder\Skins;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/skins/posts-archive-skin-cards.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\Posts\Skins\Skin_Cards;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Posts_Archive_Skin_Cards extends Skin_Cards {
	use Posts_Archive_Skin_Base;

	protected function _register_controls_actions() {
		add_action( 'elementor/element/archive-posts/section_layout/before_section_end', [ $this, 'register_controls' ] );
		add_action( 'elementor/element/archive-posts/section_layout/after_section_end', [ $this, 'register_style_sections' ] );
		add_action( 'elementor/element/archive-posts/archive_cards_section_design_image/before_section_end', [ $this, 'register_additional_design_image_controls' ] );
	}

	public function get_id() {
		return 'archive_cards';
	}

	public function get_title() {
		return esc_html__( 'Cards', 'modulix' );
	}

	public function get_container_class() {
		// Use parent class and parent css.
		return 'elementor-posts--skin-cards';
	}

	/* Remove `posts_per_page` control */
	protected function register_post_count_control() {}
}
