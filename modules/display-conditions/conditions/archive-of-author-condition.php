<?php

namespace Modulix\Modules\DisplayConditions\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/conditions/archive-of-author-condition.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DisplayConditions\Classes\Comparator_Provider;
use Modulix\Modules\DisplayConditions\Conditions\Base\Condition_Base;
use Modulix\Modules\QueryControl\Module as QueryControlModule;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Archive_Of_Author_Condition extends Condition_Base {

	public function get_name() {
		return 'archive_of_authors';
	}

	public function get_label(): string {
		return esc_html__( 'Of Authors', 'modulix' );
	}

	public function get_group() {
		return 'archive';
	}

	public function check( $args ) : bool {
		if ( empty( $args['authors'] ) ) {
			return parent::check( $args );
		}

		$author_ids = array_column( $args['authors'], 'id' );

		switch ( $args['comparator'] ) {
			case Comparator_Provider::COMPARATOR_IS:
				return $this->wordpress_adapter->is_author( $author_ids );

			case Comparator_Provider::COMPARATOR_IS_NOT:
				return ! $this->wordpress_adapter->is_author( $author_ids );

			default:
				return false;
		}

	}

	public function get_options() {
		$comparators = Comparator_Provider::get_comparators( [
			Comparator_Provider::COMPARATOR_IS,
			Comparator_Provider::COMPARATOR_IS_NOT,
		] );

		$this->add_control(
			'comparator',
			[
				'type' => Controls_Manager::SELECT,
				'options' => $comparators,
				'default' => Comparator_Provider::COMPARATOR_IS,
			]
		);

		$this->add_control(
			'authors',
			[
				'type' => QueryControlModule::QUERY_CONTROL_ID,
				'autocomplete' => [
					'object' => QueryControlModule::QUERY_OBJECT_AUTHOR,
				],
				'multiple' => true,
				'required' => true,
				'placeholder' => esc_html__( 'Type to search', 'modulix' ),
			]
		);
	}
}
