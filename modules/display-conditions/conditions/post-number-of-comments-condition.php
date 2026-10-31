<?php

namespace Modulix\Modules\DisplayConditions\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/conditions/post-number-of-comments-condition.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DisplayConditions\Classes\Comparator_Provider;
use Modulix\Modules\DisplayConditions\Classes\Comparators_Checker;
use Modulix\Modules\DisplayConditions\Conditions\Base\Condition_Base;

class Post_Number_Of_Comments_Condition extends Condition_Base {

	public function get_name() {
		return 'number_of_comments';
	}

	public function get_label() {
		return esc_html__( 'Number of Comments', 'modulix' );
	}

	public function get_group() {
		return esc_html__( 'post', 'modulix' );
	}

	public function check( $args ): bool {
		$actual_number_of_comments = $this->wordpress_adapter->get_comments_number();

		return Comparators_Checker::check_numeric_constraints( $args['comparator'], (int) $args['number_of_comments'], (int) $actual_number_of_comments );
	}

	public function get_options() {
		$comparators = Comparator_Provider::get_comparators(
			[
				Comparator_Provider::COMPARATOR_IS_GREATER_THAN_INCLUSIVE,
				Comparator_Provider::COMPARATOR_IS_LESS_THAN_INCLUSIVE,
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
			'number_of_comments',
			[
				'type' => Controls_Manager::TEXT,
				'input_type' => 'number',
				'variant' => 'number',
				'placeholder' => 'Type a number...',
				'step' => 1,
				'min' => 0,
				'default' => 1,
			]
		);
	}
}
