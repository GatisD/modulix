<?php
namespace Modulix\Modules\MegaMenu\Controls;
// Modulix: derived from Elementor Pro 4.0.4, modules/mega-menu/controls/control-menu-dropdown-animation.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Control_Hover_Animation;

class Control_Menu_Dropdown_Animation extends Control_Hover_Animation {

	const TYPE = 'animation_menu_dropdown';

	public function get_type(): string {
		return static::TYPE;
	}

	public static function get_animations(): array {
		return [
			'fadeIn' => esc_html__( 'Fade in', 'modulix' ),
		];
	}
}
