<?php
namespace Modulix\Modules\ThemeBuilder\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/conditions/in-taxonomy.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\QueryControl\Module as QueryModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class In_Taxonomy extends Condition_Base {


	/**
	 * @var \WP_Taxonomy
	 */
	private $taxonomy;

	public static function get_type() {
		return 'singular';
	}

	public static function get_priority() {
		return 40;
	}

	public function __construct( $data ) {
		parent::__construct();

		$this->taxonomy = $data['object'];
	}

	public function get_name() {
		return 'in_' . $this->taxonomy->name;
	}

	public function get_label() {
		/* translators: %s: Taxonomy label. */
		return sprintf( esc_html__( 'In %s', 'modulix' ), $this->taxonomy->labels->singular_name );
	}

	public function check( $args ) {
		return is_singular() && has_term( (int) $args['id'], $this->taxonomy->name );
	}

	protected function register_controls() {
		$this->add_control(
			'taxonomy',
			[
				'section' => 'settings',
				'type' => QueryModule::QUERY_CONTROL_ID,
				'select2options' => [
					'dropdownCssClass' => 'elementor-conditions-select2-dropdown',
				],
				'autocomplete' => [
					'object' => QueryModule::QUERY_OBJECT_TAX,
					'display' => 'detailed',
					'by_field' => 'term_id',
					'query' => [
						'taxonomy' => $this->taxonomy->name,
					],
				],
			]
		);
	}
}
