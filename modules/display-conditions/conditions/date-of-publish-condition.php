<?php
namespace Modulix\Modules\DisplayConditions\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/conditions/date-of-publish-condition.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\DisplayConditions\Conditions\Base\Date_Condition_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Date_Of_Publish_Condition extends Date_Condition_Base {
	const CONDITION_KEY = 'date';
	const GROUP_KEY = 'date';

	public function __construct() {
		parent::__construct( self::CONDITION_KEY, self::GROUP_KEY );
	}

	public function get_name() {
		return 'date_of_publish';
	}

	public function get_group() {
		return 'post';
	}

	public function get_label() {
		return esc_html__( 'Date of Publish', 'modulix' );
	}

	public function check( $args ): bool {
		return parent::check_date( $args, $this->get_post_date() );
	}

	private function get_post_date() {
		return date_create_from_format(
			self::DATE_FORMAT,
			get_the_date( self::DATE_FORMAT, get_the_ID() )
		);
	}
}
