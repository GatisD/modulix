<?php
namespace Modulix\Core;
// Modulix: derived from Elementor Pro 4.0.4, core/php-api.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This class is responsible for the interaction with PHP Core API.
 * The main benefit is making it easy to mock in testing.
 */
class PHP_Api {

	/**
	 * @param $from
	 * @param $to
	 *
	 * @return bool
	 */
	public function move_uploaded_file( $from, $to ) {
		return @ move_uploaded_file( $from, $to );
	}
}
