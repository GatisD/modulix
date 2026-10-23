<?php
namespace Modulix\Modules\ThemeBuilder\Documents;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/documents/single.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Single extends Single_Base {

	public static function get_type() {
		return 'single';
	}

	public static function get_title() {
		return esc_html__( 'Single', 'modulix' );
	}

	public static function get_plural_title() {
		return esc_html__( 'Singles', 'modulix' );
	}

	protected function get_remote_library_config() {
		$config = parent::get_remote_library_config();

		// Fallback for old multipurpose `single` documents.
		$category = $this->get_meta( self::REMOTE_CATEGORY_META_KEY );

		if ( $category ) {
			if ( 'not_found404' === $category ) {
				$category = '404 page';
			} else {
				$category = 'single ' . $category;
			}

			$config['category'] = $category;
		} else {
			$config['category'] = 'single post';
		}

		return $config;
	}

	protected static function get_site_editor_thumbnail_url() {
		return ELEMENTOR_ASSETS_URL . 'images/app/site-editor/single-post.svg';
	}
}
