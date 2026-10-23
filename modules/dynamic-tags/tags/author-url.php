<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/author-url.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DynamicTags\Tags\Base\Pro_Data_Tag;
use Modulix\Modules\DynamicTags\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Author_URL extends Pro_Data_Tag {

	public function get_name() {
		return 'author-url';
	}

	public function get_group() {
		return Module::AUTHOR_GROUP;
	}

	public function get_categories() {
		return [ Module::URL_CATEGORY ];
	}

	public function get_title() {
		return esc_html__( 'Author URL', 'modulix' );
	}

	public function get_panel_template_setting_key() {
		return 'url';
	}

	public function get_value( array $options = [] ) {
		$value = '';

		if ( 'archive' === $this->get_settings( 'url' ) ) {
			global $authordata;

			if ( $authordata ) {
				$value = get_author_posts_url( $authordata->ID, $authordata->user_nicename );
			}
		} else {
			$value = get_the_author_meta( 'url' );
		}

		return $value;
	}

	protected function register_controls() {
		$this->add_control(
			'url',
			[
				'label' => esc_html__( 'URL', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'archive',
				'options' => [
					'archive' => esc_html__( 'Author Archive', 'modulix' ),
					'website' => esc_html__( 'Author Website', 'modulix' ),
				],
			]
		);
	}
}
