<?php

namespace Modulix\Modules\Popup\DisplaySettings;
// Modulix: derived from Elementor Pro 4.0.4, modules/popup/display-settings/timing.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Plugin;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Timing extends Base {

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
		return 'popup_timing';
	}

	protected function register_controls() {
		$this->start_controls_section( 'timing' );

		$this->start_settings_group( 'page_views', esc_html__( 'Show after X page views', 'modulix' ) );

		$this->add_settings_group_control(
			'views',
			[
				'type' => Controls_Manager::NUMBER,
				'label' => esc_html__( 'Page Views', 'modulix' ),
				'default' => 3,
				'min' => 1,
			]
		);

		$this->end_settings_group();

		$this->start_settings_group( 'sessions', esc_html__( 'Show after X sessions', 'modulix' ) );

		$this->add_settings_group_control(
			'sessions',
			[
				'type' => Controls_Manager::NUMBER,
				'label' => esc_html__( 'Sessions', 'modulix' ),
				'default' => 2,
				'min' => 1,
			]
		);

		$this->end_settings_group();

		$this->start_settings_group( 'times', esc_html__( 'Show up to X times', 'modulix' ) );

		$this->add_settings_group_control(
			'times',
			[
				'type' => Controls_Manager::NUMBER,
				'label' => esc_html__( 'Times', 'modulix' ),
				'default' => 3,
				'min' => 1,
			]
		);

		$this->add_settings_group_control(
			'period',
			[
				'type' => Controls_Manager::SELECT,
				'label' => esc_html__( 'Per', 'modulix' ),
				'default' => '', // Backward Compatibility - Persisting is old default value.
				'options' => [
					'' => esc_html__( 'Persisting', 'modulix' ),
					'session' => esc_html__( 'Session', 'modulix' ),
					'day' => esc_html__( 'Day', 'modulix' ),
					'week' => esc_html__( 'Week', 'modulix' ),
					'month' => esc_html__( 'Month', 'modulix' ),
				],
			]
		);

		$this->add_settings_group_control(
			'count',
			[
				'type' => Controls_Manager::SELECT,
				'label' => esc_html__( 'Count', 'modulix' ),
				'options' => [
					'' => esc_html__( 'On Open', 'modulix' ),
					'close' => esc_html__( 'On Close', 'modulix' ),
				],
			]
		);

		$this->end_settings_group();

		$this->start_settings_group( 'url', esc_html__( 'When arriving from specific URL', 'modulix' ) );

		$this->add_settings_group_control(
			'action',
			[
				'type' => Controls_Manager::SELECT,
				'default' => 'show',
				'options' => [
					'show' => esc_html__( 'Show', 'modulix' ),
					'hide' => esc_html__( 'Hide', 'modulix' ),
					'regex' => esc_html__( 'Regex', 'modulix' ),
				],
			]
		);

		$this->add_settings_group_control(
			'url',
			[
				'type' => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'URL', 'modulix' ),
			]
		);

		$this->end_settings_group();

		$this->start_settings_group( 'sources', esc_html__( 'Show when arriving from', 'modulix' ) );

		$this->add_settings_group_control(
			'sources',
			[
				'type' => Controls_Manager::SELECT2,
				'multiple' => true,
				'default' => [ 'search', 'external', 'internal' ],
				'options' => [
					'search' => esc_html__( 'Search Engines', 'modulix' ),
					'external' => esc_html__( 'External Links', 'modulix' ),
					'internal' => esc_html__( 'Internal Links', 'modulix' ),
				],
			]
		);

		$this->end_settings_group();

		$this->start_settings_group( 'logged_in', esc_html__( 'Hide for logged in users', 'modulix' ) );

		$this->add_settings_group_control(
			'users',
			[
				'type' => Controls_Manager::SELECT,
				'default' => 'all',
				'options' => [
					'all' => esc_html__( 'All Users', 'modulix' ),
					'custom' => esc_html__( 'Custom', 'modulix' ),
				],
			]
		);

		global $wp_roles;

		$roles = array_map( function( $role ) {
			return $role['name'];
		}, $wp_roles->roles );

		$this->add_settings_group_control(
			'roles',
			[
				'type' => Controls_Manager::SELECT2,
				'multiple' => true,
				'default' => [],
				'options' => $roles,
				'select2options' => [
					'placeholder' => esc_html__( 'Select Roles', 'modulix' ),
				],
				'condition' => [
					'users' => 'custom',
				],
			]
		);

		$this->end_settings_group();

		$available_devices = [
			'desktop' => esc_html__( 'Desktop', 'modulix' ),
		];

		$default_devices = [ 'desktop' ];

		$active_breakpoints = Plugin::elementor()->breakpoints->get_active_breakpoints();

		foreach ( $active_breakpoints as $breakpoint_key => $breakpoint ) {
			$available_devices[ $breakpoint_key ] = $breakpoint->get_label();
			$default_devices[] = $breakpoint_key;
		}

		$this->start_settings_group( 'devices', esc_html__( 'Show on devices', 'modulix' ) );

		$this->add_settings_group_control(
			'devices',
			[
				'type' => Controls_Manager::SELECT2,
				'multiple' => true,
				'default' => $default_devices,
				'options' => $available_devices,
			]
		);

		$this->end_settings_group();

		$this->start_settings_group( 'browsers', esc_html__( 'Show on browsers', 'modulix' ) );

		$this->add_settings_group_control(
			'browsers',
			[
				'type' => Controls_Manager::SELECT,
				'default' => 'all',
				'options' => [
					'all' => esc_html__( 'All Browsers', 'modulix' ),
					'custom' => esc_html__( 'Custom', 'modulix' ),
				],
			]
		);

		$this->add_settings_group_control(
			'browsers_options',
			[
				'type' => Controls_Manager::SELECT2,
				'multiple' => true,
				'default' => [],
				'options' => [
					'ie' => esc_html__( 'Internet Explorer', 'modulix' ),
					'chrome' => esc_html__( 'Chrome', 'modulix' ),
					'edge' => esc_html__( 'Edge', 'modulix' ),
					'firefox' => esc_html__( 'Firefox', 'modulix' ),
					'safari' => esc_html__( 'Safari', 'modulix' ),
				],
				'condition' => [
					'browsers' => 'custom',
				],
			]
		);

		$this->end_settings_group();

		$this->start_settings_group( 'schedule', esc_html__( 'Schedule date and time', 'modulix' ) );

		$this->add_settings_group_control(
			'timezone',
			[
				'type' => Controls_Manager::SELECT,
				'label' => esc_html__( 'Timezone', 'modulix' ),
				'default' => 'site',
				'options' => [
					'site' => esc_html__( 'Site', 'modulix' ),
					'visitor' => esc_html__( 'Visitor', 'modulix' ),
				],
			]
		);

		$this->add_settings_group_control(
			'start_date',
			[
				'label' => esc_html__( 'Start', 'modulix' ),
				'type' => Controls_Manager::DATE_TIME,
				'picker_options' => [
					'enableTime' => true,
					'minDate' => 'today',
				],
				'validation' => [
					'date_time' => [
						'control_name' => $this->get_prefixed_control_id( 'end_date' ),
						'operator' => '<=',
					],
				],
			]
		);

		$this->add_settings_group_control(
			'end_date',
			[
				'label' => esc_html__( 'End', 'modulix' ),
				'type' => Controls_Manager::DATE_TIME,
				'picker_options' => [
					'enableTime' => true,
					'minDate' => 'today',
				],
				'validation' => [
					'date_time' => [
						'control_name' => $this->get_prefixed_control_id( 'start_date' ),
						'operator' => '>=',
					],
				],
			]
		);

		$datetime = new \DateTime( 'now', new \DateTimeZone( wp_timezone_string() ) );
		$datetime = $datetime->format( 'Y-m-d H:i:s' );

		$this->add_settings_group_control(
			'server_datetime',
			[
				'type' => Controls_Manager::HIDDEN,
				'default' => $datetime,
			]
		);

		$this->end_settings_group();

		$this->end_controls_section();
	}
}
