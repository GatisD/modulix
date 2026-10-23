<?php
namespace Modulix\Modules\DynamicTags\ACF\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/acf/tags/acf-date-time.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\DynamicTags\ACF\Dynamic_Value_Provider;
use Modulix\Modules\DynamicTags\ACF\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class ACF_Date_Time extends Base_ACF_Tag {

	/**
	 * @var Dynamic_Value_Provider|mixed
	 */
	private $dynamic_value_provider;

	public function get_name() {
		return 'acf-date-time';
	}

	public function get_title() {
		return esc_html__( 'ACF', 'modulix' ) . ' ' . esc_html__( 'Date Time Field', 'modulix' );
	}

	public function get_group() {
		return Module::ACF_GROUP;
	}

	public function get_categories() {
		return [
			Module::DATETIME_CATEGORY,
		];
	}

	/**
	 * @param array $options
	 *
	 * @return string - date time in format Y-m-d H:i:s
	 */
	public function get_value( array $options = [] ) {
		$field_settings = $this->dynamic_value_provider->get_value(
			$this->get_settings( 'key' )
		);

		if ( empty( $field_settings ) ) {
			return '';
		}

		$field = $field_settings[0];
		$value = '';

		if ( $field ) {
			$date_time = \DateTime::createFromFormat( $field['return_format'], $field['value'] );

			$value = $date_time instanceof \DateTime
				? $date_time->format( 'Y-m-d H:i:s' )
				: '';
		}

		if ( empty( $value ) && $this->get_settings( 'fallback' ) ) {
			$value = $this->get_settings( 'fallback' );
		}

		return wp_kses_post( $value );
	}

	public function get_panel_template_setting_key() {
		return 'key';
	}

	protected function register_controls() {
		Module::add_key_control( $this );

		$this->add_control(
			'fallback',
			[
				'type' => Controls_Manager::DATE_TIME,
				'label' => esc_html__( 'Fallback', 'modulix' ),
			]
		);
	}

	public function get_supported_fields() {
		return [
			'date_time_picker',
		];
	}

	public function __construct( array $data = [], $dynamic_value_provider = null ) {
		parent::__construct( $data );

		$this->dynamic_value_provider = $dynamic_value_provider ?? new Dynamic_Value_Provider();
	}
}
