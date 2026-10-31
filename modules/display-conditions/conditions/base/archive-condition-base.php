<?php

namespace Modulix\Modules\DisplayConditions\Conditions\Base;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/conditions/base/archive-condition-base.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DisplayConditions\Classes\Comparator_Provider;
use Modulix\Modules\QueryControl\Module as QueryControlModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class Archive_Condition_Base extends Condition_Base {

	private string $condition_key;

	public function __construct( $condition_key ) {
		$this->condition_key = $condition_key;

		parent::__construct();
	}

	abstract public function get_name();

	abstract public function get_label();

	abstract protected function get_taxonomy();

	public function get_group(): string {
		return 'archive';
	}

	abstract protected function is_of_taxonomy( $args ): bool;

	protected function check_is_of_taxonomy( $args ) {
		switch ( $args['comparator'] ) {
			case Comparator_Provider::COMPARATOR_IS:
				return $this->is_of_taxonomy( $args );
			case Comparator_Provider::COMPARATOR_IS_NOT:
				return ! $this->is_of_taxonomy( $args );
			default:
				return false;
		}
	}

	public function get_options() {
		$comparators = Comparator_Provider::get_comparators( [
			Comparator_Provider::COMPARATOR_IS,
			Comparator_Provider::COMPARATOR_IS_NOT,
		] );
		$taxonomy = $this->get_taxonomy();

		$this->add_control(
			'comparator',
			[
				'type' => Controls_Manager::SELECT,
				'options' => $comparators,
				'default' => Comparator_Provider::COMPARATOR_IS,
			]
		);

		$this->add_control(
			$this->condition_key,
			[
				'type' => QueryControlModule::QUERY_CONTROL_ID,
				'autocomplete' => [
					'object' => QueryControlModule::QUERY_OBJECT_TAX,
					'query' => [
						'taxonomy' => $taxonomy,
					],
				],
				'multiple' => true,
				'required' => true,
				'placeholder' => esc_html__( 'Type to search', 'modulix' ),
			]
		);
	}
}
