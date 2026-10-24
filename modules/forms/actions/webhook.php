<?php
namespace Modulix\Modules\Forms\Actions;
// Modulix: derived from Elementor Pro 4.0.4, modules/forms/actions/webhook.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\Forms\Classes\Action_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Webhook extends Action_Base {

	public function get_name() {
		return 'webhook';
	}

	public function get_label() {
		return esc_html__( 'Webhook', 'modulix' );
	}

	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_webhook',
			[
				'label' => esc_html__( 'Webhook', 'modulix' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'webhooks',
			[
				'label' => esc_html__( 'Webhook URL', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'https://your-webhook-url.com', 'modulix' ),
				'ai' => [
					'active' => false,
				],
				'label_block' => true,
				'separator' => 'before',
				'description' => esc_html__( 'Enter the integration URL (like Zapier) that will receive the form\'s submitted data.', 'modulix' ),
				'render_type' => 'none',
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'webhooks_advanced_data',
			[
				'label' => esc_html__( 'Advanced Data', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'no',
				'render_type' => 'none',
			]
		);

		$widget->end_controls_section();
	}

	public function on_export( $element ) {}

	public function run( $record, $ajax_handler ) {
		$settings = $record->get( 'form_settings' );

		if ( empty( $settings['webhooks'] ) ) {
			return;
		}

		if ( isset( $settings['webhooks_advanced_data'] ) && 'yes' === $settings['webhooks_advanced_data'] ) {
			$body['form'] = [
				'id' => $settings['id'],
				'name' => $settings['form_name'],
			];

			$body['fields'] = $record->get( 'fields' );
			$body['meta'] = $record->get( 'meta' );
		} else {
			$body = $record->get_formatted_data( true );
			$body['form_id'] = $settings['id'];
			$body['form_name'] = $settings['form_name'];
		}

		$args = [
			'body' => $body,
		];

		/**
		 * Forms webhook request arguments.
		 *
		 * Filters the request arguments delivered by the form webhook when executing
		 * an ajax request.
		 *
		 * @since 1.0.0
		 *
		 * @param array       $args   Webhook request arguments.
		 * @param Form_Record $record An instance of the form record.
		 */
		$args = apply_filters( 'elementor_pro/forms/webhooks/request_args', $args, $record );

		$response = wp_safe_remote_post( $settings['webhooks'], $args );

		/**
		 * Elementor form webhook response.
		 *
		 * Fires when the webhook response is retrieved by Elementor forms. This hook
		 * allows developers to add functionality after recieving webhook responses.
		 *
		 * @since 1.0.0
		 *
		 * @param \WP_Error|array $response The response or WP_Error on failure.
		 * @param Form_Record     $record   An instance of the form record.
		 */
		do_action( 'elementor_pro/forms/webhooks/response', $response, $record );

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			throw new \Exception( 'Webhook error.' );
		}
	}
}
