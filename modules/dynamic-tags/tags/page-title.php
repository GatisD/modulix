<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/page-title.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DynamicTags\Tags\Base\Pro_Tag;
use Modulix\Core\Utils;
use Modulix\Modules\DynamicTags\Module;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Page_Title extends Pro_Tag {
	public function get_name() {
		return 'page-title';
	}

	public function get_title() {
		return esc_html__( 'Page Title', 'modulix' );
	}

	public function get_group() {
		return Module::SITE_GROUP;
	}

	public function get_categories() {
		return [ Module::TEXT_CATEGORY ];
	}

	public function render() {
		if ( is_home() && 'yes' !== $this->get_settings( 'show_home_title' ) ) {
			return;
		}

		if ( Plugin::elementor()->common ) {
			$current_action_data = Plugin::elementor()->common->get_component( 'ajax' )->get_current_action_data();

			if ( $current_action_data && 'render_tags' === $current_action_data['action'] ) {
				// Override the global $post for the render.
				query_posts(
					[
						'p' => get_the_ID(),
						'post_type' => 'any',
					]
				);
			}
		}

		$include_context = $this->get_settings( 'include_context' );
		$is_included = 'yes' === $include_context || true === $include_context;

		$title = Utils::get_page_title( $is_included );

		echo wp_kses_post( $title );
	}

	protected function register_controls() {
		$this->add_control(
			'include_context',
			[
				'label' => esc_html__( 'Include Context', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
			]
		);

		$this->add_control(
			'show_home_title',
			[
				'label' => esc_html__( 'Show Home Title', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
			]
		);
	}
}
