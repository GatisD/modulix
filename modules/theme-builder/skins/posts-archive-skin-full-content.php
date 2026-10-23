<?php
namespace Modulix\Modules\ThemeBuilder\Skins;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/skins/posts-archive-skin-full-content.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\Posts\Skins\Skin_Full_Content;
use Modulix\Modules\Posts\Skins\Skin_Content_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Posts_Archive_Skin_Full_Content extends Skin_Full_Content {
	use Skin_Content_Base;
	use Posts_Archive_Skin_Base;

	public function get_id() {
		return 'archive_full_content';
	}

	/* Remove `posts_per_page` control */
	protected function register_post_count_control() {}
}
