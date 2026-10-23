<?php
namespace Modulix\Modules\ThemeBuilder\Documents;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/documents/error-404.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Error_404 extends Single_Base {

	public static function get_type() {
		return 'error-404';
	}

	public static function get_sub_type() {
		return 'not_found404';
	}

	public static function get_title() {
		return esc_html__( 'Error 404', 'modulix' );
	}

	public static function get_plural_title() {
		return esc_html__( 'Error 404', 'modulix' );
	}

	protected static function get_site_editor_icon() {
		return 'eicon-error-404';
	}

	protected static function get_site_editor_tooltip_data() {
		return [
			'title' => esc_html__( 'What is a 404 page template?', 'modulix' ),
			'content' => esc_html__( 'A 404 page template allows you to easily design the layout and style of the page that is displayed when a visitor arrives at a page that does not exist.', 'modulix' ),
			'tip' => esc_html__( 'Keep your site\'s visitors happy when they get lost by displaying your recent posts, a search bar, or any information that might help the user find what they were looking for.', 'modulix' ),
			'docs' => 'https://go.elementor.com/app-theme-builder-error-404',
			'video_url' => 'https://www.youtube.com/embed/ACCNp9tBMQg',
		];
	}

	public static function get_preview_as_options() {
		return [
			'page/404' => esc_html__( '404', 'modulix' ),
		];
	}

	protected function get_remote_library_config() {
		$config = parent::get_remote_library_config();

		$config['category'] = '404 page';

		return $config;
	}
}
