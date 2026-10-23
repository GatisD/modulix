<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/post-featured-image.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DynamicTags\Tags\Base\Pro_Data_Tag;
use Modulix\Modules\DynamicTags\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Post_Featured_Image extends Pro_Data_Tag {

	public function get_name() {
		return 'post-featured-image';
	}

	public function get_group() {
		return Module::POST_GROUP;
	}

	public function get_categories() {
		return [
			Module::IMAGE_CATEGORY,
			Module::MEDIA_CATEGORY,
		];
	}

	public function get_title() {
		return esc_html__( 'Featured Image', 'modulix' );
	}

	public function get_value( array $options = [] ) {
		$thumbnail_id = get_post_thumbnail_id();

		if ( $thumbnail_id ) {
			$image_data = [
				'id' => $thumbnail_id,
				'url' => wp_get_attachment_image_src( $thumbnail_id, 'full' )[0],
			];
		} else {
			$image_data = $this->get_settings( 'fallback' );
		}

		return $image_data;
	}

	protected function register_controls() {
		$this->add_control(
			'fallback',
			[
				'label' => esc_html__( 'Fallback', 'modulix' ),
				'type' => Controls_Manager::MEDIA,
			]
		);
	}
}
