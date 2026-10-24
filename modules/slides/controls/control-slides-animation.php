<?php
namespace Modulix\Modules\Slides\Controls;
// Modulix: derived from Elementor Pro 4.0.4, modules/slides/controls/control-slides-animation.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Control_Hover_Animation;

class Control_Slides_Animation extends Control_Hover_Animation {

	const TYPE = 'animation_slides_content';

	public function get_type(): string {
		return static::TYPE;
	}

	public static function get_animations(): array {
		return [
			'fadeInDown' => esc_html__( 'Down', 'modulix' ),
			'fadeInUp' => esc_html__( 'Up', 'modulix' ),
			'fadeInRight' => esc_html__( 'Right', 'modulix' ),
			'fadeInLeft' => esc_html__( 'Left', 'modulix' ),
			'zoomIn' => esc_html__( 'Zoom', 'modulix' ),
		];
	}
}
