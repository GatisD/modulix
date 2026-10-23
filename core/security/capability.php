<?php

namespace Modulix\Core\Security;
// Modulix: derived from Elementor Pro 4.0.4, core/security/capability.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Capability {
	/**
	 * 'edit_post' is one of the meta-capabilities which is the combination of
	 *  edit_posts and edit_others_posts primitive capabilities
	 *
	 *  https://wordpress.org/documentation/article/roles-and-capabilities/
	 *  https://learn.wordpress.org/tutorial/custom-post-types-and-capabilities/
	 */
	const EDIT_POST_META = 'edit_post';
	const EDIT_POSTS = 'edit_posts';
	const READ_PRIVATE_POSTS = 'read_private_posts';
}
