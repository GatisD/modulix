<?php
namespace Modulix\Modules\LoopBuilder\Skins;
// Modulix: derived from Elementor Pro 4.0.4, modules/loop-builder/skins/skin-loop-post.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\LoopBuilder\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Skin_Loop_Post extends Skin_Loop_Base {

	public function get_id() {
		return Module::LOOP_POST_SKIN_ID;
	}

	public function get_title() {
		return esc_html__( 'Posts', 'modulix' );
	}
}
