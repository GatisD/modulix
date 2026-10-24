<?php
namespace Modulix\Modules\LoopBuilder\Files\Css;
// Modulix: derived from Elementor Pro 4.0.4, modules/loop-builder/files/css/loop-preview.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Files\CSS\Post_Preview;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Loop_Preview extends Post_Preview {

	use Loop_Css_Trait;

	/**
	 * Get CSS file name.
	 *
	 * Retrieve the CSS file name.
	 *
	 * @access public
	 *
	 * @return string CSS file name.
	 */
	public function get_name() {
		return 'loop';
	}
}
