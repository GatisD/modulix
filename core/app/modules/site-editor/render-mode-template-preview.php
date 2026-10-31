<?php
namespace Modulix\Core\App\Modules\SiteEditor;
// Modulix: derived from Elementor Pro 4.0.4, core/app/modules/site-editor/render-mode-template-preview.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Frontend\RenderModes\Render_Mode_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Render_Mode_Template_Preview extends Render_Mode_Base {
	/**
	 * @return string
	 */
	public static function get_name() {
		return 'template-preview';
	}

	public function filter_template() {
		return ELEMENTOR_PATH . 'modules/page-templates/templates/canvas.php';
	}

	public function prepare_render() {
		parent::prepare_render();

		show_admin_bar( false );

		remove_filter(
			'the_content',
			[ \Modulix\Modules\ThemeBuilder\Module::instance()->get_locations_manager(), 'builder_wrapper' ],
			9999999
		);

		add_filter( 'template_include', [ $this, 'filter_template' ] );

		add_action( 'wp_head', [ $this, 'render_pointer_event_style' ] );
	}

	/**
	 * disable all the interactions in the preview render mode.
	 */
	public function render_pointer_event_style() {
		echo '<style> html { pointer-events: none; } </style>';
	}

	public function is_static() {
		return true;
	}
}
