<?php
namespace Modulix\Core\Data\Endpoints;
// Modulix: derived from Elementor Pro 4.0.4, core/data/endpoints/base.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Core\Data\Controller;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Base {

	protected $controller;

	protected function register() {}

	/**
	 * Endpoint constructor.
	 *
	 * runs `$this->register()`.
	 *
	 * @param Controller $controller
	 */
	public function __construct( Controller $controller ) {
		$this->controller = $controller;

		$this->register();
	}
}
