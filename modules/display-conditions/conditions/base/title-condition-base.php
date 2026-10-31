<?php

namespace Modulix\Modules\DisplayConditions\Conditions\Base;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/conditions/base/title-condition-base.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DisplayConditions\Classes\Comparator_Provider;
use Modulix\Modules\DisplayConditions\Classes\Comparators_Checker;
use Modulix\Modules\QueryControl\Module as QueryControlModule;

abstract class Title_Condition_Base extends Condition_Base {
	abstract protected function get_query();

	public function check( $args ) : bool {
		$comparator = $args['comparator'];
		$title = get_the_title();
		$titles = array_map( function ( $id ) {
			return get_the_title( $id );
		}, array_column( $args['titles'], 'id' ) );
		return Comparators_Checker::check_array_contains( $comparator, [ $title ], $titles );
	}

	public function get_options() {
		$comparators = Comparator_Provider::get_comparators(
			[
				Comparator_Provider::COMPARATOR_IS,
				Comparator_Provider::COMPARATOR_IS_NOT,
			]
		);

		$this->add_control(
			'comparator',
			[
				'type' => Controls_Manager::SELECT,
				'options' => $comparators,
				'default' => Comparator_Provider::COMPARATOR_IS,
			]
		);

		$this->add_control(
			'titles',
			[
				'type' => QueryControlModule::QUERY_CONTROL_ID,
				'autocomplete' => [
					'object' => QueryControlModule::QUERY_OBJECT_POST,
					'query' => $this->get_query(),
				],
				'multiple' => true,
				'placeholder' => esc_html__( 'Type to search', 'modulix' ),
				'required' => true,
			]
		);
	}
}
