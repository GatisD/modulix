<?php

namespace Modulix\Modules\Popup\DisplaySettings;
// Modulix: derived from Elementor Pro 4.0.4, modules/popup/display-settings/triggers.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Core\Utils\Hints;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Triggers extends Base {

	/**
	 * Get element name.
	 *
	 * Retrieve the element name.
	 *
	 * @since  2.4.0
	 * @access public
	 *
	 * @return string The name.
	 */
	public function get_name() {
		return 'popup_triggers';
	}

	protected function register_controls() {
		$this->start_controls_section( 'triggers' );

		$this->start_settings_group( 'page_load', esc_html__( 'On Page Load', 'modulix' ) );

		$this->add_settings_group_control(
			'delay',
			[
				'type' => Controls_Manager::NUMBER,
				'label' => esc_html__( 'Within', 'modulix' ) . ' (sec)',
				'default' => 0,
				'min' => 0,
				'step' => 0.1,
			]
		);

		$this->end_settings_group();

		$this->start_settings_group( 'scrolling', esc_html__( 'On Scroll', 'modulix' ) );

		$this->add_settings_group_control(
			'direction',
			[
				'type' => Controls_Manager::SELECT,
				'label' => esc_html__( 'Direction', 'modulix' ),
				'default' => 'down',
				'options' => [
					'down' => esc_html__( 'Down', 'modulix' ),
					'up' => esc_html__( 'Up', 'modulix' ),
				],
			]
		);

		$this->add_settings_group_control(
			'offset',
			[
				'type' => Controls_Manager::NUMBER,
				'label' => esc_html__( 'Within', 'modulix' ) . ' (%)',
				'default' => 50,
				'min' => 1,
				'max' => 100,
				'condition' => [
					'direction' => 'down',
				],
			]
		);

		$this->end_settings_group();

		$this->start_settings_group( 'scrolling_to', esc_html__( 'On Scroll To Element', 'modulix' ) );

		$this->add_settings_group_control(
			'selector',
			[
				'type' => Controls_Manager::TEXT,
				'label' => esc_html__( 'Selector', 'modulix' ),
				'placeholder' => '.my-class',
				'ai' => [
					'active' => false,
				],
			],
		);

		$this->end_settings_group();

		$this->start_settings_group( 'click', esc_html__( 'On Click', 'modulix' ) );

		$this->add_settings_group_control(
			'times',
			[
				'label' => esc_html__( 'Clicks', 'modulix' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 1,
				'min' => 1,
			]
		);

		$this->end_settings_group();

		$this->start_settings_group( 'inactivity', esc_html__( 'After Inactivity', 'modulix' ) );

		$this->add_settings_group_control(
			'time',
			[
				'type' => Controls_Manager::NUMBER,
				'label' => esc_html__( 'Within', 'modulix' ) . ' (sec)',
				'default' => 30,
				'min' => 1,
				'step' => 0.1,
			]
		);

		$this->end_settings_group();

		$this->start_settings_group( 'exit_intent', esc_html__( 'On Page Exit Intent', 'modulix' ) );

		$this->end_settings_group();

		$this->start_settings_group( 'adblock_detection', esc_html__( 'AdBlock Detection', 'modulix' ) );

		$this->add_settings_group_control(
			'delay',
			[
				'type' => Controls_Manager::NUMBER,
				'label' => esc_html__( 'Within', 'modulix' ) . ' (sec)',
				'default' => 0,
				'min' => 0,
				'step' => 0.1,
			]
		);

		$this->end_settings_group();

		$this->end_controls_section();
	}
}
