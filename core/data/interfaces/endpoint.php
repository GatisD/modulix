<?php
namespace Modulix\Core\Data\Interfaces;
// Modulix: derived from Elementor Pro 4.0.4, core/data/interfaces/endpoint.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

interface Endpoint {
	/**
	 * @return string The interface ID
	 */
	public function get_name(): string;

	/**
	 * @return string The route slug which will be used to access the endpoint by URL.
	 */
	public function get_route(): string;
}
