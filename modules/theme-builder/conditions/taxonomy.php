<?php
namespace Modulix\Modules\ThemeBuilder\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/conditions/taxonomy.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\QueryControl\Module as QueryModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Taxonomy extends Condition_Base {

	private $taxonomy;

	public static function get_type() {
		return 'archive';
	}

	public static function get_priority() {
		return 70;
	}

	public function __construct( $data ) {
		parent::__construct();

		$this->taxonomy = $data['object'];
	}

	public function get_name() {
		return $this->taxonomy->name;
	}

	public function get_label() {
		return $this->taxonomy->label;
	}

	public function check( $args ) {
		$taxonomy = $this->get_name();
		$id = (int) $args['id'];

		if ( 'category' === $taxonomy ) {
			return is_category( $id );
		}

		if ( 'post_tag' === $taxonomy ) {
			return is_tag( $id );
		}

		return is_tax( $taxonomy, $id );
	}

	protected function register_controls() {
		$this->add_control(
			'taxonomy',
			[
				'section' => 'settings',
				'type' => QueryModule::QUERY_CONTROL_ID,
				'options' => [
					'' => esc_html__( 'All', 'modulix' ),
				],
				'autocomplete' => [
					'object' => QueryModule::QUERY_OBJECT_TAX,
					'by_field' => 'term_id',
					'query' => [
						'taxonomy' => $this->taxonomy->name,
					],
				],
			]
		);
	}
}
