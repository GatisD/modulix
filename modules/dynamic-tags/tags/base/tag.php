<?php

namespace Modulix\Modules\DynamicTags\Tags\Base;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/base/tag.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): query-control import trait removed (as in Base_Widget and Data_Tag).

use Elementor\Core\DynamicTags\Tag as Base_Tag;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class Tag extends Base_Tag {

	use Tag_Trait;
}
