<?php

namespace Modulix\Modules\DisplayConditions\Classes\DynamicTags;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/classes/dynamic-tags/data-provider.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

interface Data_Provider {

	/**
	 * @param array $args
	 * @return string | bool
	 */
	public function get_value( array $args );

	public function get_control_options(): array;
}
