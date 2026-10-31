<?php
namespace Modulix\Modules\DisplayConditions\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/conditions/post-author-condition.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

class Post_Author_Condition extends Page_Author_Condition {

	public function get_name() {
		return 'post_author';
	}

	public function get_group() {
		return 'post';
	}
}
