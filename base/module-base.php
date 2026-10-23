<?php
namespace Modulix\Base;
// Modulix: derived from Elementor Pro 4.0.4, base/module-base.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Base\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class Module_Base extends Module {
	// This class was needed in the past and not being removed for future functionalities that might be needed for all classes that still extend it.
}
