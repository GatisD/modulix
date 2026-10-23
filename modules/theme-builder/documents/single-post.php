<?php
namespace Modulix\Modules\ThemeBuilder\Documents;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/documents/single-post.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Single_Post extends Single_Base {

	public static function get_type() {
		return 'single-post';
	}

	public static function get_title() {
		return esc_html__( 'Single Post', 'modulix' );
	}

	public static function get_plural_title() {
		return esc_html__( 'Single Posts', 'modulix' );
	}

	protected static function get_site_editor_icon() {
		return 'eicon-single-post';
	}

	protected static function get_site_editor_tooltip_data() {
		return [
			'title' => esc_html__( 'What is a single post template?', 'modulix' ),
			'content' => esc_html__( 'A single post template allows you to easily design the layout and style of posts, ensuring a design consistency throughout all your blog posts, for example.', 'modulix' ),
			'tip' => esc_html__( 'You can create multiple single post templates, and assign each to a different category.', 'modulix' ),
			'docs' => 'https://go.elementor.com/app-theme-builder-post',
			'video_url' => 'https://www.youtube.com/embed/8Fk-Edu7DL0',
		];
	}

	protected function get_remote_library_config() {
		$config = parent::get_remote_library_config();

		$config['category'] = 'single post';

		return $config;
	}
}
