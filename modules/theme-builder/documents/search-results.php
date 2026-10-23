<?php
namespace Modulix\Modules\ThemeBuilder\Documents;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/documents/search-results.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Search_Results extends Archive {

	public function get_name() {
		return 'search-results';
	}

	public static function get_type() {
		return 'search-results';
	}

	public static function get_sub_type() {
		return 'search';
	}

	public static function get_title() {
		return esc_html__( 'Search Results', 'modulix' );
	}

	public static function get_plural_title() {
		return esc_html__( 'Search Results', 'modulix' );
	}

	protected static function get_site_editor_icon() {
		return 'eicon-search-results';
	}

	protected static function get_site_editor_tooltip_data() {
		return [
			'title' => esc_html__( 'What is a search results template?', 'modulix' ),
			'content' => esc_html__( 'You can easily control the layout and design of the Search Results page with the Search Results template, which is simply a special archive template just for displaying search results.', 'modulix' ),
			'tip' => esc_html__( 'You can customize the message if there are no results for the search term.', 'modulix' ),
			'docs' => 'https://go.elementor.com/app-theme-builder-search-results',
			'video_url' => 'https://www.youtube.com/embed/KKkIU_L5sDo',
		];
	}

	public static function get_preview_as_default() {
		return 'search';
	}

	public static function get_preview_as_options() {
		$options = [
			'search' => esc_html__( 'Search results', 'modulix' ),
		];

		return [
			'archive' => [
				'label' => esc_html__( 'Archive', 'modulix' ),
				'options' => $options,
			],
		];
	}

	protected function get_remote_library_config() {
		$config = parent::get_remote_library_config();

		$config['category'] = 'archive';

		return $config;
	}
}
