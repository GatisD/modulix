<?php

namespace Modulix\Modules\DisplayConditions\Classes;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/classes/or-condition.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

class Or_Condition {
	/**
	 * @var $conditions_manager Object
	 */
	private $conditions_manager;

	/**
	 * @var $and_conditions And_Condition[]
	 */
	private $and_conditions;

	public function __construct( $conditions_manager, $sets ) {
		$this->conditions_manager = $conditions_manager;
		$this->set_and_conditions( $sets );
	}

	public function check() {
		if ( empty( $this->and_conditions ) ) {
			return true;
		}

		foreach ( $this->and_conditions as $condition ) {
			if ( $condition->check() ) {
				return true;
			}
		}

		return false;
	}

	private function set_and_conditions( $groups ) {
		$this->and_conditions = array_map( function ( $condition ) {
			return new And_Condition( $this->conditions_manager, $condition );
		}, $groups );
	}
}
