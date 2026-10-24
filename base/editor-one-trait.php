<?php

namespace Modulix\Base;
// Modulix: derived from Elementor Pro 4.0.4, base/editor-one-trait.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Editor_One_Trait {

	protected function is_editor_one_active(): bool {
		return (bool) Plugin::elementor()->modules_manager->get_modules( 'editor-one' );
	}
}
