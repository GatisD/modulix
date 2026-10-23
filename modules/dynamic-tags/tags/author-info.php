<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/author-info.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DynamicTags\Tags\Base\Author_Tag;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Author_Info extends Author_Tag {

	public function get_name() {
		return 'author-info';
	}

	public function get_title() {
		return esc_html__( 'Author Info', 'modulix' );
	}

	public function get_editor_config() {
		$config = parent::get_editor_config();

		$config['display_conditions'] = [
			'author_info_bio' => [
				'label' => esc_html__( 'Author Bio', 'modulix' ),
				'settings' => [ 'key' => 'description' ],
				'group' => 'author',
			],
			'author_info_email' => [
				'label' => esc_html__( 'Author Email', 'modulix' ),
				'settings' => [ 'key' => 'email' ],
				'group' => 'author',
			],
			'author_info_website' => [
				'label' => esc_html__( 'Author Website', 'modulix' ),
				'settings' => [ 'key' => 'url' ],
				'group' => 'author',
			],
		];

		return $config;
	}

	protected function register_controls() {
		$this->add_control(
			'key',
			[
				'label' => esc_html__( 'Field', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'description',
				'options' => $this->get_key_options(),
			]
		);
	}

	private function get_key_options() {
		$options = [];
		$options['description'] = esc_html__( 'Bio', 'modulix' );

		if ( current_user_can( 'manage_options' ) ) {
			$options['email'] = esc_html__( 'Email', 'modulix' );
		}

		$options['url'] = esc_html__( 'Website', 'modulix' );

		return $options;
	}
}
