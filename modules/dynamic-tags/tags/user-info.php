<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/user-info.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-24): protected user metadata and non-scalar values are not rendered.

use Elementor\Controls_Manager;
use Modulix\Modules\DynamicTags\Tags\Base\Pro_Tag;
use Modulix\Modules\DynamicTags\Module;
use Modulix\Security\DynamicContent;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class User_Info extends Pro_Tag {

	public function get_name() {
		return 'user-info';
	}

	public function get_title() {
		return esc_html__( 'User Info', 'modulix' );
	}

	public function get_group() {
		return Module::SITE_GROUP;
	}

	public function get_categories() {
		return [ Module::TEXT_CATEGORY ];
	}

	public function render() {
		$type = DynamicContent::scalar_text( $this->get_settings( 'type' ) );
		$user = wp_get_current_user();
		if ( empty( $type ) || 0 === $user->ID ) {
			return;
		}

		$value = '';
		switch ( $type ) {
			case 'login':
			case 'email':
			case 'url':
			case 'nicename':
				$field = 'user_' . $type;
				$value = isset( $user->$field ) ? $user->$field : '';
				break;
			case 'id':
				$value = $user->ID;
				break;
			case 'description':
			case 'first_name':
			case 'last_name':
			case 'display_name':
				$value = isset( $user->$type ) ? $user->$type : '';
				break;
			case 'meta':
				$key = DynamicContent::scalar_text( $this->get_settings( 'meta_key' ) );
				if ( DynamicContent::user_meta_allowed( $key, is_protected_meta( $key, 'user' ) ) ) {
					$value = get_user_meta( $user->ID, $key, true );
				}
				break;
		}

		echo esc_html( DynamicContent::scalar_text( $value ) );
	}

	public function get_panel_template_setting_key() {
		return 'type';
	}

	protected function register_controls() {
		$this->add_control(
			'type',
			[
				'label' => esc_html__( 'Field', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'' => esc_html__( 'Choose', 'modulix' ),
					'id' => esc_html__( 'ID', 'modulix' ),
					'display_name' => esc_html__( 'Display Name', 'modulix' ),
					'login' => esc_html__( 'Username', 'modulix' ),
					'first_name' => esc_html__( 'First Name', 'modulix' ),
					'last_name' => esc_html__( 'Last Name', 'modulix' ),
					'description' => esc_html__( 'Bio', 'modulix' ),
					'email' => esc_html__( 'Email', 'modulix' ),
					'url' => esc_html__( 'Website', 'modulix' ),
					'meta' => esc_html__( 'User Meta', 'modulix' ),
				],
			]
		);

		$this->add_control(
			'meta_key',
			[
				'label' => esc_html__( 'Meta Key', 'modulix' ),
				'condition' => [
					'type' => 'meta',
				],
				'ai' => [
					'active' => false,
				],
			]
		);
	}
}
