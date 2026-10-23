<?php

namespace Modulix\Modules\QueryControl\Controls;
// Modulix: derived from Elementor Pro 4.0.4, modules/query-control/controls/query.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Control_Select2;
use Modulix\Modules\QueryControl\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Query extends Control_Select2 {

	public function get_type() {
		return 'query';
	}

	/**
	 * 'query' can be used for passing query args in the structure and format used by WP_Query.
	 * @return array
	 */
	protected function get_default_settings() {
		return array_merge(
			parent::get_default_settings(), [
				'query' => '',
			]
		);
	}

	/**
	 * Update control settings using mapping config
	 *
	 * @param $value
	 * @param array $control_args
	 * @param array $config
	 *
	 * @return mixed
	 */
	public function on_import_update_settings( $value, array $control_args, array $config ) {
		switch ( $control_args['autocomplete']['object'] ) {
			case Module::QUERY_OBJECT_POST:
			case Module::QUERY_OBJECT_LIBRARY_TEMPLATE:
				return $this->replace_id_from_mapping( $value, $config['post_ids'] );
			case Module::QUERY_OBJECT_TAX:
				return $this->replace_id_from_mapping( $value, $config['term_ids'] );
			default:
				return $value;
		}
	}

	/**
	 * replace id from config
	 *
	 * @param mixed $value
	 * @param array $mapping
	 *
	 * @return string
	 */
	private function replace_id_from_mapping( $value, array $mapping ): string {
		return $mapping[ $value ] ?? $value;
	}

}
