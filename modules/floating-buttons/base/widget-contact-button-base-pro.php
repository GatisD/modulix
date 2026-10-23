<?php

namespace Modulix\Modules\FloatingButtons\Base;
// Modulix: derived from Elementor Pro 4.0.4, modules/floating-buttons/base/widget-contact-button-base-pro.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Modules\FloatingButtons\Base\Widget_Contact_Button_Base;
use Modulix\Plugin;

abstract class Widget_Contact_Button_Base_Pro extends Widget_Contact_Button_Base {

	public function has_widget_inner_wrapper(): bool {
		return ! Plugin::elementor()->experiments->is_feature_active( 'e_optimized_markup' );
	}
}
