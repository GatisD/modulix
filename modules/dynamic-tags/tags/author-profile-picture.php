<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/author-profile-picture.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\DynamicTags\Tags\Base\Pro_Data_Tag;
use Modulix\Core\Utils;
use Modulix\Modules\DynamicTags\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Author_Profile_Picture extends Pro_Data_Tag {

	public function get_name() {
		return 'author-profile-picture';
	}

	public function get_title() {
		return esc_html__( 'Author Profile Picture', 'modulix' );
	}

	public function get_group() {
		return Module::AUTHOR_GROUP;
	}

	public function get_categories() {
		return [
			Module::IMAGE_CATEGORY,
			Module::MEDIA_CATEGORY,
		];
	}

	public function get_value( array $options = [] ) {
		Utils::set_global_authordata();

		return [
			'id' => '',
			'url' => get_avatar_url( (int) get_the_author_meta( 'ID' ) ),
		];
	}
}
