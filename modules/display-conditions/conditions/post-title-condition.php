<?php
namespace Modulix\Modules\DisplayConditions\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/conditions/post-title-condition.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\DisplayConditions\Conditions\Base\Title_Condition_Base;

class Post_Title_Condition extends Title_Condition_Base {

	public function get_name() {
		return 'post_title';
	}

	public function get_group() {
		return 'post';
	}

	public function get_label() {
		return esc_html__( 'Post Title', 'modulix' );
	}

	protected function get_query() {
		return [
			'post_status' => 'publish',
			'post_type' => 'post',
		];
	}
}
