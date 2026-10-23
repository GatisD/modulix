<?php
namespace Modulix\Base;
// Modulix: derived from Elementor Pro 4.0.4, base/base-widget-trait.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): licence check (is_editable) removed; Pro panel category kept.


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait Base_Widget_Trait {
	public function get_categories() {
		return [ 'pro-elements' ];
	}
}
