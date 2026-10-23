<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/site-title.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\DynamicTags\Tags\Base\Pro_Tag;
use Modulix\Modules\DynamicTags\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Site_Title extends Pro_Tag {
	public function get_name() {
		return 'site-title';
	}

	public function get_title() {
		return esc_html__( 'Site Title', 'modulix' );
	}

	public function get_group() {
		return Module::SITE_GROUP;
	}

	public function get_categories() {
		return [ Module::TEXT_CATEGORY ];
	}

	public function render() {
		echo wp_kses_post( get_bloginfo() );
	}
}
