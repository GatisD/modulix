<?php
namespace Modulix\Modules\Forms\Controls;
// Modulix: derived from Elementor Pro 4.0.4, modules/forms/controls/fields-map.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Control_Repeater;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class Fields_Map
 * @package Modulix\Modules\Forms\Controls
 *
 * each item needs the following properties:
 *   remote_id,
 *   remote_label
 *   remote_type
 *   remote_required
 *   local_id
 */
class Fields_Map extends Control_Repeater {

	const CONTROL_TYPE = 'fields_map';

	public function get_type() {
		return self::CONTROL_TYPE;
	}

	protected function get_default_settings() {
		return array_merge( parent::get_default_settings(), [
			'render_type' => 'none',
			'fields' => [
				[
					'name' => 'remote_id',
					'type' => Controls_Manager::HIDDEN,
				],
				[
					'name' => 'local_id',
					'type' => Controls_Manager::SELECT,
				],
			],
		] );
	}
}
