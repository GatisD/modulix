<?php


namespace Modulix\Modules\DisplayConditions\Classes;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/classes/and-condition.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

class And_Condition {
	/**
	 * @var $conditions_manager Object
	 */
	private $conditions_manager;

	/**
	 * @var $conditions array
	 */
	private $conditions;

	public function __construct( $conditions_manager, $conditions ) {
		$this->conditions_manager = $conditions_manager;
		$this->conditions = $conditions;
	}

	public function check() {
		foreach ( $this->conditions as $condition_options ) {
			$condition_result = $this->is_condition_passing_check( $condition_options );

			if ( ! $condition_result ) {
				return false;
			}
		}

		return true;
	}

	private function is_condition_passing_check( $condition_options ) {
		$condition_instance = $this->get_condition_instance( $condition_options );

		return $condition_instance
			? $condition_instance->check( $condition_options )
			: true;
	}

	private function get_condition_instance( $condition ) {
		if ( ! isset( $condition['condition'] ) ) {
			return false;
		}

		return $this->conditions_manager->get_condition( $condition['condition'] );
	}
}
