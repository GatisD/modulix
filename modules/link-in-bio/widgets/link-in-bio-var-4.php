<?php

namespace Modulix\Modules\LinkInBio\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/link-in-bio/widgets/link-in-bio-var-4.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Utils;
use Modulix\Modules\LinkInBio\Base\Widget_Link_In_Bio_Base_Pro;
use Modulix\Modules\LinkInBio\Classes\Render\Icons_Below_Cta_Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Link_In_Bio_Var_4 extends Widget_Link_In_Bio_Base_Pro {

	public static function get_configuration() {
		$config = parent::get_configuration();
		$config['content']['bio_section']['title']['default'] = '';
		$config['content']['identity_section']['identity_image_style'] = false;
		$config['content']['identity_section']['has_heading_text'] = esc_html__( 'Profile', 'modulix' );
		$config['style']['cta_section']['has_link_type'] = false;
		$config['style']['cta_section']['has_corners'] = false;
		$config['style']['cta_section']['has_padding'] = false;
		$config['style']['cta_section']['has_dividers'] = true;
		$config['style']['cta_section']['has_border_control'] = false;
		$config['style']['cta_section']['has_background_control'] = false;
		$config['style']['cta_section']['has_cta_control_text'] = esc_html__( 'Links', 'modulix' );
		$config['style']['border_section']['field_options'] = [
			'image' => [
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
			],
		];
		$config['content']['cta_section']['cta_repeater_defaults'] = [
			[
				'cta_link_text' => esc_html__( 'Get Healthy', 'modulix' ),
			],
			[
				'cta_link_text' => esc_html__( 'Top 10 Recipes', 'modulix' ),
			],
			[
				'cta_link_text' => esc_html__( 'Meal Prep', 'modulix' ),
			],
			[
				'cta_link_text' => esc_html__( 'Healthful Resources', 'modulix' ),
			],
		];

		return $config;
	}

	public function get_name(): string {
		return 'link-in-bio-var-4';
	}

	public function get_title(): string {
		return esc_html__( 'Links', 'modulix' );
	}

	public function render(): void {
		$render = new Icons_Below_Cta_Render( $this );
		$render->render();
	}

	protected function add_content_tab(): void {
		$this->add_identity_section();

		$this->add_bio_section();

		$this->add_cta_controls();

		$this->add_icons_controls();
	}

	protected function add_style_tab(): void {
		$this->add_style_identity_controls();

		$this->add_style_bio_controls();

		$this->add_style_cta_section();

		$this->add_style_icons_controls();

		$this->add_style_background_controls();
	}
}
