<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/request-parameter.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-24): request values are limited to scalar text and escaped at HTML output.

use Elementor\Controls_Manager;
use Modulix\Modules\DynamicTags\Tags\Base\Pro_Tag;
use Modulix\Modules\DynamicTags\Module;
use Modulix\Core\Utils;
use Modulix\Security\DynamicContent;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Request_Parameter extends Pro_Tag {
	public function get_name() {
		return 'request-arg';
	}

	public function get_title() {
		return esc_html__( 'Request Parameter', 'modulix' );
	}

	public function get_group() {
		return Module::SITE_GROUP;
	}

	public function get_categories() {
		return [
			Module::TEXT_CATEGORY,
			Module::POST_META_CATEGORY,
		];
	}

	public function render() {
		$settings = $this->get_settings();
		$request_type = strtoupper( DynamicContent::scalar_text( $settings['request_type'] ?? '' ) );
		$param_name = DynamicContent::scalar_text( $settings['param_name'] ?? '' );
		$value = '';

		if ( ! $param_name || ! $request_type ) {
			return '';
		}

		switch ( $request_type ) {
			case 'POST':
				$value = Utils::_unstable_get_super_global_value( $_POST, $param_name ) ?? ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
				break;
			case 'GET':
				$value = Utils::_unstable_get_super_global_value( $_GET, $param_name ) ?? ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				break;
			case 'QUERY_VAR':
				$value = get_query_var( $param_name );
				break;
		}
		$value = DynamicContent::scalar_text( wp_unslash( $value ) );
		echo esc_html( $value );
	}

	protected function register_controls() {
		$this->add_control(
			'request_type',
			[
				'label'   => esc_html__( 'Type', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'get',
				'options' => [
					'get' => 'Get',
					'post' => 'Post',
					'query_var' => 'Query Var',
				],
			]
		);
		$this->add_control(
			'param_name',
			[
				'label'   => esc_html__( 'Parameter Name', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'ai' => [
					'active' => false,
				],
			]
		);
	}
}
