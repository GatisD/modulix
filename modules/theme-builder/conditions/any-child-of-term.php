<?php
namespace Modulix\Modules\ThemeBuilder\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/conditions/any-child-of-term.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Any_Child_Of_Term extends Child_Of_Term {

	private $taxonomy;

	public function get_name() {
		return 'any_child_of_' . $this->taxonomy->name;
	}

	public function get_label() {
		/* translators: %s: Singular taxonomy label. */
		return sprintf( esc_html__( 'Any child %s of', 'modulix' ), $this->taxonomy->labels->singular_name );
	}

	public function __construct( $data ) {
		parent::__construct( $data );

		$this->taxonomy = $data['object'];
	}

	public function check( $args ) {
		$id = (int) $args['id'];
		/**
		 * @var \WP_Term $current
		 */
		$current = get_queried_object();
		if ( ! $this->is_term() || 0 === $current->parent ) {
			return false;
		}

		while ( $current->parent > 0 ) {
			if ( $id === $current->parent ) {
				return true;
			}
			$current = get_term_by( 'id', $current->parent, $current->taxonomy );
		}

		return $id === $current->parent;
	}
}
