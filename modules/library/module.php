<?php
namespace Modulix\Modules\Library;
// Modulix: derived from Elementor Pro 4.0.4, modules/library/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): WordPress library widget and deprecated autocomplete callbacks removed.
// Modified by Modulix (2026-10-24): guarded [elementor-template] shortcode restored.

use Modulix\Base\Module_Base;
use Modulix\Modules\Library\Classes\Shortcode;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Module extends Module_Base {
	public function __construct() {
		parent::__construct();

		new Shortcode();
	}

	public function get_widgets() {
		return [
			'Template',
		];
	}

	public function get_name() {
		return 'library';
	}

	public static function get_templates() {
		return Plugin::elementor()->templates_manager->get_source( 'local' )->get_items();
	}

	public static function empty_templates_message() {
		return '<div id="elementor-widget-template-empty-templates">
				<div class="elementor-widget-template-empty-templates-icon"><i class="eicon-nerd" aria-hidden="true"></i></div>
				<div class="elementor-widget-template-empty-templates-title">' . esc_html__( 'You Haven’t Saved Templates Yet.', 'modulix' ) . '</div>
				<div class="elementor-widget-template-empty-templates-footer">' . esc_html__( 'Want to learn more about Elementor library?', 'modulix' ) . ' <a class="elementor-widget-template-empty-templates-footer-url" href="https://go.elementor.com/docs-library/" target="_blank">' . esc_html__( 'Click Here', 'modulix' ) . '</a>
				</div>
				</div>';
	}
}
