<?php
namespace Modulix\Modules\ThemeBuilder\Documents;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/documents/footer.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Footer extends Header_Footer_Base {

	public static function get_properties() {
		$properties = parent::get_properties();

		$properties['location'] = 'footer';

		return $properties;
	}

	public static function get_type() {
		return 'footer';
	}

	public static function get_title() {
		return esc_html__( 'Footer', 'modulix' );
	}

	public static function get_plural_title() {
		return esc_html__( 'Footers', 'modulix' );
	}

	protected static function get_site_editor_icon() {
		return 'eicon-footer';
	}

	protected function get_default_wrapping_html_tag() {
		return 'footer';
	}

	protected static function get_site_editor_tooltip_data() {
		return [
			'title' => esc_html__( 'What is a footer template?', 'modulix' ),
			'content' => esc_html__( 'The footer template allows you to easily design and edit custom WordPress footers without the limits of your theme’s footer design constraints', 'modulix' ),
			'tip' => esc_html__( 'You can create multiple footers, and assign each to different areas of your site.', 'modulix' ),
			'docs' => 'https://go.elementor.com/app-theme-builder-footer',
			'video_url' => 'https://www.youtube.com/embed/xa8DoR4tQrY',
		];
	}
}
