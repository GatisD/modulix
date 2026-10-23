<?php

namespace Modulix\Modules\QueryControl\Controls;
// Modulix: derived from Elementor Pro 4.0.4, modules/query-control/controls/group-control-taxonomy.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;

class Group_Control_Taxonomy extends Group_Control_Query {

	public static function get_type() {
		return 'taxonomy-query';
	}

	protected function get_fields_array( $name ) {
		$tab_keys = $this->get_tabs_keys( $name . '_' );
		$fields = parent::get_fields_array( $name );
		$new_fields = [];

		foreach ( $fields as $key => $field ) {
			if ( 'query_args' === $key ) {
				$new_fields['filter_by'] = [
					'label' => esc_html__( 'Filter By', 'modulix' ),
					'type' => Controls_Manager::SELECT,
					'default' => 'show_all',
					'options' => [
						'show_all' => esc_html__( 'Show All', 'modulix' ),
						'manual_selection' => esc_html__( 'Manual Selection', 'modulix' ),
					],
					'tabs_wrapper' => $tab_keys['tabs_wrapper'],
					'inner_tab' => $tab_keys['include_wrapper'],
				];
			}

			$new_fields[ $key ] = $field;
		}

		return $new_fields;
	}
}
