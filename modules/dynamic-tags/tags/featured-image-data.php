<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/featured-image-data.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DynamicTags\Tags\Base\Pro_Tag;
use Modulix\Modules\DynamicTags\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Featured_Image_Data extends Pro_Tag {

	public function get_name() {
		return 'featured-image-data';
	}

	public function get_group() {
		return Module::MEDIA_GROUP;
	}

	public function get_categories() {
		return [
			Module::TEXT_CATEGORY,
			Module::URL_CATEGORY,
			Module::POST_META_CATEGORY,
		];
	}

	public function get_title() {
		return esc_html__( 'Featured Image Data', 'modulix' );
	}

	public function get_editor_config() {
		$config = parent::get_editor_config();

		$config['display_conditions'] = [
			'featured_image_data_title' => [
				'label' => esc_html__( 'Image Title', 'modulix' ),
				'settings' => [ 'attachment_data' => 'title' ],
				'group' => 'featured_image',
			],
			'featured_image_data_alt' => [
				'label' => esc_html__( 'Image Alt', 'modulix' ),
				'settings' => [ 'attachment_data' => 'alt' ],
				'group' => 'featured_image',
			],
			'featured_image_data_caption' => [
				'label' => esc_html__( 'Image Caption', 'modulix' ),
				'settings' => [ 'attachment_data' => 'caption' ],
				'group' => 'featured_image',
			],
		];

		return $config;
	}

	private function get_attacment() {
		$id = get_post_thumbnail_id();

		if ( ! $id ) {
			return false;
		}

		return get_post( $id );
	}

	public function render() {
		$settings = $this->get_settings();
		$attachment = $this->get_attacment();

		if ( ! $attachment ) {
			return;
		}

		$value = '';

		switch ( $settings['attachment_data'] ) {
			case 'alt':
				$value = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
				break;
			case 'caption':
				$value = $attachment->post_excerpt;
				break;
			case 'description':
				$value = $attachment->post_content;
				break;
			case 'href':
				$value = get_permalink( $attachment->ID );
				break;
			case 'src':
				$value = $attachment->guid;
				break;
			case 'title':
				$value = $attachment->post_title;
				break;
		}

		echo wp_kses_post( $value );
	}

	protected function register_controls() {

		$this->add_control(
			'attachment_data',
			[
				'label' => esc_html__( 'Data', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'title',
				'options' => [
					'title' => esc_html__( 'Title', 'modulix' ),
					'alt' => esc_html__( 'Alt', 'modulix' ),
					'caption' => esc_html__( 'Caption', 'modulix' ),
					'description' => esc_html__( 'Description', 'modulix' ),
					'src' => esc_html__( 'File URL', 'modulix' ),
					'href' => esc_html__( 'Attachment URL', 'modulix' ),
				],
			]
		);
	}
}
