<?php
namespace Modulix\Base;
// Modulix: derived from Elementor Pro 4.0.4, base/base-widget.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): licence trait (Base_Widget_Trait) and query-control import trait (On_Import_Trait) removed.

use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class Base_Widget extends Widget_Base {

}
