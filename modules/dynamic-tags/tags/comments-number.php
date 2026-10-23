<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/comments-number.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DynamicTags\Tags\Base\Pro_Tag;
use Modulix\Modules\DynamicTags\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Comments_Number extends Pro_Tag {

	public function get_name() {
		return 'comments-number';
	}

	public function get_title() {
		return esc_html__( 'Comments Number', 'modulix' );
	}

	public function get_group() {
		return Module::COMMENTS_GROUP;
	}

	public function get_categories() {
		return [
			Module::TEXT_CATEGORY,
			Module::NUMBER_CATEGORY,
		];
	}

	protected function register_controls() {
		$this->add_control(
			'format_no_comments',
			[
				'label' => esc_html__( 'No Comments Format', 'modulix' ),
				'default' => esc_html__( 'No Responses', 'modulix' ),
				'ai' => [
					'active' => false,
				],
			]
		);

		$this->add_control(
			'format_one_comments',
			[
				'label' => esc_html__( 'One Comment Format', 'modulix' ),
				'default' => esc_html__( 'One Response', 'modulix' ),
				'ai' => [
					'active' => false,
				],
			]
		);

		$this->add_control(
			'format_many_comments',
			[
				'label' => esc_html__( 'Many Comment Format', 'modulix' ),
				'default' => esc_html__( '{number} Responses', 'modulix' ),
				'ai' => [
					'active' => false,
				],
			]
		);

		$this->add_control(
			'link_to',
			[
				'label' => esc_html__( 'Link', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'' => esc_html__( 'None', 'modulix' ),
					'comments_link' => esc_html__( 'Comments Link', 'modulix' ),
				],
			]
		);
	}

	public function render() {
		$settings = $this->get_settings();

		$comments_number = get_comments_number();

		if ( ! $comments_number ) {
			$count = $settings['format_no_comments'];
		} elseif ( 1 === $comments_number ) {
			$count = $settings['format_one_comments'];
		} else {
			$count = strtr( $settings['format_many_comments'], [
				'{number}' => number_format_i18n( $comments_number ),
			] );
		}

		if ( 'comments_link' === $this->get_settings( 'link_to' ) ) {
			$count = sprintf( '<a href="%s">%s</a>', get_comments_link(), $count );
		}

		echo wp_kses_post( $count );
	}
}
