<?php
namespace Modulix\Modules\DisplayConditions\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/conditions/from-url-condition.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DisplayConditions\Classes\Comparator_Provider;
use Modulix\Modules\DisplayConditions\Classes\Comparators_Checker;
use Modulix\Modules\DisplayConditions\Conditions\Base\Condition_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class From_URL_Condition extends Condition_Base {

	public function get_label() {
		return esc_html__( 'From URL', 'modulix' );
	}

	public function get_options() {
		$comparators = Comparator_Provider::get_comparators(
			[
				Comparator_Provider::COMPARATOR_IS,
				Comparator_Provider::COMPARATOR_IS_NOT,
				Comparator_Provider::COMPARATOR_CONTAINS,
				Comparator_Provider::COMPARATOR_NOT_CONTAIN,
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
			'from_url',
			[
				'label' => esc_html__( 'URL', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'URL', 'modulix' ),
				'required' => true,
			]
		);
	}

	public function get_name() {
		return 'from_url';
	}

	public function wp_get_referer() {
		return wp_get_raw_referer();
	}

	public function check( $args ) : bool {
		$referrer = $this->wp_get_referer();

		return Comparators_Checker::check_string_contains( $args['comparator'], $args['from_url'], $referrer );
	}
}
