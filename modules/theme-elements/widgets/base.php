<?php
namespace Modulix\Modules\ThemeElements\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-elements/widgets/base.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Base\Base_Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class Base extends Base_Widget {

	public function get_categories() {
		return [ 'theme-elements' ];
	}

	public function render_plain_content() {}
}
