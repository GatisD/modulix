<?php
namespace Modulix\Modules\Forms\Actions;
// Modulix: derived from Elementor Pro 4.0.4, modules/forms/actions/redirect.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use Modulix\Modules\Forms\Classes\Action_Base;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Redirect extends Action_Base {

	public function get_name() {
		return 'redirect';
	}

	public function get_label() {
		return esc_html__( 'Redirect', 'modulix' );
	}

	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_redirect',
			[
				'label' => esc_html__( 'Redirect', 'modulix' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'redirect_to',
			[
				'label' => esc_html__( 'Redirect To', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'https://your-link.com', 'modulix' ),
				'ai' => [
					'active' => false,
				],
				'dynamic' => [
					'active' => true,
					'categories' => [
						TagsModule::POST_META_CATEGORY,
						TagsModule::TEXT_CATEGORY,
						TagsModule::URL_CATEGORY,
					],
				],
				'label_block' => true,
				'render_type' => 'none',
				'classes' => 'elementor-control-direction-ltr',
			]
		);

		$widget->end_controls_section();
	}

	public function on_export( $element ) {
		unset(
			$element['settings']['redirect_to']
		);

		return $element;
	}

	public function run( $record, $ajax_handler ) {
		$redirect_to = $record->get_form_settings( 'redirect_to' );

		$redirect_to = $record->replace_setting_shortcodes( $redirect_to, true );

		$redirect_to = esc_url_raw( $redirect_to );

		if ( ! empty( $redirect_to ) && filter_var( $redirect_to, FILTER_VALIDATE_URL ) ) {
			$ajax_handler->add_response_data( 'redirect_url', $redirect_to );
		}
	}
}
