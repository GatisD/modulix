<?php

namespace Modulix\Modules\DynamicTags\Tags\Base;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/base/tag-trait.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): licence check removed.


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait Tag_Trait {

	protected function render_taxonomy_content_by_key( string $key = 'name' ): void {
		global $wp_query;

		if ( ! isset( $wp_query->loop_term ) || ! is_object( $wp_query->loop_term ) ) {
			return;
		}

		$content = '';

		if ( isset( $wp_query->loop_term->$key ) ) {
			$content = $wp_query->loop_term->$key;
		}

		echo wp_kses_post( $content );
	}

	protected function get_data_id_from_taxonomy_loop_query() {
		global $wp_query;

		if ( isset( $wp_query->loop_term ) && isset( $wp_query->loop_term->term_id ) ) {
			return $wp_query->loop_term->term_id;
		}

		return 0;
	}
}
