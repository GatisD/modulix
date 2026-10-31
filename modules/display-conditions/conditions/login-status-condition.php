<?php
namespace Modulix\Modules\DisplayConditions\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/conditions/login-status-condition.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DisplayConditions\Classes\Comparator_Provider;
use Modulix\Modules\DisplayConditions\Classes\Comparators_Checker;
use Modulix\Modules\DisplayConditions\Conditions\Base\Condition_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Login_Status_Condition extends Condition_Base {

	public function get_name() {
		return 'login_status';
	}

	public function get_label() {
		return esc_html__( 'Login Status', 'modulix' );
	}

	public function get_group() {
		return 'user';
	}

	public function check( $args ) : bool {
		return Comparators_Checker::check_equality( $args['comparator'], 'logged_in' === $args['status'], is_user_logged_in() );
	}

	public function get_options() {
		$comparators = Comparator_Provider::get_comparators(
			[
				Comparator_Provider::COMPARATOR_IS,
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
			'status',
			[
				'type' => Controls_Manager::SELECT,
				'options' => [
					'logged_in' => esc_html__( 'Logged In', 'modulix' ),
					'logged_out' => esc_html__( 'Logged Out', 'modulix' ),
				],
				'default' => 'logged_in',
			]
		);
	}
}
