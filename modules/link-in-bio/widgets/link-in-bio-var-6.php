<?php

namespace Modulix\Modules\LinkInBio\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/link-in-bio/widgets/link-in-bio-var-6.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\LinkInBio\Base\Widget_Link_In_Bio_Base_Pro;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Link_In_Bio_Var_6 extends Widget_Link_In_Bio_Base_Pro {

	public static function get_configuration() {
		$config = parent::get_configuration();
		$config['content']['cta_section'] = [];
		$config['content']['image_links_section'] = [
			'images_max' => 0,
			'images_repeater_defaults' => [
				[],
				[],
				[],
				[],
			],
		];
		$config['style']['cta_section'] = false;
		$config['style']['image_links_section'] = [
			'has_border_control' => [
				'prefix' => 'image_links',
				'show_border_args' => [
					'label' => esc_html__( 'Image Border', 'modulix' ),
				],
				'border_width_args' => [
					'selectors' => [
						'{{WRAPPER}} .e-link-in-bio' => '--e-link-in-bio-image-links-border-width: {{SIZE}}{{UNIT}}',
					],
				],
				'border_color_args' => [
					'selectors' => [
						'{{WRAPPER}} .e-link-in-bio' => '--e-link-in-bio-image-links-border-color: {{VALUE}}',
					],
				],
			],
		];

		return $config;
	}

	public function get_name(): string {
		return 'link-in-bio-var-6';
	}

	public function get_title(): string {
		return esc_html__( 'Portfolio', 'modulix' );
	}
}
