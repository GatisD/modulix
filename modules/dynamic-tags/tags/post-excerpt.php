<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/post-excerpt.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Core\Utils;
use Modulix\Modules\DynamicTags\Tags\Base\Pro_Tag;
use Modulix\Modules\DynamicTags\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Post_Excerpt extends Pro_Tag {
	public function get_name() {
		return 'post-excerpt';
	}

	public function get_title() {
		return esc_html__( 'Post Excerpt', 'modulix' );
	}

	public function get_group() {
		return Module::POST_GROUP;
	}

	protected function register_controls() {

		$this->add_control(
			'max_length',
			[
				'label' => esc_html__( 'Excerpt Length', 'modulix' ),
				'type' => Controls_Manager::NUMBER,
				'default' => null,
				'min' => 0,
				'step' => 1,
				'should_force_int' => true,
			]
		);

		$this->add_control(
			'apply_to_post_content',
			[
				'label' => esc_html__( 'Apply to post content', 'modulix' ),
				'atomic_label' => esc_html__( 'Apply post content', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'modulix' ),
				'label_off' => esc_html__( 'No', 'modulix' ),
				'default' => 'no',
			]
		);
	}

	public function get_categories() {
		return [ Module::TEXT_CATEGORY ];
	}

	public function should_get_excerpt_from_post_content( $settings ) {
		return 'yes' === $settings['apply_to_post_content'] || true === $settings['apply_to_post_content'];
	}

	public function is_post_excerpt_valid( $settings, $post ) {
		if ( ! $post ) {
			return false;
		}

		if ( empty( $post->post_excerpt ) && ! $this->should_get_excerpt_from_post_content( $settings ) ) {
			return false;
		}

		if ( empty( $post->post_excerpt ) && empty( $post->post_content ) && $this->should_get_excerpt_from_post_content( $settings ) ) {
			return false;
		}

		if ( empty( $post->post_excerpt ) && empty( $post->post_content ) ) {
			return false;
		}

		return true;
	}

	public function get_post_excerpt( $settings, $post ) {
		$post_excerpt = $post->post_excerpt ?? '';

		if ( empty( $post_excerpt ) && ! empty( $post->post_content ) && $this->should_get_excerpt_from_post_content( $settings ) ) {
			$post_excerpt = apply_filters( 'the_excerpt', get_the_excerpt( $post ) );
		}

		return $post_excerpt;
	}

	public function render() {
		// Allow only a real `post_excerpt` and not the trimmed `post_content` from the `get_the_excerpt` filter
		$post = get_post();
		$settings = $this->get_settings_for_display();

		if ( ! $this->is_post_excerpt_valid( $settings, $post ) ) {
			return;
		}

		$max_length = (int) $settings['max_length'];
		$excerpt = $this->get_post_excerpt( $settings, $post );

		$excerpt = Utils::trim_words( $excerpt, $max_length );

		echo wp_kses_post( $excerpt );
	}
}
