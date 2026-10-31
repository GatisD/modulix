<?php
namespace Modulix\Modules\DisplayConditions\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/conditions/user-role-condition.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DisplayConditions\Classes\Comparator_Provider;
use Modulix\Modules\DisplayConditions\Classes\Comparators_Checker;
use Modulix\Modules\DisplayConditions\Conditions\Base\Condition_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class User_Role_Condition extends Condition_Base {
	const CONDITION_KEY = 'roles';

	public function get_name() {
		return 'user_role';
	}

	public function get_label() {
		return esc_html__( 'Role', 'modulix' );
	}

	public function get_group() {
		return 'user';
	}

	public function check( $args ) : bool {
		$current_user = wp_get_current_user();
		$current_user_roles = $current_user ? $current_user->roles : [];

		return Comparators_Checker::check_array_contains( $args['comparator'], $current_user_roles, $args['roles'] );
	}

	public function get_options() {
		$user_roles = $this->get_available_roles();

		$comparators = Comparator_Provider::get_comparators(
			[
				Comparator_Provider::COMPARATOR_IS_ONE_OF,
				Comparator_Provider::COMPARATOR_IS_NONE_OF,
			]
		);

		$this->add_control( 'comparator', [
			'type' => Controls_Manager::SELECT,
			'options' => $comparators,
			'default' => Comparator_Provider::COMPARATOR_IS_ONE_OF,
		] );

		$this->add_control( self::CONDITION_KEY, [
			'type' => Controls_Manager::SELECT2,
			'options' => $user_roles,
			'multiple' => true,
			'required' => true,
			'default' => [],
		] );
	}

	private function get_available_roles(): array {
		$user_roles = [];

		foreach ( get_editable_roles() as $role_slug => $role_data ) {
			$role = $role_data['name'];
			$user_roles[ $role_slug ] = esc_html( $role );
		}

		return $user_roles;
	}
}
