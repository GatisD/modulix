<?php
namespace Modulix\Core\Behaviors;
// Modulix: derived from Elementor Pro 4.0.4, core/behaviors/feature-lock.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Feature_Lock implements Temp_Lock_Behavior {

	private $config;

	public function __construct( $config = [] ) {
		$this->config = $config;
	}

	public function is_locked() {
		return false;
	}

	public function get_config() {
		return [
			'is_locked' => false,
		];
	}
}
