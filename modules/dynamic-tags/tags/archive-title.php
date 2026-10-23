<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/archive-title.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): loop builder taxonomy branch removed until the loop builder is lifted.

use Elementor\Controls_Manager;
use Modulix\Modules\DynamicTags\Tags\Base\Pro_Tag;
use Modulix\Core\Utils;
use Modulix\Modules\DynamicTags\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Archive_Title extends Pro_Tag {
	public function get_name() {
		return 'archive-title';
	}

	public function get_title() {
		return esc_html__( 'Archive Title', 'modulix' );
	}

	public function get_group() {
		return Module::ARCHIVE_GROUP;
	}

	public function get_categories() {
		return [ Module::TEXT_CATEGORY ];
	}

	public function get_editor_config() {
		$config = parent::get_editor_config();

		$config['display_conditions'] = [
			'archive_title' => [
				'label' => esc_html__( 'Title', 'modulix' ),
				'settings' => [ 'include_context' => 'no' ],
				'group' => 'archive',
			],
		];

		return $config;
	}

	public function render() {
		$this->render_post();
	}

	private function render_post() {
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
				'default' => 'yes',
			]
		);
	}
}
