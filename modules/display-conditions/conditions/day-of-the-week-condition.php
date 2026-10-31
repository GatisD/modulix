<?php
namespace Modulix\Modules\DisplayConditions\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/conditions/day-of-the-week-condition.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DisplayConditions\Classes\Comparator_Provider;
use Modulix\Modules\DisplayConditions\Classes\Comparators_Checker;
use Modulix\Modules\DisplayConditions\Conditions\Base\Condition_Base;
use Modulix\Modules\DisplayConditions\Conditions\Base\Date_Condition_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Day_Of_The_Week_Condition extends Condition_Base {
	const CONDITION_KEY = 'days';

	public function get_name() {
		return 'day_of_the_week';
	}

	public function get_label() {
		return esc_html__( 'Day of the week', 'modulix' );
	}

	public function get_group() {
		return 'date';
	}

	public function check( $args ) : bool {
		return Comparators_Checker::check_array_contains( $args['comparator'], [ strtolower( gmdate( 'l' ) ) ], $args['days'] );
	}

	public function get_options() {
		$comparators = Comparator_Provider::get_comparators(
			[
				Comparator_Provider::COMPARATOR_IS_ONE_OF,
				Comparator_Provider::COMPARATOR_IS_NONE_OF,
			]
		);

		$this->add_control(
			'comparator',
			[
				'type' => Controls_Manager::SELECT,
				'options' => $comparators,
				'default' => Comparator_Provider::COMPARATOR_IS_ONE_OF,
			]
		);

		$this->add_control(
			self::CONDITION_KEY,
			[
				'type' => Controls_Manager::SELECT2,
				'options' => [
					'monday' => esc_html__( 'Monday', 'modulix' ),
					'tuesday' => esc_html__( 'Tuesday', 'modulix' ),
					'wednesday' => esc_html__( 'Wednesday', 'modulix' ),
					'thursday' => esc_html__( 'Thursday', 'modulix' ),
					'friday' => esc_html__( 'Friday', 'modulix' ),
					'saturday' => esc_html__( 'Saturday', 'modulix' ),
					'sunday' => esc_html__( 'Sunday', 'modulix' ),
				],
				'multiple' => true,
				'required' => true,
			]
		);

		$this->add_control(
			'time_type',
			[
				'type' => Controls_Manager::SELECT,
				'options' => Date_Condition_Base::get_time_options(),
				'default' => Date_Condition_Base::OPTION_SERVER,
				'disabled_options' => [ Date_Condition_Base::OPTION_CLIENT ],
			]
		);
	}
}
