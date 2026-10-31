<?php
namespace Modulix\Modules\ThemeBuilder\Classes;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/classes/template-conditions.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-25): namespace ElementorPro -> Modulix.

use Elementor\Controls_Stack;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Template_Conditions extends Controls_Stack {

	public function get_name() {
		return 'template-conditions';
	}

	protected function register_controls() {
		parent::register_controls();

		$this->add_control(
			'conditions',
			[
				'section' => 'settings',
				'type' => Conditions_Repeater::CONTROL_TYPE,
			]
		);
	}
}
