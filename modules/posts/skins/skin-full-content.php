<?php
namespace Modulix\Modules\Posts\Skins;
// Modulix: derived from Elementor Pro 4.0.4, modules/posts/skins/skin-full-content.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Skin_Full_Content extends Skin_Classic {
	use Skin_Content_Base;

	public function get_id() {
		return 'full_content';
	}
}
