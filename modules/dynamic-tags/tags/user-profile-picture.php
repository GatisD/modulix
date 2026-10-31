<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/user-profile-picture.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-24): the current user's avatar URL is normalized before use.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class User_Profile_Picture extends Author_Profile_Picture {
	public function get_name() {
		return 'user-profile-picture';
	}
	public function get_title() {
		return esc_html__( 'User Profile Picture', 'modulix' );
	}
	public function get_value( array $options = [] ) {
		return [
			'id' => '',
			'url' => esc_url_raw( get_avatar_url( get_current_user_id() ) ),
		];
	}
}
