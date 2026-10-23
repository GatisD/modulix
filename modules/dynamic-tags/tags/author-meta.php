<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/author-meta.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\DynamicTags\Tags\Base\Author_Tag;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Author_Meta extends Author_Tag {

	public function get_name() {
		return 'author-meta';
	}

	public function get_title() {
		return esc_html__( 'Author Meta', 'modulix' );
	}

	protected function register_controls() {
		$this->add_control(
			'key',
			[
				'label' => esc_html__( 'Meta Key', 'modulix' ),
				'ai' => [
					'active' => false,
				],
			]
		);
	}
}
