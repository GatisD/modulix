<?php

namespace Modulix\Modules\Forms\Controls;
// Modulix: derived from Elementor Pro 4.0.4, modules/forms/controls/fields-repeater.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Control_Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Fields_Repeater extends Control_Repeater {

	const CONTROL_TYPE = 'form-fields-repeater';

	public function get_type() {
		return self::CONTROL_TYPE;
	}
}
