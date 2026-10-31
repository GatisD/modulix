<?php

namespace Modulix\Modules\DisplayConditions\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/conditions/archive-of-category-condition.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\DisplayConditions\Classes\Comparator_Provider;
use Modulix\Modules\DisplayConditions\Conditions\Base\Archive_Condition_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Archive_Of_Category_Condition extends Archive_Condition_Base {
	public function __construct() {
		parent::__construct( 'categories' );
	}

	public function get_name() {
		return 'archive_of_categories';
	}

	public function get_label(): string {
		return esc_html__( 'Of Categories', 'modulix' );
	}

	public function check( $args ): bool {
		return parent::check_is_of_taxonomy( $args );
	}

	protected function is_of_taxonomy( $args ): bool {
		return is_category( array_column( $args['categories'], 'id' ) );
	}

	protected function get_taxonomy() {
		return 'category';
	}
}
