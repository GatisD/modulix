<?php
namespace Modulix\Modules\DisplayConditions\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/conditions/page-parent-condition.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DisplayConditions\Classes\Comparator_Provider;
use Modulix\Modules\DisplayConditions\Classes\Comparators_Checker;
use Modulix\Modules\QueryControl\Module as QueryControlModule;
use Modulix\Modules\DisplayConditions\Conditions\Base\Condition_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Page_Parent_Condition extends Condition_Base {

	public function get_name() {
		return 'page_parent';
	}

	public function get_label() {
		return esc_html__( 'Page Parent', 'modulix' );
	}

	public function get_group() {
		return 'page';
	}

	public function check( $args ) : bool {
		if ( empty( $args['pages'] ) ) {
			return true;
		}

		$current_post = get_post();

		$parent_page_ids = array_column( $args['pages'], 'id' );

		return Comparators_Checker::check_array_contains( $args['comparator'], [ $current_post->post_parent ], $parent_page_ids );
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
			'pages',
			[
				'type' => QueryControlModule::QUERY_CONTROL_ID,
				'autocomplete' => [
					'object' => QueryControlModule::QUERY_OBJECT_POST,
					'query' => [
						'post_status' => 'publish',
						'post_type' => 'page',
					],
				],
				'multiple' => true,
				'placeholder' => esc_html__( 'Type to search', 'modulix' ),
				'required' => true,
			]
		);
	}
}
