<?php
namespace Modulix\Core\Data;
// Modulix: derived from Elementor Pro 4.0.4, core/data/controller.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\LoopFilter\Data\Endpoints\Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class Controller {

	private $endpoints = [];

	abstract public function get_name();

	public function get_namespace() {
		return 'elementor-pro/v1';
	}

	abstract protected function register_endpoints();

	protected function register_endpoint( $endpoint ) {
		$endpoint_instance = new $endpoint( $this );

		$this->endpoints[ $endpoint_instance->get_name() ] = $endpoint_instance;
	}

	public function __construct() {
		add_action( 'rest_api_init', function () {
			$this->register_endpoints(); // Because 'register_endpoints' is protected.
		} );
	}
}
