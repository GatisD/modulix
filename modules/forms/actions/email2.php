<?php
namespace Modulix\Modules\Forms\Actions;
// Modulix: derived from Elementor Pro 4.0.4, modules/forms/actions/email2.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Email2 extends Email {

	public function get_name() {
		return 'email2';
	}

	public function get_label() {
		return esc_html__( 'Email 2', 'modulix' );
	}

	protected function get_control_id( $control_id ) {
		return $control_id . '_2';
	}

	protected function get_reply_to( $record, $fields ) {
		return isset( $fields['email_reply_to'] ) ? $fields['email_reply_to'] : '';
	}

	public function register_settings_section( $widget ) {
		parent::register_settings_section( $widget );

		$admin_email = get_option( 'admin_email' );

		$widget->update_control(
			$this->get_control_id( 'email_reply_to' ),
			[
				'type' => Controls_Manager::TEXT,
				'default' => $admin_email,
				'placeholder' => $admin_email,
				'ai' => [
					'active' => false,
				],
			]
		);

		$widget->update_control(
			$this->get_control_id( 'form_metadata' ),
			[
				'default' => [],
			]
		);
	}
}
