<?php
namespace Modulix\Modules\DynamicTags\ACF\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/acf/tags/acf-file.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\DynamicTags\ACF\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class ACF_File extends ACF_Image {

	public function get_name() {
		return 'acf-file';
	}

	public function get_title() {
		return esc_html__( 'ACF', 'modulix' ) . ' ' . esc_html__( 'File Field', 'modulix' );
	}

	public function get_categories() {
		return [
			Module::MEDIA_CATEGORY,
		];
	}

	public function get_supported_fields() {
		return [
			'file',
		];
	}
}
