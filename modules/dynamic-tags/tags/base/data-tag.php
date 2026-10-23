<?php

namespace Modulix\Modules\DynamicTags\Tags\Base;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/base/data-tag.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\DynamicTags\Data_Tag as Base_Data_Tag;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class Data_Tag extends Base_Data_Tag {

	use Tag_Trait;
}
