<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/lightbox.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-24): media URLs are normalized and attachment metadata requires read access.

use Modulix\Modules\DynamicTags\Tags\Base\Pro_Tag;
use Modulix\Modules\DynamicTags\Module;
use Elementor\Controls_Manager;
use Elementor\Embed;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class Lightbox extends Pro_Tag {

	public function get_name() {
		return 'lightbox';
	}

	public function get_title() {
		return esc_html__( 'Lightbox', 'modulix' );
	}

	public function get_group() {
		return Module::ACTION_GROUP;
	}

	public function get_categories() {
		return [ Module::URL_CATEGORY ];
	}

	// Keep Empty to avoid default advanced section
	protected function register_advanced_section() {}

	public function register_controls() {
		$this->add_control(
			'type',
			[
				'label' => esc_html__( 'Type', 'modulix' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'video' => [
						'title' => esc_html__( 'Video', 'modulix' ),
						'icon' => 'eicon-video-camera',
						'atomic-icon' => 'BrandYoutubeIcon',
					],
					'image' => [
						'title' => esc_html__( 'Image', 'modulix' ),
						'icon' => 'eicon-image-bold',
						'atomic-icon' => 'PhotoIcon',
					],
				],
				'default' => 'video',
			]
		);

		$this->add_control(
			'image',
			[
				'label' => esc_html__( 'Image', 'modulix' ),
				'type' => Controls_Manager::MEDIA,
				'condition' => [
					'type' => 'image',
				],
			]
		);

		$this->add_control(
			'video_url',
			[
				'label' => esc_html__( 'Video URL', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
				'condition' => [
					'type' => 'video',
				],
				'ai' => [
					'active' => false,
				],
			]
		);
	}

	private function get_image_settings( $settings ) {
		$attachment_id = $this->get_image_attachment_id( $settings['image'] );
		if ( $attachment_id ) {
			$attachment = get_post( $attachment_id );
			$parent = $attachment && ! empty( $attachment->post_parent ) ? get_post( $attachment->post_parent ) : null;
			$can_read_attachment = current_user_can( 'read_post', $attachment_id );
			$can_read_parent = $parent && current_user_can( 'read_post', $parent->ID );

			if ( ! \Modulix\Security\DynamicContent::lightbox_attachment_allowed( $attachment, $can_read_attachment, $parent, $can_read_parent ) ) {
				return [];
			}

			$image_src = wp_get_attachment_image_src( $attachment_id, 'full' );
			if ( empty( $image_src[0] ) ) {
				return [];
			}

			return array_merge(
				[
					'url' => esc_url_raw( $image_src[0] ),
					'type' => 'image',
				],
				Plugin::elementor()->images_manager->get_lightbox_image_attributes( $attachment_id )
			);
		}

		$image_settings = [
			'url' => esc_url_raw( $this->get_image_url( $settings['image'] ) ),
			'type' => 'image',
		];

		return $image_settings['url'] ? $image_settings : [];
	}

	private function get_image_attachment_id( array $image_settings ): int {
		$attachment_id = ! empty( $image_settings['id'] ) ? $image_settings['id'] : ( $image_settings['src']['id'] ?? 0 );

		return max( 0, (int) $attachment_id );
	}

	private function get_image_url( array $image_settings ): string {
		if ( ! empty( $image_settings['url'] ) ) {
			return $image_settings['url'];
		}

		if ( ! empty( $image_settings['src'] ) ) {
			return $this->get_atomic_image_url( $image_settings );
		}

		return '';
	}

	private function get_atomic_image_url( array $image_settings ): string {
		$default = $image_settings['src']['url'] ?? '';

		return $default;
	}

	private function get_video_settings( $settings ) {
		$input_url = esc_url_raw( $settings['video_url'] );
		if ( ! $input_url ) {
			return '';
		}

		$video_properties = Embed::get_video_properties( $input_url );
		$video_url = null;
		if ( ! $video_properties ) {
			$video_type = 'hosted';
			$video_url = $input_url;
		} else {
			$video_type = $video_properties['provider'];
			$video_url = Embed::get_embed_url( $input_url );
		}

		if ( null === $video_url ) {
			return '';
		}

		return [
			'type' => 'video',
			'videoType' => $video_type,
			'url' => esc_url_raw( $video_url ),
		];
	}

	public function render() {
		$settings = $this->get_settings();

		$value = [];

		if ( ! $settings['type'] ) {
			return;
		}

		if ( 'image' === $settings['type'] && $settings['image'] ) {
			$value = $this->get_image_settings( $settings );
		} elseif ( 'video' === $settings['type'] && $settings['video_url'] ) {
			$value = $this->get_video_settings( $settings );
		}

		if ( ! $value ) {
			return;
		}

		// PHPCS - the method Plugin::elementor()->frontend->create_action_hash is safe.
		echo Plugin::elementor()->frontend->create_action_hash( 'lightbox', $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
