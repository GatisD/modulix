<?php
namespace Modulix\Modules\ThemeBuilder\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/widgets/archive-title.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Archive_Title extends Title_Widget_Base {

	protected function get_dynamic_tag_name() {
		return 'archive-title';
	}

	public function get_name() {
		// `theme` prefix is to avoid conflicts with a dynamic-tag with same name.
		return 'theme-archive-title';
	}

	public function get_title() {
		return esc_html__( 'Archive Title', 'modulix' );
	}

	public function get_icon() {
		return 'eicon-archive-title';
	}

	public function get_categories() {
		return [ 'theme-elements-archive' ];
	}

	public function get_keywords() {
		return [ 'title', 'heading', 'archive' ];
	}

	public function has_widget_inner_wrapper(): bool {
		return ! Plugin::elementor()->experiments->is_feature_active( 'e_optimized_markup' );
	}
}
